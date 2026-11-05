<?php
/**
 * ==============================================================================
 * MODEL: VENDOR_MATERIAL (KATALOG PEMETAAN BAHAN BAKU REKANAN LOGAM)
 * ==============================================================================
 * Mata Kuliah : Pemrograman Web Dasar (PHP Native MVC, OOP, PDO Security)
 * Target DB   : db_scm_otomotif (Tabel `vendor_materials` & `materials`)
 * ==============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class VendorMaterial {
    private $db;

    public function __construct(?PDO $pdo = null) {
        $this->db = $pdo ?? Database::connect();
    }

    /**
     * Mengambil katalog material aktif yang ditawarkan oleh vendor tertentu
     * Hanya material dengan status_pasokan = 'Tersedia' yang diambil
     *
     * @param int $vendorId
     * @return array
     */
    public function getByVendorId($vendorId) {
        $sql = "SELECT 
                    vm.`id` AS `vendor_material_id`,
                    vm.`vendor_id`,
                    vm.`material_id`,
                    vm.`harga_penawaran`,
                    vm.`standar_lead_time`,
                    vm.`status_pasokan`,
                    m.`kode_material`,
                    m.`nama_material`,
                    m.`kategori`,
                    m.`satuan`,
                    m.`spesifikasi_teknis`,
                    m.`stok_gudang`
                FROM `vendor_materials` vm
                INNER JOIN `materials` m ON vm.`material_id` = m.`id`
                WHERE vm.`vendor_id` = :vendor_id 
                  AND vm.`status_pasokan` = 'Tersedia'
                ORDER BY m.`nama_material` ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':vendor_id' => (int)$vendorId]);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil satu baris penawaran material vendor berdasarkan ID primary key
     *
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT 
                    vm.`id` AS `vendor_material_id`,
                    vm.`vendor_id`,
                    vm.`material_id`,
                    vm.`harga_penawaran`,
                    vm.`standar_lead_time`,
                    vm.`status_pasokan`,
                    m.`kode_material`,
                    m.`nama_material`,
                    m.`kategori`,
                    m.`satuan`,
                    m.`spesifikasi_teknis`,
                    m.`stok_gudang`
                FROM `vendor_materials` vm
                INNER JOIN `materials` m ON vm.`material_id` = m.`id`
                WHERE vm.`id` = :id 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Mengambil satu baris penawaran material dengan verifikasi kepemilikan vendor
     * Menjamin integritas relasi bahwa vendor_material_id benar milik vendor_id tersebut
     *
     * @param int $id ID dari vendor_materials
     * @param int $vendorId ID dari vendors
     * @return array|false
     */
    public function getByIdAndVendor($id, $vendorId) {
        $sql = "SELECT 
                    vm.`id` AS `vendor_material_id`,
                    vm.`vendor_id`,
                    vm.`material_id`,
                    vm.`harga_penawaran`,
                    vm.`standar_lead_time`,
                    vm.`status_pasokan`,
                    m.`kode_material`,
                    m.`nama_material`,
                    m.`kategori`,
                    m.`satuan`
                FROM `vendor_materials` vm
                INNER JOIN `materials` m ON vm.`material_id` = m.`id`
                WHERE vm.`id` = :id 
                  AND vm.`vendor_id` = :vendor_id 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'        => (int)$id,
            ':vendor_id' => (int)$vendorId
        ]);
        return $stmt->fetch();
    }
}

