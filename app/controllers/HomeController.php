<?php
/**
 * ==============================================================================
 * HOME CONTROLLER - SISTEM SUPPLY CHAIN MANAGEMENT (SCM)
 * ==============================================================================
 * Mengendalikan alur navigasi global layout dan tampilan menu sistem SCM Otomotif.
 * ==============================================================================
 */

require_once __DIR__ . '/../core/Controller.php';

class HomeController extends Controller {
    /**
     * Halaman Dashboard Utama
     */
    public function index() {
        $data = [
            'pageTitle'    => 'Dashboard SCM Otomotif',
            'pageSubTitle' => 'Pusat Kendali Pengadaan Bahan Logam & SPK Kelayakan Supplier',
            'activeMenu'   => 'dashboard'
        ];
        $this->render('home/index', $data);
    }

    /**
     * Halaman Master Rekanan Vendor
     */
    public function vendor() {
        $data = [
            'pageTitle'    => 'Master Rekanan Vendor Logam',
            'pageSubTitle' => 'Akreditasi Mutu ISO / IATF 16949 & Verifikasi Rekanan',
            'activeMenu'   => 'vendor',
            'moduleName'   => 'Master Rekanan Vendor Logam & Akreditasi Mutu ISO',
            'author'       => 'Divisi Procurement & QA',
            'features'     => [
                'Form verifikasi legalitas dan sertifikasi mutu ISO/IATF 16949',
                'Live filter pencarian instan sisi klien dengan Vanilla JS',
                'Badge status ganda: Status Kemitraan (Terverifikasi, Audit Pending, Blacklist) dan Status Masa Berlaku ISO',
                'Operasi CRUD penuh berparameter PDO Prepared Statements'
            ]
        ];
        $this->render('vendor/index', $data);
    }

    /**
     * Halaman Master Katalog Bahan Baku Logam (Fitur Pendukung)
     */
    public function material() {
        $data = [
            'pageTitle'    => 'Katalog Bahan Baku Logam Otomotif',
            'pageSubTitle' => 'Spesifikasi Standar Bahan Baku (Baja SPCC, Pipa Baja, Aluminium Alloy)',
            'activeMenu'   => 'material',
            'moduleName'   => 'Katalog Bahan Baku & Pemetaan Pasokan',
            'author'       => 'Divisi Gudang & Logistik',
            'features'     => [
                'Master spesifikasi teknis pelat baja, batangan, dan aluminium standar industri otomotif (JIS/ASTM)',
                'Pemetaan pasokan vendor melalui junction table vendor_materials',
                'Penguncian relasi agar Purchase Order hanya memuat material yang disuplai oleh rekanan terpilih',
                'Pencatatan volume stok fisik gudang penerimaan pabrik'
            ]
        ];
        $this->render('material/index', $data);
    }

    /**
     * Halaman Transaksi Purchase Order & Jadwal Kirim
     */
    public function purchaseOrder() {
        $data = [
            'pageTitle'    => 'Pengadaan Bahan Baku (Purchase Order)',
            'pageSubTitle' => 'Transaksi PO Dinamis, Kalkulasi Subtotal & Jadwal Pengiriman',
            'activeMenu'   => 'purchase-order',
            'moduleName'   => 'Pengadaan Bahan Baku (Purchase Order) & Jadwal Kirim',
            'author'       => 'Divisi Purchasing',
            'features'     => [
                'Penerbitan dokumen PO dengan nomor otomatis (PO-YYYYMMDD-XXXX)',
                'Form transaksi dinamis: tambah dan hapus baris item material via Vanilla JS DOM',
                'Kalkulasi subtotal dan grand total otomatis secara real-time saat angka diketik',
                'Penjadwalan lead time hari dan estimasi tanggal kedatangan di docking gudang (ETA)',
                'Integritas data terkunci: Composite Foreign Key mencegah pemilihan material lintas vendor',
                'Tampilan cetak faktur pengadaan resmi berstandar printer'
            ]
        ];
        $this->render('purchase_order/index', $data);
    }

    /**
     * Halaman Manajemen Kriteria Pembobotan SAW
     */
    public function sawCriteria() {
        $data = [
            'pageTitle'    => 'Kriteria Pembobotan SPK SAW',
            'pageSubTitle' => 'Manajemen Bobot Preferensi (0–100%) & Atribut Benefit/Cost',
            'activeMenu'   => 'saw-criteria',
            'moduleName'   => 'Kriteria Pembobotan SPK SAW',
            'author'       => 'Divisi QC & Audit Supplier',
            'features'     => [
                'CRUD tolak ukur evaluasi supplier (Harga, Kualitas/Defect Rate, Lead Time, Fleksibilitas)',
                'Format bobot persentase presisi DECIMAL(5,2) dengan batas 0.00 s/d 100.00%',
                'Penanda kriteria aktif (is_active) untuk fleksibilitas skenario penilaian',
                'Validasi sistem: total bobot kriteria wajib tepat 100% sebelum kalkulasi dapat dijalankan'
            ]
        ];
        $this->render('saw/criteria', $data);
    }

    /**
     * Halaman Input Nilai Evaluasi Supplier
     */
    public function sawEvaluation() {
        $data = [
            'pageTitle'    => 'Evaluasi Kinerja Rekanan Supplier',
            'pageSubTitle' => 'Input Skor Matriks Keputusan Alternatif (X)',
            'activeMenu'   => 'saw-evaluation',
            'moduleName'   => 'Input Matriks Evaluasi Alternatif Supplier',
            'author'       => 'Divisi QC & Audit Supplier',
            'features'     => [
                'Pengisian skor kuantitatif kinerja supplier terhadap setiap kriteria aktif per periode',
                'Dibatasi hanya untuk vendor yang berstatus "Terverifikasi"',
                'Pemeriksaan Matrix Completeness Guard berbasis Cross Join untuk mendeteksi sel evaluasi yang belum lengkap',
                'Pencegahan duplikasi skor melalui unique constraint (vendor_id, criteria_id, periode_evaluasi)'
            ]
        ];
        $this->render('saw/evaluation', $data);
    }

    /**
     * Halaman Hasil Normalisasi & Perangkingan Supplier
     */
    public function sawRanking() {
        $data = [
            'pageTitle'    => 'Hasil Perangkingan Kelayakan Supplier (SAW)',
            'pageSubTitle' => 'Matriks Normalisasi (R), Skor Preferensi (V) & Ranking Bar',
            'activeMenu'   => 'saw-ranking',
            'moduleName'   => 'Kalkulasi Algoritma SAW & Visualisasi Ranking Bar',
            'author'       => 'Divisi SCM & Strategic Sourcing',
            'features'     => [
                'Kalkulasi otomatis Matriks Normalisasi (R) berdasarkan formula atribut Benefit dan Cost',
                'Perhitungan Nilai Preferensi Akhir (Vi) dengan bobot fraksi desimal (w = bobot / 100)',
                'Visual ranking bar horizontal berbasis persentase CSS progress meter (0–100%)',
                'Rekomendasi supplier terbaik juara 1 untuk prioritas penerbitan Purchase Order'
            ]
        ];
        $this->render('saw/ranking', $data);
    }
}
