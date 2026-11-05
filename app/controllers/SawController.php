<?php
/**
 * ==============================================================================
 * CONTROLLER: SPK METODE SIMPLE ADDITIVE WEIGHTING (SAW) - MODUL 3
 * ==============================================================================
 * Penanggung Jawab: M. Hadi Pratama
 * Menangani CRUD kriteria, validasi bobot 100%, input nilai matriks evaluasi,
 * komputasi normalisasi benefit/cost, dan pemeringkatan supplier terbaik.
 * ==============================================================================
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/SawCriteria.php';
require_once __DIR__ . '/../models/SawEvaluation.php';
require_once __DIR__ . '/../models/Vendor.php';

class SawController extends Controller {
    private $criteriaModel;
    private $evaluationModel;
    private $vendorModel;

    public function __construct() {
        Session::start();
        $this->criteriaModel   = new SawCriteria();
        $this->evaluationModel = new SawEvaluation();
        $this->vendorModel     = new Vendor();
    }

    // ==============================================================================
    // 1. MANAJEMEN KRITERIA & PEMBOBOTAN SAW
    // ==============================================================================

    /**
     * Halaman Utama Kriteria Pembobotan
     * Halaman dapat dibaca publik/read-only, aksi mutasi dibatasi role
     */
    public function criteria() {
        $authUser = $this->getAuthUser();

        $criteriaList = $this->criteriaModel->getAll(false);
        $totalWeight  = $this->criteriaModel->getTotalWeight();
        $isExact100   = $this->criteriaModel->isTotalWeightExact100();

        $data = [
            'pageTitle'    => 'Kriteria Pembobotan SPK SAW',
            'pageSubTitle' => 'Modul 3 (M. Hadi Pratama): Penentuan Atribut Benefit/Cost & Validasi Akumulasi Bobot 100%',
            'activeMenu'   => 'saw-criteria',
            'criteriaList' => $criteriaList,
            'totalWeight'  => $totalWeight,
            'isExact100'   => $isExact100,
            'csrfToken'    => Session::generateCsrfToken(),
            'flashSuccess' => Session::getFlash('success'),
            'flashError'   => Session::getFlash('error'),
            'authUser'     => $authUser
        ];

        $this->render('saw/criteria', $data);
    }

    /**
     * Menyimpan Kriteria Baru
     * HANYA diizinkan untuk role Admin dan QC_Auditor
     */
    public function storeCriteria() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/saw-criteria');
            exit;
        }

        // 1. RBAC Server-Side: Wajib Role Admin atau QC_Auditor
        $user = $this->requireRole(['Admin', 'QC_Auditor'], '/scm/saw-criteria');

        // 2. Proteksi Keamanan CSRF
        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Permintaan tidak sah (CSRF Token tidak valid atau telah kedaluwarsa).');
            header('Location: /scm/saw-criteria');
            exit;
        }

        try {
            $this->criteriaModel->create([
                'kode_kriteria' => $_POST['kode_kriteria'] ?? '',
                'nama_kriteria' => $_POST['nama_kriteria'] ?? '',
                'bobot'         => $_POST['bobot'] ?? 0,
                'tipe'          => $_POST['tipe'] ?? 'Benefit',
                'is_active'     => isset($_POST['is_active']) ? 1 : 0
            ]);
            Session::setFlash('success', 'Kriteria keputusan SAW berhasil ditambahkan.');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        header('Location: /scm/saw-criteria');
        exit;
    }

    /**
     * Memperbarui Kriteria
     *
     * @param int $id
     */
    public function updateCriteria($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/saw-criteria');
            exit;
        }

        // 1. Otorisasi Role & Autentikasi Pengguna
        $user = $this->requireRole(['Admin', 'QC_Auditor'], '/scm/saw-criteria');

        // 2. Proteksi Keamanan CSRF
        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Permintaan tidak sah (CSRF Token tidak valid atau telah kedaluwarsa).');
            header('Location: /scm/saw-criteria');
            exit;
        }

        try {
            $this->criteriaModel->update((int)$id, [
                'kode_kriteria' => $_POST['kode_kriteria'] ?? '',
                'nama_kriteria' => $_POST['nama_kriteria'] ?? '',
                'bobot'         => $_POST['bobot'] ?? 0,
                'tipe'          => $_POST['tipe'] ?? 'Benefit',
                'is_active'     => isset($_POST['is_active']) ? 1 : 0
            ]);
            Session::setFlash('success', 'Kriteria keputusan SAW berhasil diperbarui.');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        header('Location: /scm/saw-criteria');
        exit;
    }

    /**
     * Menghapus Kriteria
     *
     * @param int $id
     */
    public function deleteCriteria($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/saw-criteria');
            exit;
        }

        // 1. Otorisasi Role & Autentikasi Pengguna
        $user = $this->requireRole(['Admin', 'QC_Auditor'], '/scm/saw-criteria');

        // 2. Proteksi Keamanan CSRF
        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Permintaan tidak sah (CSRF Token tidak valid atau telah kedaluwarsa).');
            header('Location: /scm/saw-criteria');
            exit;
        }

        try {
            $this->criteriaModel->delete((int)$id);
            Session::setFlash('success', 'Kriteria berhasil dihapus.');
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menghapus kriteria: ' . $e->getMessage());
        }

        header('Location: /scm/saw-criteria');
        exit;
    }

    /**
     * Mengaktifkan/Menonaktifkan Kriteria
     *
     * @param int $id
     */
    public function toggleCriteria($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/saw-criteria');
            exit;
        }

        // 1. Otorisasi Role & Autentikasi Pengguna
        $user = $this->requireRole(['Admin', 'QC_Auditor'], '/scm/saw-criteria');

        // 2. Proteksi Keamanan CSRF
        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Permintaan tidak sah (CSRF Token tidak valid atau telah kedaluwarsa).');
            header('Location: /scm/saw-criteria');
            exit;
        }

        try {
            $this->criteriaModel->toggleActive((int)$id);
            Session::setFlash('success', 'Status aktif kriteria berhasil diperbarui.');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        header('Location: /scm/saw-criteria');
        exit;
    }

    // ==============================================================================
    // 2. INPUT & PENGELOLAAN EVALUASI SUPPLIER PER PERIODE
    // ==============================================================================

    /**
     * Halaman Input Evaluasi Alternatif Supplier
     * Halaman dapat dibaca, form simpan diproteksi role Admin dan QC Auditor
     */
    public function evaluation() {
        $authUser = $this->getAuthUser();

        $periods = $this->evaluationModel->getPeriods();

        // Tentukan periode yang dipilih (default: periode pertama atau '2026-Q4')
        $selectedPeriod = trim($_GET['periode'] ?? '');
        if (empty($selectedPeriod)) {
            $selectedPeriod = !empty($periods) ? $periods[0] : '2026-Q4';
        }

        // Ambil kriteria aktif
        $criteriaList = $this->criteriaModel->getAll(true);

        // Ambil HANYA vendor yang berstatus 'Terverifikasi'
        $db = $this->criteriaModel->getDb();
        $stmtV = $db->query("SELECT `id`, `kode_vendor`, `nama_vendor`, `spesialisasi_logam`, `jenis_akreditasi`, `status_verifikasi` 
                             FROM `vendors` 
                             WHERE `status_verifikasi` = 'Terverifikasi' 
                             ORDER BY `nama_vendor` ASC");
        $verifiedVendors = $stmtV->fetchAll();

        // Ambil nilai matriks evaluasi untuk periode terpilih
        $currentMatrix = $this->evaluationModel->getMatrixByPeriod($selectedPeriod);

        $data = [
            'pageTitle'       => 'Input Evaluasi Alternatif Supplier',
            'pageSubTitle'    => 'Modul 3 (M. Hadi Pratama): Matriks Penilaian Alternatif Rekanan Terverifikasi per Periode',
            'activeMenu'      => 'saw-evaluation',
            'periods'         => $periods,
            'selectedPeriod'  => $selectedPeriod,
            'criteriaList'    => $criteriaList,
            'verifiedVendors' => $verifiedVendors,
            'currentMatrix'   => $currentMatrix,
            'csrfToken'       => Session::generateCsrfToken(),
            'flashSuccess'    => Session::getFlash('success'),
            'flashError'      => Session::getFlash('error'),
            'authUser'        => $authUser
        ];

        $this->render('saw/evaluation', $data);
    }

    /**
     * Menyimpan Batch Matriks Nilai Evaluasi
     */
    public function storeEvaluation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /scm/saw-evaluation');
            exit;
        }

        // 1. Otorisasi Role & Autentikasi Pengguna
        $user = $this->requireRole(['Admin', 'QC_Auditor'], '/scm/saw-evaluation');

        // 2. Proteksi Keamanan CSRF
        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Permintaan tidak sah (CSRF Token tidak valid atau telah kedaluwarsa).');
            header('Location: /scm/saw-evaluation');
            exit;
        }

        $periode = trim($_POST['periode_evaluasi'] ?? '');
        $scores  = $_POST['scores'] ?? [];

        if (empty($periode)) {
            Session::setFlash('error', 'Nama periode evaluasi wajib diisi.');
            header('Location: /scm/saw-evaluation');
            exit;
        }

        if (empty($scores) || !is_array($scores)) {
            Session::setFlash('error', 'Tidak ada data nilai evaluasi yang dikirimkan.');
            header('Location: /scm/saw-evaluation?periode=' . urlencode($periode));
            exit;
        }

        try {
            $this->evaluationModel->saveBatchMatrix($periode, $scores);
            Session::setFlash('success', "Seluruh nilai evaluasi supplier untuk periode '{$periode}' berhasil disimpan ke basis data.");
        } catch (Exception $e) {
            Session::setFlash('error', 'Gagal menyimpan nilai evaluasi: ' . $e->getMessage());
        }

        header('Location: /scm/saw-evaluation?periode=' . urlencode($periode));
        exit;
    }

    // ==============================================================================
    // 3. KOMPUTASI NORMALISASI & PERANGKINGAN SAW
    // ==============================================================================

    /**
     * Halaman Hasil Normalisasi & Perangkingan Supplier Terbaik
     * Halaman dapat dibaca seluruh role/pengunjung untuk referensi pengadaan
     */
    public function ranking() {
        $authUser = $this->getAuthUser();

        $periods = $this->evaluationModel->getPeriods();

        $selectedPeriod = trim($_GET['periode'] ?? '');
        if (empty($selectedPeriod)) {
            $selectedPeriod = !empty($periods) ? $periods[0] : '2026-Q4';
        }

        // Jalankan perhitungan komputasi SAW lengkap
        $sawResult = $this->evaluationModel->calculateSaw($selectedPeriod);

        $data = [
            'pageTitle'      => 'Hasil Perangkingan Supplier (Metode SAW)',
            'pageSubTitle'   => 'Modul 3 (M. Hadi Pratama): Matriks Keputusan, Normalisasi Benefit/Cost & Nilai Preferensi',
            'activeMenu'     => 'saw-ranking',
            'periods'        => $periods,
            'selectedPeriod' => $selectedPeriod,
            'sawResult'      => $sawResult,
            'flashSuccess'   => Session::getFlash('success'),
            'flashError'     => Session::getFlash('error'),
            'authUser'       => $authUser
        ];

        $this->render('saw/ranking', $data);
    }
}

