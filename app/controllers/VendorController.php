<?php
/**
 * ==============================================================================
 * CONTROLLER: VENDOR CONTROLLER - MODUL 1 (GORI)
 * ==============================================================================
 * Menangani alur CRUD Rekanan Vendor Logam & Akreditasi Mutu ISO
 * lengkap dengan validasi server-side, CSRF protection, flash messages,
 * dan proses verifikasi khusus.
 * ==============================================================================
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Vendor.php';

class VendorController extends Controller {
    private $model;

    public function __construct() {
        Session::start();
        $this->model = new Vendor();
    }

    /**
     * Halaman Utama: Daftar Seluruh Rekanan Vendor
     */
    public function index() {
        $vendors = $this->model->getAll();
        $authUser = $this->getAuthUser();

        $data = [
            'pageTitle'    => 'Master Rekanan Vendor Logam',
            'pageSubTitle' => 'Modul 1 (Gori - 43250583): Akreditasi Mutu ISO / IATF 16949 & Verifikasi Rekanan',
            'activeMenu'   => 'vendor',
            'vendors'      => $vendors,
            'authUser'     => $authUser,
            'csrfToken'    => Session::generateCsrfToken(),
            'flashSuccess' => Session::getFlash('success'),
            'flashError'   => Session::getFlash('error')
        ];

        $this->render('vendor/index', $data);
    }

    /**
     * Formulir Tambah Rekanan Vendor Baru
     * HANYA diizinkan untuk role Admin
     */
    public function create() {
        // RBAC: Hanya Admin yang dapat mendaftarkan rekanan vendor baru
        $authUser = $this->requireRole(['Admin'], '/scm/vendor');

        $oldInput = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);

        $data = [
            'pageTitle'    => 'Pendaftaran Rekanan Vendor Baru',
            'pageSubTitle' => 'Modul 1: Form Input Legalitas, Spesifikasi Logam & Mutu ISO (Status Awal: Audit Pending)',
            'activeMenu'   => 'vendor',
            'authUser'     => $authUser,
            'csrfToken'    => Session::generateCsrfToken(),
            'flashError'   => Session::getFlash('error'),
            'old'          => $oldInput
        ];

        $this->render('vendor/create', $data);
    }

    /**
     * Memproses Penyimpanan Vendor Baru ke Basis Data
     * HANYA diizinkan untuk role Admin
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/vendor');
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin
        $this->requireRole(['Admin'], '/scm/vendor');

        // 2. Validasi Keamanan CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Permintaan tidak sah (CSRF Token tidak valid atau kedaluwarsa). Silakan coba lagi.');
            header('Location: /scm/vendor/create');
            exit;
        }

        // 2. Ambil dan Bersihkan Input
        $kodeVendor       = strtoupper(trim($_POST['kode_vendor'] ?? ''));
        $namaVendor       = trim($_POST['nama_vendor'] ?? '');
        $spesialisasi     = trim($_POST['spesialisasi_logam'] ?? '');
        $kontakPerson     = trim($_POST['kontak_person'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $telepon          = trim($_POST['telepon'] ?? '');
        $alamat           = trim($_POST['alamat'] ?? '');
        $jenisAkreditasi  = trim($_POST['jenis_akreditasi'] ?? 'Non-ISO');
        $noSertifikat     = trim($_POST['no_sertifikat'] ?? '');
        $badanSertifikasi = trim($_POST['badan_sertifikasi'] ?? '');
        $masaBerlakuIso   = trim($_POST['masa_berlaku_iso'] ?? '');

        // ATURAN BISNIS: Abaikan/tolak manipulasi POST status.
        // Vendor baru WAJIB berkunci otomatis pada status 'Audit Pending'
        $statusVerifikasi = 'Audit Pending';

        // Simpan input untuk form jika terjadi galat
        $_SESSION['old_input'] = $_POST;

        // 3. Validasi Server-Side Dasar
        $allowedAkreditasi = ['IATF 16949', 'ISO 9001', 'ISO 14001', 'Non-ISO'];

        if (empty($kodeVendor) || empty($namaVendor) || empty($spesialisasi) || empty($kontakPerson) || empty($telepon) || empty($alamat)) {
            Session::setFlash('error', 'Gagal menyimpan: Seluruh kolom wajib bertanda bintang (*) harus diisi.');
            header('Location: /scm/vendor/create');
            exit;
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Format alamat email tidak valid.');
            header('Location: /scm/vendor/create');
            exit;
        }

        if (!in_array($jenisAkreditasi, $allowedAkreditasi, true)) {
            Session::setFlash('error', 'Pilihan jenis akreditasi mutu tidak valid.');
            header('Location: /scm/vendor/create');
            exit;
        }

        // 4. Validasi Keterikatan Akreditasi ISO
        if ($jenisAkreditasi !== 'Non-ISO') {
            if (empty($noSertifikat)) {
                Session::setFlash('error', "Untuk vendor berakreditasi {$jenisAkreditasi}, Nomor Registrasi Sertifikat wajib diisi.");
                header('Location: /scm/vendor/create');
                exit;
            }

            if (empty($masaBerlakuIso)) {
                Session::setFlash('error', "Untuk vendor berakreditasi {$jenisAkreditasi}, Tanggal Masa Berlaku Sertifikat wajib diisi.");
                header('Location: /scm/vendor/create');
                exit;
            }

            $dateParsed = date_parse($masaBerlakuIso);
            if ($dateParsed['error_count'] > 0 || !checkdate($dateParsed['month'], $dateParsed['day'], $dateParsed['year'])) {
                Session::setFlash('error', 'Format tanggal masa berlaku sertifikat tidak valid.');
                header('Location: /scm/vendor/create');
                exit;
            }
        } else {
            // Jika Non-ISO, seluruh field sertifikat dipaksa menjadi NULL
            $noSertifikat     = null;
            $badanSertifikasi = null;
            $masaBerlakuIso   = null;
        }

        // 5. Validasi Duplikasi Kode Vendor
        if ($this->model->isKodeVendorExists($kodeVendor)) {
            Session::setFlash('error', "Kode vendor '{$kodeVendor}' sudah digunakan oleh rekanan lain. Gunakan kode yang unik.");
            header('Location: /scm/vendor/create');
            exit;
        }

        // 6. Eksekusi Penyimpanan ke Database
        try {
            $insertData = [
                'kode_vendor'        => $kodeVendor,
                'nama_vendor'        => $namaVendor,
                'spesialisasi_logam' => $spesialisasi,
                'kontak_person'      => $kontakPerson,
                'email'              => $email,
                'telepon'            => $telepon,
                'alamat'             => $alamat,
                'jenis_akreditasi'   => $jenisAkreditasi,
                'no_sertifikat'      => $noSertifikat,
                'badan_sertifikasi'  => $badanSertifikasi,
                'masa_berlaku_iso'   => $masaBerlakuIso,
                'status_verifikasi'  => $statusVerifikasi
            ];

            $this->model->create($insertData);
            unset($_SESSION['old_input']);

            Session::setFlash('success', "Rekanan vendor '{$namaVendor}' ({$kodeVendor}) berhasil didaftarkan dengan status 'Audit Pending'.");
            header('Location: /scm/vendor');
            exit;
        } catch (Throwable $e) {
            Session::setFlash('error', 'Terjadi kesalahan sistem saat menyimpan data vendor. Silakan periksa kembali formulir.');
            header('Location: /scm/vendor/create');
            exit;
        }
    }

    /**
     * Formulir Ubah Data Umum Rekanan Vendor
     * HANYA diizinkan untuk role Admin
     *
     * @param int|string $id
     */
    public function edit($id) {
        // RBAC: Hanya Admin yang dapat mengubah data legalitas vendor
        $authUser = $this->requireRole(['Admin'], '/scm/vendor');

        $vendorId = (int)$id;
        if ($vendorId <= 0) {
            Session::setFlash('error', 'ID Vendor tidak valid.');
            header('Location: /scm/vendor');
            exit;
        }

        $vendor = $this->model->getById($vendorId);
        if (!$vendor) {
            Session::setFlash('error', 'Data rekanan vendor tidak ditemukan.');
            header('Location: /scm/vendor');
            exit;
        }

        $data = [
            'pageTitle'    => 'Ubah Data Rekanan Vendor',
            'pageSubTitle' => "Modul 1: Memperbarui Legalitas Umum & Dokumen Mutu untuk {$vendor['nama_vendor']}",
            'activeMenu'   => 'vendor',
            'authUser'     => $authUser,
            'vendor'       => $vendor,
            'csrfToken'    => Session::generateCsrfToken(),
            'flashError'   => Session::getFlash('error')
        ];

        $this->render('vendor/edit', $data);
    }

    /**
     * Memproses Pembaruan Data Umum Rekanan Vendor
     * HANYA diizinkan untuk role Admin
     *
     * @param int|string $id
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/vendor');
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin
        $this->requireRole(['Admin'], '/scm/vendor');

        $vendorId = (int)$id;
        if ($vendorId <= 0) {
            Session::setFlash('error', 'ID Vendor tidak valid.');
            header('Location: /scm/vendor');
            exit;
        }

        $existing = $this->model->getById($vendorId);
        if (!$existing) {
            Session::setFlash('error', 'Data vendor yang akan diperbarui tidak ditemukan.');
            header('Location: /scm/vendor');
            exit;
        }

        // 2. Validasi Keamanan CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Permintaan tidak sah (CSRF Token tidak valid).');
            header("Location: /scm/vendor/edit/{$vendorId}");
            exit;
        }

        // 2. Ambil Input
        $kodeVendor       = strtoupper(trim($_POST['kode_vendor'] ?? ''));
        $namaVendor       = trim($_POST['nama_vendor'] ?? '');
        $spesialisasi     = trim($_POST['spesialisasi_logam'] ?? '');
        $kontakPerson     = trim($_POST['kontak_person'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $telepon          = trim($_POST['telepon'] ?? '');
        $alamat           = trim($_POST['alamat'] ?? '');
        $jenisAkreditasi  = trim($_POST['jenis_akreditasi'] ?? 'Non-ISO');
        $noSertifikat     = trim($_POST['no_sertifikat'] ?? '');
        $badanSertifikasi = trim($_POST['badan_sertifikasi'] ?? '');
        $masaBerlakuIso   = trim($_POST['masa_berlaku_iso'] ?? '');

        // 3. Validasi Server-Side
        $allowedAkreditasi = ['IATF 16949', 'ISO 9001', 'ISO 14001', 'Non-ISO'];

        if (empty($kodeVendor) || empty($namaVendor) || empty($spesialisasi) || empty($kontakPerson) || empty($telepon) || empty($alamat)) {
            Session::setFlash('error', 'Kolom wajib bertanda bintang (*) tidak boleh dikosongkan.');
            header("Location: /scm/vendor/edit/{$vendorId}");
            exit;
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Format alamat email tidak valid.');
            header("Location: /scm/vendor/edit/{$vendorId}");
            exit;
        }

        if (!in_array($jenisAkreditasi, $allowedAkreditasi, true)) {
            Session::setFlash('error', 'Jenis akreditasi mutu tidak valid.');
            header("Location: /scm/vendor/edit/{$vendorId}");
            exit;
        }

        // 4. Validasi Keterikatan Akreditasi ISO
        if ($jenisAkreditasi !== 'Non-ISO') {
            if (empty($noSertifikat)) {
                Session::setFlash('error', "Untuk vendor berakreditasi {$jenisAkreditasi}, Nomor Registrasi Sertifikat wajib diisi.");
                header("Location: /scm/vendor/edit/{$vendorId}");
                exit;
            }

            if (empty($masaBerlakuIso)) {
                Session::setFlash('error', "Untuk vendor berakreditasi {$jenisAkreditasi}, Tanggal Masa Berlaku Sertifikat wajib diisi.");
                header("Location: /scm/vendor/edit/{$vendorId}");
                exit;
            }

            $dateParsed = date_parse($masaBerlakuIso);
            if ($dateParsed['error_count'] > 0 || !checkdate($dateParsed['month'], $dateParsed['day'], $dateParsed['year'])) {
                Session::setFlash('error', 'Format tanggal masa berlaku sertifikat tidak valid.');
                header("Location: /scm/vendor/edit/{$vendorId}");
                exit;
            }
        } else {
            // Jika Non-ISO, seluruh field sertifikat dipaksa menjadi NULL
            $noSertifikat     = null;
            $badanSertifikasi = null;
            $masaBerlakuIso   = null;
        }

        // Cek duplikasi kode vendor pada record lain
        if ($this->model->isKodeVendorExists($kodeVendor, $vendorId)) {
            Session::setFlash('error', "Kode vendor '{$kodeVendor}' sudah digunakan oleh vendor lain.");
            header("Location: /scm/vendor/edit/{$vendorId}");
            exit;
        }

        // 5. Update Database (Tanpa mengubah status_verifikasi)
        try {
            $updateData = [
                'kode_vendor'        => $kodeVendor,
                'nama_vendor'        => $namaVendor,
                'spesialisasi_logam' => $spesialisasi,
                'kontak_person'      => $kontakPerson,
                'email'              => $email,
                'telepon'            => $telepon,
                'alamat'             => $alamat,
                'jenis_akreditasi'   => $jenisAkreditasi,
                'no_sertifikat'      => $noSertifikat,
                'badan_sertifikasi'  => $badanSertifikasi,
                'masa_berlaku_iso'   => $masaBerlakuIso
            ];

            $this->model->update($vendorId, $updateData);
            Session::setFlash('success', "Data umum rekanan vendor '{$namaVendor}' berhasil diperbarui.");
            header('Location: /scm/vendor');
            exit;
        } catch (Throwable $e) {
            Session::setFlash('error', 'Terjadi kesalahan sistem saat memperbarui data vendor.');
            header("Location: /scm/vendor/edit/{$vendorId}");
            exit;
        }
    }

    /**
     * Halaman Khusus Verifikasi Rekanan Vendor & Dokumen Mutu ISO
     * HANYA diizinkan untuk role Admin dan QC_Auditor
     *
     * @param int|string $id
     */
    public function verify($id) {
        // RBAC: Hanya Admin dan QC Auditor yang dapat memverifikasi mutu ISO vendor
        $authUser = $this->requireRole(['Admin', 'QC_Auditor'], '/scm/vendor');

        $vendorId = (int)$id;
        if ($vendorId <= 0) {
            Session::setFlash('error', 'ID Vendor tidak valid.');
            header('Location: /scm/vendor');
            exit;
        }

        $vendor = $this->model->getById($vendorId);
        if (!$vendor) {
            Session::setFlash('error', 'Data rekanan vendor tidak ditemukan.');
            header('Location: /scm/vendor');
            exit;
        }

        $data = [
            'pageTitle'    => 'Verifikasi Rekanan & Audit Mutu ISO',
            'pageSubTitle' => "Proses Audit Resmi untuk Menetapkan Kelayakan Rekanan: {$vendor['nama_vendor']}",
            'activeMenu'   => 'vendor',
            'authUser'     => $authUser,
            'vendor'       => $vendor,
            'csrfToken'    => Session::generateCsrfToken(),
            'flashError'   => Session::getFlash('error')
        ];

        $this->render('vendor/verify', $data);
    }

    /**
     * Memproses Perubahan Status Verifikasi Vendor Melalui Jalur Khusus
     * HANYA diizinkan untuk role Admin dan QC_Auditor
     *
     * @param int|string $id
     */
    public function processVerify($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/vendor');
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin atau QC_Auditor
        $this->requireRole(['Admin', 'QC_Auditor'], '/scm/vendor');

        $vendorId = (int)$id;
        if ($vendorId <= 0) {
            Session::setFlash('error', 'ID Vendor tidak valid.');
            header('Location: /scm/vendor');
            exit;
        }

        $vendor = $this->model->getById($vendorId);
        if (!$vendor) {
            Session::setFlash('error', 'Data vendor tidak ditemukan.');
            header('Location: /scm/vendor');
            exit;
        }

        // 2. Validasi CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Permintaan verifikasi tidak sah (CSRF Token tidak valid).');
            header("Location: /scm/vendor/verify/{$vendorId}");
            exit;
        }

        $statusVerifikasi = trim($_POST['status_verifikasi'] ?? '');
        $allowedStatus = ['Terverifikasi', 'Audit Pending', 'Blacklist'];

        if (!in_array($statusVerifikasi, $allowedStatus, true)) {
            Session::setFlash('error', 'Status verifikasi yang dipilih tidak valid.');
            header("Location: /scm/vendor/verify/{$vendorId}");
            exit;
        }

        try {
            $this->model->updateStatus($vendorId, $statusVerifikasi);
            Session::setFlash('success', "Status verifikasi vendor '{$vendor['nama_vendor']}' berhasil diubah menjadi '{$statusVerifikasi}'.");
            header('Location: /scm/vendor');
            exit;
        } catch (Throwable $e) {
            Session::setFlash('error', 'Terjadi kesalahan sistem saat memperbarui status verifikasi vendor.');
            header("Location: /scm/vendor/verify/{$vendorId}");
            exit;
        }
    }

    /**
     * Menghapus Rekanan Vendor
     * HANYA diizinkan untuk role Admin
     *
     * @param int|string $id
     */
    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/vendor');
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin
        $this->requireRole(['Admin'], '/scm/vendor');

        $vendorId = (int)$id;
        if ($vendorId <= 0) {
            Session::setFlash('error', 'ID Vendor tidak valid.');
            header('Location: /scm/vendor');
            exit;
        }

        // 2. Validasi CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Penghapusan ditolak: Token keamanan CSRF tidak valid.');
            header('Location: /scm/vendor');
            exit;
        }

        $vendor = $this->model->getById($vendorId);
        if (!$vendor) {
            Session::setFlash('error', 'Data vendor yang akan dihapus tidak ditemukan.');
            header('Location: /scm/vendor');
            exit;
        }

        try {
            $this->model->delete($vendorId);
            Session::setFlash('success', "Rekanan vendor '{$vendor['nama_vendor']}' ({$vendor['kode_vendor']}) berhasil dihapus dari sistem.");
        } catch (PDOException $e) {
            // Tangkap Foreign Key Constraint jika vendor sudah memiliki PO / Evaluasi
            Session::setFlash('error', "Vendor '{$vendor['nama_vendor']}' tidak dapat dihapus karena telah memiliki relasi transaksi Purchase Order atau data evaluasi SPK.");
        } catch (Throwable $e) {
            Session::setFlash('error', 'Terjadi kesalahan sistem saat menghapus data vendor.');
        }

        header('Location: /scm/vendor');
        exit;
    }
}
