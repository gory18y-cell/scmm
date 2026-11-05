<?php
/**
 * ==============================================================================
 * CONTROLLER: PURCHASE ORDER & JADWAL KIRIM - MODUL 2 (LATIFAH NOOR AZIZAH)
 * ==============================================================================
 * Mengendalikan alur penerbitan dokumen PO, validasi server-side,
 * integrasi transaksi multi-item ACID, otorisasi role, dan transisi status PO.
 * ==============================================================================
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/PurchaseOrder.php';
require_once __DIR__ . '/../models/PurchaseOrderDetail.php';
require_once __DIR__ . '/../models/PurchaseOrderReceipt.php';
require_once __DIR__ . '/../models/Vendor.php';
require_once __DIR__ . '/../models/VendorMaterial.php';

class PurchaseOrderController extends Controller {
    private $poModel;
    private $podModel;
    private $receiptModel;
    private $vendorModel;
    private $vmModel;

    public function __construct() {
        Session::start();
        $this->poModel      = new PurchaseOrder();
        $this->podModel     = new PurchaseOrderDetail();
        $this->receiptModel = new PurchaseOrderReceipt();
        $this->vendorModel  = new Vendor();
        $this->vmModel      = new VendorMaterial();
    }

    /**
     * Halaman Utama: Daftar Seluruh Dokumen Purchase Order
     */
    public function index() {
        $orders = $this->poModel->getAll();
        $authUser = $this->getAuthUser();

        $data = [
            'pageTitle'    => 'Pengadaan Bahan Baku (Purchase Order)',
            'pageSubTitle' => 'Modul 2 (Latifah Noor Azizah - 43250555): Penerbitan PO Logam, Subtotal Presisi & Jadwal Kirim',
            'activeMenu'   => 'purchase-order',
            'orders'       => $orders,
            'csrfToken'    => Session::generateCsrfToken(),
            'flashSuccess' => Session::getFlash('success'),
            'flashError'   => Session::getFlash('error'),
            'authUser'     => $authUser
        ];

        $this->render('purchase_order/index', $data);
    }

    /**
     * Formulir Penerbitan Purchase Order Baru
     * HANYA diizinkan untuk role Admin dan Purchasing
     */
    public function create() {
        // RBAC: Hanya Admin dan Purchasing yang berhak menerbitkan Purchase Order
        $authUser = $this->requireRole(['Admin', 'Purchasing'], '/scm/purchase-order');

        // Ambil HANYA rekanan vendor yang berstatus 'Terverifikasi'
        $db = $this->poModel->getDb();
        $stmtV = $db->query("SELECT `id`, `kode_vendor`, `nama_vendor`, `spesialisasi_logam`, `status_verifikasi` 
                             FROM `vendors` 
                             WHERE `status_verifikasi` = 'Terverifikasi' 
                             ORDER BY `nama_vendor` ASC");
        $verifiedVendors = $stmtV->fetchAll();

        $oldInput = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);

        $data = [
            'pageTitle'       => 'Penerbitan Purchase Order Baru',
            'pageSubTitle'    => 'Modul 2: Form Input Dinamis Multi-Item Logam & Estimasi Kedatangan (ETA)',
            'activeMenu'      => 'purchase-order',
            'verifiedVendors' => $verifiedVendors,
            'authUser'        => $authUser,
            'csrfToken'       => Session::generateCsrfToken(),
            'flashError'      => Session::getFlash('error'),
            'old'             => $oldInput
        ];

        $this->render('purchase_order/create', $data);
    }

    /**
     * Endpoint API JSON: Mengambil Katalog Material Aktif Milik Vendor Tertentu
     *
     * @param int $vendorId
     */
    public function getMaterialsByVendor($vendorId) {
        header('Content-Type: application/json; charset=utf-8');

        $vendorId = (int)$vendorId;
        if ($vendorId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Vendor ID tidak valid.', 'data' => []]);
            exit;
        }

        $materials = $this->vmModel->getByVendorId($vendorId);
        echo json_encode([
            'success' => true,
            'count'   => count($materials),
            'data'    => $materials
        ]);
        exit;
    }

    /**
     * Memproses Penyimpanan Dokumen Purchase Order Baru (Header + Rincian Item)
     * Menggunakan Transaksi Penuh createWithDetails()
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/purchase-order');
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin atau Purchasing
        $authUser = $this->requireRole(['Admin', 'Purchasing'], '/scm/purchase-order');

        // 2. Validasi Keamanan CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Permintaan tidak sah (Token CSRF kedaluwarsa atau tidak valid). Silakan coba kembali.');
            header('Location: /scm/purchase-order/create');
            exit;
        }

        // Simpan input sementara untuk pemulihan jika terjadi error
        $_SESSION['old_input'] = $_POST;

        $vendorId          = (int)($_POST['vendor_id'] ?? 0);
        $tanggalPo         = trim($_POST['tanggal_po'] ?? '');
        $catatanPengiriman = trim($_POST['catatan_pengiriman'] ?? '');
        $rawItems          = $_POST['items'] ?? [];

        // 3. Validasi Server-Side Awal
        if ($vendorId <= 0) {
            Session::setFlash('error', 'Silakan pilih rekanan vendor yang berstatus Terverifikasi.');
            header('Location: /scm/purchase-order/create');
            exit;
        }

        if (empty($tanggalPo)) {
            Session::setFlash('error', 'Tanggal Purchase Order wajib diisi.');
            header('Location: /scm/purchase-order/create');
            exit;
        }

        if (empty($rawItems) || !is_array($rawItems)) {
            Session::setFlash('error', 'Minimal harus ada 1 item bahan baku logam dalam pesanan.');
            header('Location: /scm/purchase-order/create');
            exit;
        }

        // 4. Strukturkan dan Validasi Item Material
        $validatedItems = [];
        $selectedVmIds  = [];

        foreach ($rawItems as $idx => $row) {
            $vmId   = (int)($row['vendor_material_id'] ?? 0);
            $qtyRaw = trim((string)($row['jumlah_pesanan'] ?? ''));

            if ($vmId <= 0) {
                Session::setFlash('error', "Item ke-" . ($idx + 1) . ": Material bahan baku belum dipilih.");
                header('Location: /scm/purchase-order/create');
                exit;
            }

            // Cegah duplikasi material yang sama dalam satu PO
            if (in_array($vmId, $selectedVmIds, true)) {
                Session::setFlash('error', "Terdapat duplikasi pemilihan material pada daftar pesanan. Gabungkan kuantitas dalam 1 baris.");
                header('Location: /scm/purchase-order/create');
                exit;
            }
            $selectedVmIds[] = $vmId;

            if ($qtyRaw === '' || !is_numeric($qtyRaw)) {
                Session::setFlash('error', "Item ke-" . ($idx + 1) . ": Kuantitas pesanan harus berupa nilai angka valid.");
                header('Location: /scm/purchase-order/create');
                exit;
            }

            // Simpan pasangan material dan kuantitas (harga satuan murni diambil oleh model dari basis data)
            $validatedItems[] = [
                'vendor_material_id' => $vmId,
                'jumlah_pesanan'     => $qtyRaw
            ];
        }

        // 5. Eksekusi Transaksi Atomik Terpadu Melalui Model
        try {
            $headerData = [
                'vendor_id'          => $vendorId,
                'user_id'            => (int)$authUser['id'], // DARI SESI SERVER
                'tanggal_po'         => $tanggalPo,
                'catatan_pengiriman' => $catatanPengiriman ?: null
            ];

            // Panggil transaksi ACID model (mengunci baris, menghitung harga resmi DB, dan anti-konkurensi no_po)
            $result = $this->poModel->createWithDetails($headerData, $validatedItems);

            unset($_SESSION['old_input']);

            $formattedTotal = number_format((float)$result['total_nilai'], 2, ',', '.');
            Session::setFlash('success', "Purchase Order nomor '{$result['no_po']}' berhasil diterbitkan dengan total nilai Rp {$formattedTotal}. Estimasi kedatangan logistik: {$result['estimasi_kedatangan']}.");
            header('Location: /scm/purchase-order/detail/' . $result['po_id']);
            exit;

        } catch (InvalidArgumentException $e) {
            Session::setFlash('error', 'Validasi gagal: ' . $e->getMessage());
            header('Location: /scm/purchase-order/create');
            exit;

        } catch (RuntimeException $e) {
            Session::setFlash('error', 'Kendala transaksi pengadaan: ' . $e->getMessage());
            header('Location: /scm/purchase-order/create');
            exit;

        } catch (Exception $e) {
            Session::setFlash('error', 'Terjadi kesalahan sistem saat menyimpan dokumen PO: ' . $e->getMessage());
            header('Location: /scm/purchase-order/create');
            exit;
        }
    }

    /**
     * Halaman Detail Purchase Order Lengkap dengan Rincian Item & Aksi Status
     *
     * @param int $id
     */
    public function detail($id) {
        $id = (int)$id;
        $po = $this->poModel->getById($id);

        if (!$po) {
            Session::setFlash('error', "Dokumen Purchase Order dengan ID {$id} tidak ditemukan.");
            header('Location: /scm/purchase-order');
            exit;
        }

        $items    = $this->podModel->getByPoId($id);
        $receipts = $this->receiptModel->getReceiptsWithDetailsByPoId($id);

        $totalOrdered   = 0.0;
        $totalReceived  = 0.0;
        $totalRemaining = 0.0;

        foreach ($items as $item) {
            $totalOrdered   += (float)$item['jumlah_pesanan'];
            $totalReceived  += (float)$item['jumlah_diterima'];
            $totalRemaining += (float)$item['sisa_pesanan'];
        }

        $data = [
            'pageTitle'      => 'Detail Purchase Order: ' . $po['no_po'],
            'pageSubTitle'   => 'Status Dokumen: ' . $po['status_po'] . ' | Rekanan: ' . $po['nama_vendor'],
            'activeMenu'     => 'purchase-order',
            'po'             => $po,
            'items'          => $items,
            'receipts'       => $receipts,
            'totalOrdered'   => $totalOrdered,
            'totalReceived'  => $totalReceived,
            'totalRemaining' => $totalRemaining,
            'csrfToken'      => Session::generateCsrfToken(),
            'flashSuccess'   => Session::getFlash('success'),
            'flashError'     => Session::getFlash('error'),
            'authUser'       => $this->getAuthUser()
        ];

        $this->render('purchase_order/detail', $data);
    }

    /**
     * Memproses Konfirmasi Penerimaan Fisik Barang Gudang (Goods Receipt)
     * Mengupdate kuantitas diterima pada item PO dan menambah stok master bahan baku
     *
     * @param int $id
     */
    public function receive($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/purchase-order/detail/' . (int)$id);
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin atau Purchasing
        $authUser = $this->requireRole(['Admin', 'Purchasing'], '/scm/purchase-order/detail/' . (int)$id);

        // 2. Validasi Keamanan CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Permintaan tidak sah (Token CSRF kedaluwarsa atau tidak valid). Silakan coba lagi.');
            header('Location: /scm/purchase-order/detail/' . (int)$id);
            exit;
        }

        $poId          = (int)$id;
        $tanggalTerima = trim($_POST['tanggal_terima'] ?? date('Y-m-d'));
        $catatan       = trim($_POST['catatan_penerimaan'] ?? '');
        $receivedItems = $_POST['items'] ?? [];

        try {
            $result = $this->receiptModel->processReceipt(
                $poId,
                (int)$authUser['id'],
                $tanggalTerima,
                $receivedItems,
                $catatan
            );

            $formattedQty = number_format((float)$result['total_qty'], 2, ',', '.');
            Session::setFlash(
                'success',
                "Penerimaan barang dengan nomor tanda terima '{$result['no_penerimaan']}' berhasil dikonfirmasi. Total {$formattedQty} fisik bahan baku telah ditambahkan ke stok gudang. Status dokumen PO kini: '{$result['new_status']}'."
            );

        } catch (InvalidArgumentException $e) {
            Session::setFlash('error', 'Validasi penerimaan gagal: ' . $e->getMessage());
        } catch (RuntimeException $e) {
            Session::setFlash('error', 'Kendala proses gudang: ' . $e->getMessage());
        } catch (Exception $e) {
            Session::setFlash('error', 'Terjadi kesalahan sistem saat memproses penerimaan: ' . $e->getMessage());
        }

        header('Location: /scm/purchase-order/detail/' . $poId);
        exit;
    }

    /**
     * Memperbarui Status Purchase Order Berdasarkan Matriks Bisnis
     *
     * @param int $id
     */
    public function updateStatus($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/purchase-order/detail/' . (int)$id);
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin atau Purchasing
        $authUser = $this->requireRole(['Admin', 'Purchasing'], '/scm/purchase-order/detail/' . (int)$id);

        // 2. Validasi CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Permintaan tidak sah (Token CSRF tidak valid).');
            header('Location: /scm/purchase-order/detail/' . (int)$id);
            exit;
        }

        $statusPo = trim($_POST['status_po'] ?? '');

        try {
            $this->poModel->updateStatus((int)$id, $statusPo);
            Session::setFlash('success', "Status dokumen Purchase Order berhasil diperbarui menjadi '{$statusPo}'.");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal memperbarui status: ' . $e->getMessage());
        }

        header('Location: /scm/purchase-order/detail/' . (int)$id);
        exit;
    }

    /**
     * Membatalkan Dokumen Purchase Order
     *
     * @param int $id
     */
    public function cancel($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/purchase-order/detail/' . (int)$id);
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin atau Purchasing
        $authUser = $this->requireRole(['Admin', 'Purchasing'], '/scm/purchase-order/detail/' . (int)$id);

        // 2. Validasi CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Permintaan tidak sah (Token CSRF tidak valid).');
            header('Location: /scm/purchase-order/detail/' . (int)$id);
            exit;
        }

        $alasan = trim($_POST['alasan_pembatalan'] ?? '');

        try {
            $this->poModel->cancel((int)$id, $alasan);
            Session::setFlash('success', 'Dokumen Purchase Order berhasil dibatalkan.');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal membatalkan PO: ' . $e->getMessage());
        }

        header('Location: /scm/purchase-order/detail/' . (int)$id);
        exit;
    }
}

