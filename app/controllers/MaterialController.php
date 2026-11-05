<?php
/**
 * ==============================================================================
 * CONTROLLER: MATERIAL (MASTER BAHAN BAKU LOGAM OTOMOTIF)
 * ==============================================================================
 * Mengendalikan alur CRUD katalog bahan baku logam otomotif (Plat Baja, Pipa,
 * Aluminium, Besi Tempa), standarisasi JIS/ASTM, volume stok gudang penerimaan,
 * serta pencegahan penghapusan material yang terikat transaksi / rekanan vendor.
 * ==============================================================================
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Material.php';

class MaterialController extends Controller {
    private $model;

    public function __construct() {
        Session::start();
        $this->model = new Material();
    }

    /**
     * Halaman Utama: Daftar Seluruh Bahan Baku Material
     */
    public function index() {
        $materials = $this->model->getAll();
        $authUser  = $this->getAuthUser();

        // Hitung ringkasan statistik stok
        $totalStok = 0;
        foreach ($materials as $m) {
            $totalStok += (float)$m['stok_gudang'];
        }

        $data = [
            'pageTitle'    => 'Katalog Bahan Baku Logam Otomotif',
            'pageSubTitle' => 'Master Spesifikasi Teknis, Kategori Logam & Manajemen Volume Stok Gudang',
            'activeMenu'   => 'material',
            'materials'    => $materials,
            'totalStok'    => $totalStok,
            'authUser'     => $authUser,
            'csrfToken'    => Session::generateCsrfToken(),
            'flashSuccess' => Session::getFlash('success'),
            'flashError'   => Session::getFlash('error')
        ];

        $this->render('material/index', $data);
    }

    /**
     * Formulir Tambah Bahan Baku Baru
     */
    public function create() {
        // RBAC: Hanya Admin dan Purchasing yang berhak mengelola master material
        $authUser = $this->requireRole(['Admin', 'Purchasing'], '/scm/material');

        $oldInput = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);

        $data = [
            'pageTitle'    => 'Tambah Bahan Baku Material Baru',
            'pageSubTitle' => 'Form Pendaftaran Spesifikasi Logam Industri Otomotif (JIS / ASTM) & Volume Stok Awal',
            'activeMenu'   => 'material',
            'authUser'     => $authUser,
            'csrfToken'    => Session::generateCsrfToken(),
            'flashError'   => Session::getFlash('error'),
            'old'          => $oldInput
        ];

        $this->render('material/create', $data);
    }

    /**
     * Memproses Penyimpanan Material Baru ke Basis Data
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/material');
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin atau Purchasing
        $this->requireRole(['Admin', 'Purchasing'], '/scm/material');

        // 2. Validasi Keamanan CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Permintaan tidak sah (CSRF Token tidak valid atau kedaluwarsa). Silakan coba lagi.');
            header('Location: /scm/material/create');
            exit;
        }

        // Simpan input untuk formulir jika terjadi error
        $_SESSION['old_input'] = $_POST;

        $kodeMaterial      = strtoupper(trim($_POST['kode_material'] ?? ''));
        $namaMaterial      = trim($_POST['nama_material'] ?? '');
        $kategori          = trim($_POST['kategori'] ?? 'Plat Baja');
        $satuan            = trim($_POST['satuan'] ?? 'Kg');
        $stokGudang        = trim($_POST['stok_gudang'] ?? '0');
        $spesifikasiTeknis = trim($_POST['spesifikasi_teknis'] ?? '');

        // 3. Validasi Server-Side
        if (empty($kodeMaterial)) {
            Session::setFlash('error', 'Kode material wajib diisi (contoh: MAT-SPCC-05).');
            header('Location: /scm/material/create');
            exit;
        }

        if (strlen($kodeMaterial) > 25) {
            Session::setFlash('error', 'Kode material maksimal 25 karakter.');
            header('Location: /scm/material/create');
            exit;
        }

        if (empty($namaMaterial)) {
            Session::setFlash('error', 'Nama bahan baku material wajib diisi.');
            header('Location: /scm/material/create');
            exit;
        }

        if (strlen($namaMaterial) > 120) {
            Session::setFlash('error', 'Nama material maksimal 120 karakter.');
            header('Location: /scm/material/create');
            exit;
        }

        $allowedKategori = ['Plat Baja', 'Pipa Baja', 'Aluminium Alloy', 'Besi Tempa'];
        if (!in_array($kategori, $allowedKategori, true)) {
            Session::setFlash('error', 'Kategori material yang dipilih tidak valid.');
            header('Location: /scm/material/create');
            exit;
        }

        if (empty($satuan)) {
            $satuan = 'Kg';
        }

        if (!is_numeric($stokGudang) || (float)$stokGudang < 0) {
            Session::setFlash('error', 'Volume stok gudang harus berupa angka positif.');
            header('Location: /scm/material/create');
            exit;
        }

        if ($this->model->isKodeExists($kodeMaterial)) {
            Session::setFlash('error', "Kode material '{$kodeMaterial}' sudah terdaftar dalam sistem.");
            header('Location: /scm/material/create');
            exit;
        }

        try {
            $this->model->create([
                'kode_material'      => $kodeMaterial,
                'nama_material'      => $namaMaterial,
                'kategori'           => $kategori,
                'satuan'             => $satuan,
                'stok_gudang'        => (float)$stokGudang,
                'spesifikasi_teknis' => $spesifikasiTeknis
            ]);

            unset($_SESSION['old_input']);
            Session::setFlash('success', "Bahan baku '{$namaMaterial}' ({$kodeMaterial}) berhasil ditambahkan ke katalog master.");
            header('Location: /scm/material');
            exit;
        } catch (Throwable $e) {
            Session::setFlash('error', 'Terjadi kesalahan sistem saat menyimpan data bahan baku: ' . $e->getMessage());
            header('Location: /scm/material/create');
            exit;
        }
    }

    /**
     * Formulir Ubah Data Bahan Baku Material
     *
     * @param int|string $id
     */
    public function edit($id) {
        $authUser = $this->requireRole(['Admin', 'Purchasing'], '/scm/material');

        $materialId = (int)$id;
        if ($materialId <= 0) {
            Session::setFlash('error', 'ID material tidak valid.');
            header('Location: /scm/material');
            exit;
        }

        $material = $this->model->getById($materialId);
        if (!$material) {
            Session::setFlash('error', 'Data bahan baku material tidak ditemukan.');
            header('Location: /scm/material');
            exit;
        }

        $data = [
            'pageTitle'    => 'Ubah Data Bahan Baku Material',
            'pageSubTitle' => "Memperbarui Spesifikasi Teknis & Stok untuk {$material['nama_material']} ({$material['kode_material']})",
            'activeMenu'   => 'material',
            'material'     => $material,
            'authUser'     => $authUser,
            'csrfToken'    => Session::generateCsrfToken(),
            'flashError'   => Session::getFlash('error')
        ];

        $this->render('material/edit', $data);
    }

    /**
     * Memproses Pembaruan Data Bahan Baku Material
     *
     * @param int|string $id
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/material');
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin atau Purchasing
        $this->requireRole(['Admin', 'Purchasing'], '/scm/material');

        $materialId = (int)$id;
        if ($materialId <= 0) {
            Session::setFlash('error', 'ID material tidak valid.');
            header('Location: /scm/material');
            exit;
        }

        $existing = $this->model->getById($materialId);
        if (!$existing) {
            Session::setFlash('error', 'Data bahan baku yang akan diperbarui tidak ditemukan.');
            header('Location: /scm/material');
            exit;
        }

        // 2. Validasi Keamanan CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Permintaan tidak sah (CSRF Token tidak valid atau kedaluwarsa).');
            header("Location: /scm/material/edit/{$materialId}");
            exit;
        }

        $kodeMaterial      = strtoupper(trim($_POST['kode_material'] ?? ''));
        $namaMaterial      = trim($_POST['nama_material'] ?? '');
        $kategori          = trim($_POST['kategori'] ?? 'Plat Baja');
        $satuan            = trim($_POST['satuan'] ?? 'Kg');
        $stokGudang        = trim($_POST['stok_gudang'] ?? '0');
        $spesifikasiTeknis = trim($_POST['spesifikasi_teknis'] ?? '');

        // 3. Validasi Server-Side
        if (empty($kodeMaterial)) {
            Session::setFlash('error', 'Kode material wajib diisi.');
            header("Location: /scm/material/edit/{$materialId}");
            exit;
        }

        if (strlen($kodeMaterial) > 25) {
            Session::setFlash('error', 'Kode material maksimal 25 karakter.');
            header("Location: /scm/material/edit/{$materialId}");
            exit;
        }

        if (empty($namaMaterial)) {
            Session::setFlash('error', 'Nama bahan baku material wajib diisi.');
            header("Location: /scm/material/edit/{$materialId}");
            exit;
        }

        if (strlen($namaMaterial) > 120) {
            Session::setFlash('error', 'Nama material maksimal 120 karakter.');
            header("Location: /scm/material/edit/{$materialId}");
            exit;
        }

        $allowedKategori = ['Plat Baja', 'Pipa Baja', 'Aluminium Alloy', 'Besi Tempa'];
        if (!in_array($kategori, $allowedKategori, true)) {
            Session::setFlash('error', 'Kategori material yang dipilih tidak valid.');
            header("Location: /scm/material/edit/{$materialId}");
            exit;
        }

        if (empty($satuan)) {
            $satuan = 'Kg';
        }

        if (!is_numeric($stokGudang) || (float)$stokGudang < 0) {
            Session::setFlash('error', 'Volume stok gudang harus berupa angka positif.');
            header("Location: /scm/material/edit/{$materialId}");
            exit;
        }

        if ($this->model->isKodeExists($kodeMaterial, $materialId)) {
            Session::setFlash('error', "Kode material '{$kodeMaterial}' sudah digunakan oleh material lain.");
            header("Location: /scm/material/edit/{$materialId}");
            exit;
        }

        try {
            $this->model->update($materialId, [
                'kode_material'      => $kodeMaterial,
                'nama_material'      => $namaMaterial,
                'kategori'           => $kategori,
                'satuan'             => $satuan,
                'stok_gudang'        => (float)$stokGudang,
                'spesifikasi_teknis' => $spesifikasiTeknis
            ]);

            Session::setFlash('success', "Data bahan baku '{$namaMaterial}' ({$kodeMaterial}) berhasil diperbarui.");
            header('Location: /scm/material');
            exit;
        } catch (Throwable $e) {
            Session::setFlash('error', 'Terjadi kesalahan sistem saat memperbarui data bahan baku: ' . $e->getMessage());
            header("Location: /scm/material/edit/{$materialId}");
            exit;
        }
    }

    /**
     * Menghapus Data Bahan Baku Material
     *
     * @param int|string $id
     */
    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/material');
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin atau Purchasing
        $this->requireRole(['Admin', 'Purchasing'], '/scm/material');

        $materialId = (int)$id;
        if ($materialId <= 0) {
            Session::setFlash('error', 'ID material tidak valid.');
            header('Location: /scm/material');
            exit;
        }

        // 2. Validasi Keamanan CSRF Token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('error', 'Penghapusan ditolak: Token keamanan CSRF tidak valid.');
            header('Location: /scm/material');
            exit;
        }

        $material = $this->model->getById($materialId);
        if (!$material) {
            Session::setFlash('error', 'Data bahan baku yang akan dihapus tidak ditemukan.');
            header('Location: /scm/material');
            exit;
        }

        // 3. Pemeriksaan Integritas Relasi (Cegah Penghapusan jika terpakai)
        if ($this->model->isUsedInRelations($materialId)) {
            Session::setFlash('error', "Material '{$material['nama_material']}' ({$material['kode_material']}) tidak dapat dihapus karena telah terhubung dalam pemetaan pasokan vendor atau transaksi Purchase Order.");
            header('Location: /scm/material');
            exit;
        }

        try {
            $this->model->delete($materialId);
            Session::setFlash('success', "Bahan baku '{$material['nama_material']}' ({$material['kode_material']}) berhasil dihapus dari katalog master.");
        } catch (PDOException $e) {
            Session::setFlash('error', "Material '{$material['nama_material']}' tidak dapat dihapus karena memiliki keterkaitan data pada modul lain.");
        } catch (Throwable $e) {
            Session::setFlash('error', 'Terjadi kesalahan sistem saat menghapus data bahan baku: ' . $e->getMessage());
        }

        header('Location: /scm/material');
        exit;
    }
}

