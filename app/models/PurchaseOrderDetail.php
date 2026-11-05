<?php
/**
 * ==============================================================================
 * MODEL: PURCHASE_ORDER_DETAIL (RINCIAN ITEM PURCHASE ORDER)
 * ==============================================================================
 * Mata Kuliah : Pemrograman Web Dasar (PHP Native MVC, OOP, PDO Security)
 * Target DB   : db_scm_otomotif (Tabel `purchase_order_details`)
 * Relasi      : Composite FK Ganda (po_id, vendor_id) & (vendor_material_id, vendor_id)
 *               Relasi Langsung ke master `materials` (`material_id`)
 * ==============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class PurchaseOrderDetail {
    private $db;

    public function __construct(?PDO $pdo = null) {
        $this->db = $pdo ?? Database::connect();
    }

    /**
     * Mengambil seluruh rincian item pesanan dari sebuah Purchase Order
     * Dilengkapi kuantitas sudah diterima, sisa pesanan, dan stok fisik gudang saat ini.
     *
     * @param int $poId
     * @return array
     */
    public function getByPoId($poId) {
        $sql = "SELECT 
                    pod.`id`,
                    pod.`po_id`,
                    pod.`vendor_id`,
                    pod.`vendor_material_id`,
                    COALESCE(pod.`material_id`, vm.`material_id`) AS `material_id`,
                    pod.`jumlah_pesanan`,
                    pod.`jumlah_diterima`,
                    (pod.`jumlah_pesanan` - pod.`jumlah_diterima`) AS `sisa_pesanan`,
                    pod.`harga_satuan`,
                    pod.`subtotal`,
                    m.`kode_material`,
                    m.`nama_material`,
                    m.`kategori`,
                    m.`satuan`,
                    m.`stok_gudang`,
                    vm.`standar_lead_time`
                FROM `purchase_order_details` pod
                INNER JOIN `vendor_materials` vm ON pod.`vendor_material_id` = vm.`id`
                INNER JOIN `materials` m ON COALESCE(pod.`material_id`, vm.`material_id`) = m.`id`
                WHERE pod.`po_id` = :po_id
                ORDER BY pod.`id` ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':po_id' => (int)$poId]);
        return $stmt->fetchAll();
    }

    /**
     * Menyimpan satu baris detail item pesanan
     * Mematuhi skema aktual, composite foreign key, dan relasi langsung material_id
     *
     * @param array $data
     * @return int ID detail yang baru dibuat
     * @throws InvalidArgumentException
     */
    public function create(array $data) {
        // Validasi keberadaan field esensial
        if (!isset($data['po_id'], $data['vendor_id'], $data['vendor_material_id'], $data['jumlah_pesanan'], $data['harga_satuan'])) {
            throw new InvalidArgumentException("Data item PO tidak lengkap untuk disimpan ke basis data.");
        }

        $poId             = (int)$data['po_id'];
        $vendorId         = (int)$data['vendor_id'];
        $vendorMaterialId = (int)$data['vendor_material_id'];
        $jumlahPesanan    = (float)$data['jumlah_pesanan'];
        $hargaSatuan      = (float)$data['harga_satuan'];
        $jumlahDiterima   = isset($data['jumlah_diterima']) ? (float)$data['jumlah_diterima'] : 0.00;

        if ($jumlahPesanan <= 0) {
            throw new InvalidArgumentException("Jumlah pesanan material harus lebih besar dari 0.");
        }

        // Tentukan material_id dari input atau relasi vendor_materials
        $materialId = !empty($data['material_id']) ? (int)$data['material_id'] : null;
        if (!$materialId) {
            $stmtVm = $this->db->prepare("SELECT `material_id` FROM `vendor_materials` WHERE `id` = :vmid");
            $stmtVm->execute([':vmid' => $vendorMaterialId]);
            $vmRow = $stmtVm->fetch();
            $materialId = $vmRow ? (int)$vmRow['material_id'] : null;
        }

        // Subtotal dihitung secara pasti di sisi server
        $subtotal = isset($data['subtotal']) ? (float)$data['subtotal'] : $this->calculateSubtotal($jumlahPesanan, $hargaSatuan);

        $sql = "INSERT INTO `purchase_order_details` (
                    `po_id`,
                    `vendor_id`,
                    `vendor_material_id`,
                    `material_id`,
                    `jumlah_pesanan`,
                    `jumlah_diterima`,
                    `harga_satuan`,
                    `subtotal`
                ) VALUES (
                    :po_id,
                    :vendor_id,
                    :vendor_material_id,
                    :material_id,
                    :jumlah_pesanan,
                    :jumlah_diterima,
                    :harga_satuan,
                    :subtotal
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':po_id'              => $poId,
            ':vendor_id'          => $vendorId,
            ':vendor_material_id' => $vendorMaterialId,
            ':material_id'        => $materialId,
            ':jumlah_pesanan'     => $jumlahPesanan,
            ':jumlah_diterima'    => $jumlahDiterima,
            ':harga_satuan'       => $hargaSatuan,
            ':subtotal'           => $subtotal
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Menghitung subtotal matematis sisi server (jumlah_pesanan x harga_satuan)
     *
     * @param float $jumlahPesanan
     * @param float $hargaSatuan
     * @return float
     */
    public function calculateSubtotal($jumlahPesanan, $hargaSatuan) {
        return round((float)$jumlahPesanan * (float)$hargaSatuan, 2);
    }

    /**
     * Menghapus seluruh detail item berdasarkan po_id
     *
     * @param int $poId
     * @return bool
     */
    public function deleteByPoId($poId) {
        $sql = "DELETE FROM `purchase_order_details` WHERE `po_id` = :po_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':po_id' => (int)$poId]);
    }
}
