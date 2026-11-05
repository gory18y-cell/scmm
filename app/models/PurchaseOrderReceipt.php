<?php
/**
 * ==============================================================================
 * MODEL: PURCHASE_ORDER_RECEIPT (PENERIMAAN FISIK GUDANG & UPDATE STOK)
 * ==============================================================================
 * Mengelola pencatatan fisik barang masuk di docking gudang manufaktur,
 * mutasi penambahan stok bahan baku ke master materials,
 * penerimaan bertahap / sebagian (partial receipt),
 * serta pencegahan proses ganda dengan transaksi ACID dan penguncian baris (FOR UPDATE).
 * ==============================================================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/PurchaseOrder.php';
require_once __DIR__ . '/PurchaseOrderDetail.php';
require_once __DIR__ . '/Material.php';

class PurchaseOrderReceipt {
    private $db;

    public function __construct(?PDO $pdo = null) {
        $this->db = $pdo ?? Database::connect();
    }

    /**
     * Mengambil instance PDO
     *
     * @return PDO
     */
    public function getDb() {
        return $this->db;
    }

    /**
     * Mengambil seluruh berkas tanda terima barang untuk sebuah Purchase Order
     *
     * @param int $poId
     * @return array
     */
    public function getByPoId($poId) {
        $sql = "SELECT 
                    r.`id`,
                    r.`po_id`,
                    r.`no_penerimaan`,
                    r.`tanggal_terima`,
                    r.`user_id`,
                    r.`catatan`,
                    r.`created_at`,
                    u.`nama_lengkap` AS `nama_penerima`,
                    u.`username` AS `username_penerima`,
                    u.`role` AS `role_penerima`
                FROM `po_receipts` r
                LEFT JOIN `users` u ON r.`user_id` = u.`id`
                WHERE r.`po_id` = :po_id
                ORDER BY r.`id` ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':po_id' => (int)$poId]);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil rincian item penerimaan lengkap berdasarkan ID penerimaan
     *
     * @param int $receiptId
     * @return array
     */
    public function getDetailsByReceiptId($receiptId) {
        $sql = "SELECT 
                    rd.`id`,
                    rd.`receipt_id`,
                    rd.`po_detail_id`,
                    rd.`material_id`,
                    rd.`jumlah_diterima`,
                    rd.`created_at`,
                    m.`kode_material`,
                    m.`nama_material`,
                    m.`kategori`,
                    m.`satuan`,
                    pod.`jumlah_pesanan`,
                    pod.`harga_satuan`
                FROM `po_receipt_details` rd
                INNER JOIN `materials` m ON rd.`material_id` = m.`id`
                INNER JOIN `purchase_order_details` pod ON rd.`po_detail_id` = pod.`id`
                WHERE rd.`receipt_id` = :receipt_id
                ORDER BY rd.`id` ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':receipt_id' => (int)$receiptId]);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil riwayat penerimaan PO beserta detail seluruh item per penerimaan
     *
     * @param int $poId
     * @return array
     */
    public function getReceiptsWithDetailsByPoId($poId) {
        $receipts = $this->getByPoId($poId);
        if (empty($receipts)) {
            return [];
        }

        foreach ($receipts as &$rcpt) {
            $rcpt['items'] = $this->getDetailsByReceiptId($rcpt['id']);
            $totalQty = 0;
            foreach ($rcpt['items'] as $item) {
                $totalQty += (float)$item['jumlah_diterima'];
            }
            $rcpt['total_qty_diterima'] = $totalQty;
        }
        unset($rcpt);

        return $receipts;
    }

    /**
     * Menghasilkan nomor tanda terima penerimaan gudang unik (Format: RCV-YYYYMM-XXXX)
     * Menggunakan penguncian baris jika berada dalam transaksi aktif
     *
     * @param string|null $tanggalTerima
     * @param bool $forUpdate
     * @return string
     */
    public function generateReceiptNumber($tanggalTerima = null, $forUpdate = false) {
        if (!empty($tanggalTerima)) {
            $time = strtotime($tanggalTerima);
        } else {
            $time = time();
        }

        $prefix = 'RCV-' . date('Ym', $time) . '-';

        $sql = "SELECT `no_penerimaan` 
                FROM `po_receipts` 
                WHERE `no_penerimaan` LIKE :prefix 
                ORDER BY `no_penerimaan` DESC 
                LIMIT 1";

        if ($forUpdate && $this->db->inTransaction()) {
            $sql .= " FOR UPDATE";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $lastReceipt = $stmt->fetchColumn();

        if ($lastReceipt) {
            $lastSequence = (int) substr($lastReceipt, strlen($prefix));
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . sprintf('%04d', $nextSequence);
    }

    /**
     * Memproses Konfirmasi Penerimaan Barang Fisik di Gudang (Goods Receipt)
     * --------------------------------------------------------------------------
     * 1. Menjalankan transaksi ACID dengan penguncian baris FOR UPDATE.
     * 2. Memvalidasi status PO (hanya 'Dalam Pengiriman' atau 'Sebagian Diterima').
     * 3. Memvalidasi jumlah diterima per item:
     *    - Tidak boleh negatif.
     *    - Tidak boleh melebihi sisa pesanan (jumlah_pesanan - jumlah_diterima).
     * 4. Mencatat nomor penerimaan baru dan riwayat ke po_receipts & po_receipt_details.
     * 5. Mengupdate akumulasi jumlah_diterima pada purchase_order_details.
     * 6. Menambah volume stok_gudang pada master materials secara atomik.
     * 7. Memperbarui status PO:
     *    - Jika seluruh item telah terpenuhi (100% diterima) -> 'Selesai'.
     *    - Jika baru sebagian -> 'Sebagian Diterima'.
     * --------------------------------------------------------------------------
     *
     * @param int $poId
     * @param int $userId
     * @param string $tanggalTerima
     * @param array $receivedItems Associative array: [pod_id => qty_received] atau array of ['po_detail_id' => ..., 'jumlah_diterima' => ...]
     * @param string|null $catatan
     * @return array
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function processReceipt($poId, $userId, $tanggalTerima, array $receivedItems, $catatan = null) {
        $poId   = (int)$poId;
        $userId = (int)$userId;

        if ($poId <= 0) {
            throw new InvalidArgumentException("ID Purchase Order tidak valid.");
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException("ID Petugas penerima gudang tidak valid.");
        }

        if (empty($tanggalTerima)) {
            $tanggalTerima = date('Y-m-d');
        }

        $d = DateTime::createFromFormat('Y-m-d', $tanggalTerima);
        if (!$d || $d->format('Y-m-d') !== $tanggalTerima) {
            throw new InvalidArgumentException("Format tanggal penerimaan tidak valid (wajib YYYY-MM-DD).");
        }

        $isLocalTx = false;

        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $isLocalTx = true;
            }

            // A. Kunci Header Dokumen PO
            $sqlPoLock = "SELECT `id`, `no_po`, `status_po` 
                          FROM `purchase_orders` 
                          WHERE `id` = :id 
                          FOR UPDATE";
            $stmtPoLock = $this->db->prepare($sqlPoLock);
            $stmtPoLock->execute([':id' => $poId]);
            $po = $stmtPoLock->fetch();

            if (!$po) {
                throw new RuntimeException("Dokumen Purchase Order dengan ID {$poId} tidak ditemukan.");
            }

            // Validasi status PO untuk penerimaan
            if ($po['status_po'] === 'Selesai') {
                throw new RuntimeException("Purchase Order nomor '{$po['no_po']}' sudah berstatus Selesai penuh. Seluruh pesanan telah diterima sebelumnya.");
            }

            if ($po['status_po'] === 'Dibatalkan') {
                throw new RuntimeException("Purchase Order nomor '{$po['no_po']}' telah dibatalkan. Tidak dapat memproses penerimaan barang.");
            }

            if (!in_array($po['status_po'], ['Dalam Pengiriman', 'Sebagian Diterima'], true)) {
                throw new RuntimeException("Penerimaan barang hanya dapat diproses setelah dokumen PO berstatus 'Dalam Pengiriman' atau 'Sebagian Diterima'. Status saat ini: '{$po['status_po']}'.");
            }

            // B. Kunci Rincian Item PO dan Dapatkan Data Material
            $sqlItemsLock = "SELECT 
                                pod.`id`,
                                pod.`po_id`,
                                pod.`vendor_id`,
                                pod.`vendor_material_id`,
                                COALESCE(pod.`material_id`, vm.`material_id`) AS `material_id`,
                                pod.`jumlah_pesanan`,
                                pod.`jumlah_diterima`,
                                (pod.`jumlah_pesanan` - pod.`jumlah_diterima`) AS `sisa_pesanan`,
                                m.`kode_material`,
                                m.`nama_material`,
                                m.`satuan`,
                                m.`stok_gudang`
                             FROM `purchase_order_details` pod
                             INNER JOIN `vendor_materials` vm ON pod.`vendor_material_id` = vm.`id`
                             INNER JOIN `materials` m ON COALESCE(pod.`material_id`, vm.`material_id`) = m.`id`
                             WHERE pod.`po_id` = :po_id
                             FOR UPDATE";

            $stmtItemsLock = $this->db->prepare($sqlItemsLock);
            $stmtItemsLock->execute([':po_id' => $poId]);
            $dbItems = $stmtItemsLock->fetchAll();

            if (empty($dbItems)) {
                throw new RuntimeException("Rincian item pesanan untuk Purchase Order '{$po['no_po']}' tidak ditemukan.");
            }

            // Index database items by pod id
            $indexedDbItems = [];
            foreach ($dbItems as $row) {
                $indexedDbItems[(int)$row['id']] = $row;
            }

            // C. Strukturkan dan Validasi Input Barang Diterima
            $processedItems = [];
            $totalReceivedThisBatch = 0.0;

            // Normalisasi input received items
            $normalizedInputs = [];
            foreach ($receivedItems as $k => $v) {
                if (is_array($v)) {
                    $pId = (int)($v['po_detail_id'] ?? $v['id'] ?? 0);
                    $qty = $v['jumlah_diterima'] ?? 0;
                    if ($pId > 0) {
                        $normalizedInputs[$pId] = $qty;
                    }
                } else {
                    $pId = (int)$k;
                    if ($pId > 0) {
                        $normalizedInputs[$pId] = $v;
                    }
                }
            }

            foreach ($indexedDbItems as $podId => $item) {
                if (!isset($normalizedInputs[$podId])) {
                    continue;
                }

                $rawQty = trim((string)$normalizedInputs[$podId]);
                if ($rawQty === '') {
                    $rawQty = '0';
                }

                if (!is_numeric($rawQty)) {
                    throw new InvalidArgumentException("Jumlah diterima untuk bahan baku '{$item['nama_material']}' harus berupa angka valid.");
                }

                $qtyReceived = (float)$rawQty;

                if ($qtyReceived < 0) {
                    throw new InvalidArgumentException("Jumlah diterima untuk bahan baku '{$item['nama_material']}' tidak boleh bernilai negatif ({$qtyReceived}).");
                }

                // Jika 0, lewati (tidak ada yang diterima untuk item ini pada pengiriman saat ini)
                if ($qtyReceived <= 0.00001) {
                    continue;
                }

                $sisaPesanan = (float)$item['sisa_pesanan'];

                // Validasi agar jumlah diterima tidak melebihi sisa pesanan (toleransi float 0.0001)
                if ($qtyReceived > ($sisaPesanan + 0.0001)) {
                    $fmtQty = number_format($qtyReceived, 2);
                    $fmtSisa = number_format($sisaPesanan, 2);
                    throw new InvalidArgumentException("Jumlah diterima untuk material '{$item['nama_material']}' ({$fmtQty} {$item['satuan']}) melebihi sisa pesanan yang belum datang ({$fmtSisa} {$item['satuan']}).");
                }

                $processedItems[] = [
                    'po_detail_id'    => $podId,
                    'material_id'     => (int)$item['material_id'],
                    'nama_material'   => $item['nama_material'],
                    'kode_material'   => $item['kode_material'],
                    'satuan'          => $item['satuan'],
                    'jumlah_diterima' => $qtyReceived
                ];

                $totalReceivedThisBatch += $qtyReceived;
            }

            // Minimal 1 item harus memiliki jumlah diterima > 0
            if (empty($processedItems) || $totalReceivedThisBatch <= 0.00001) {
                throw new InvalidArgumentException("Harus ada minimal satu item material bahan baku dengan jumlah fisik diterima lebih besar dari nol.");
            }

            // D. Generate Nomor Penerimaan Unik & Simpan Header Penerimaan
            $noPenerimaan = $this->generateReceiptNumber($tanggalTerima, true);

            $sqlInsertReceipt = "INSERT INTO `po_receipts` (
                                    `po_id`, 
                                    `no_penerimaan`, 
                                    `tanggal_terima`, 
                                    `user_id`, 
                                    `catatan`
                                 ) VALUES (
                                    :po_id, 
                                    :no_penerimaan, 
                                    :tanggal_terima, 
                                    :user_id, 
                                    :catatan
                                 )";
            $stmtReceipt = $this->db->prepare($sqlInsertReceipt);
            $stmtReceipt->execute([
                ':po_id'          => $poId,
                ':no_penerimaan'  => $noPenerimaan,
                ':tanggal_terima' => $tanggalTerima,
                ':user_id'        => $userId,
                ':catatan'        => !empty($catatan) ? trim($catatan) : null
            ]);

            $receiptId = (int)$this->db->lastInsertId();

            // E. Simpan Rincian Penerimaan, Update Detail PO & Tambah Stok Master Material
            $sqlInsertDetail = "INSERT INTO `po_receipt_details` (
                                    `receipt_id`,
                                    `po_detail_id`,
                                    `material_id`,
                                    `jumlah_diterima`
                                ) VALUES (
                                    :receipt_id,
                                    :po_detail_id,
                                    :material_id,
                                    :jumlah_diterima
                                )";
            $stmtInsertDetail = $this->db->prepare($sqlInsertDetail);

            $sqlUpdatePod = "UPDATE `purchase_order_details` 
                             SET `jumlah_diterima` = `jumlah_diterima` + :qty,
                                 `material_id`     = :material_id
                             WHERE `id` = :pod_id";
            $stmtUpdatePod = $this->db->prepare($sqlUpdatePod);

            // Tambahkan stok pada modul Bahan Baku (materials.stok_gudang)
            $sqlUpdateStock = "UPDATE `materials` 
                               SET `stok_gudang` = `stok_gudang` + :qty 
                               WHERE `id` = :mat_id";
            $stmtUpdateStock = $this->db->prepare($sqlUpdateStock);

            foreach ($processedItems as $pItem) {
                // 1. Catat ke po_receipt_details
                $stmtInsertDetail->execute([
                    ':receipt_id'      => $receiptId,
                    ':po_detail_id'    => $pItem['po_detail_id'],
                    ':material_id'     => $pItem['material_id'],
                    ':jumlah_diterima' => $pItem['jumlah_diterima']
                ]);

                // 2. Akumulasi jumlah_diterima pada item PO
                $stmtUpdatePod->execute([
                    ':qty'         => $pItem['jumlah_diterima'],
                    ':material_id' => $pItem['material_id'],
                    ':pod_id'      => $pItem['po_detail_id']
                ]);

                // 3. Mutasi stok masuk pada master material
                $stmtUpdateStock->execute([
                    ':qty'    => $pItem['jumlah_diterima'],
                    ':mat_id' => $pItem['material_id']
                ]);
            }

            // F. Evaluasi Status Akhir Dokumen Purchase Order
            $sqlCheckFulfillment = "SELECT 
                                        COUNT(*) AS total_items,
                                        SUM(CASE WHEN `jumlah_diterima` >= (`jumlah_pesanan` - 0.0001) THEN 1 ELSE 0 END) AS fulfilled_items,
                                        SUM(`jumlah_pesanan` - `jumlah_diterima`) AS total_sisa
                                    FROM `purchase_order_details` 
                                    WHERE `po_id` = :po_id";
            $stmtFulfill = $this->db->prepare($sqlCheckFulfillment);
            $stmtFulfill->execute([':po_id' => $poId]);
            $fulfillStatus = $stmtFulfill->fetch();

            $totalItems     = (int)$fulfillStatus['total_items'];
            $fulfilledItems = (int)$fulfillStatus['fulfilled_items'];
            $totalSisa      = (float)$fulfillStatus['total_sisa'];

            if ($totalItems > 0 && $fulfilledItems >= $totalItems && $totalSisa <= 0.0001) {
                // Seluruh item telah terpenuhi sempurna
                $newStatus = 'Selesai';
            } else {
                // Masih ada sebagian barang yang belum tiba
                $newStatus = 'Sebagian Diterima';
            }

            // Update status PO
            $sqlUpdatePo = "UPDATE `purchase_orders` 
                            SET `status_po` = :status_po 
                            WHERE `id` = :po_id";
            $stmtUpdatePo = $this->db->prepare($sqlUpdatePo);
            $stmtUpdatePo->execute([
                ':status_po' => $newStatus,
                ':po_id'     => $poId
            ]);

            if ($isLocalTx && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return [
                'receipt_id'     => $receiptId,
                'no_penerimaan'  => $noPenerimaan,
                'po_id'          => $poId,
                'no_po'          => $po['no_po'],
                'new_status'     => $newStatus,
                'total_qty'      => $totalReceivedThisBatch,
                'item_count'     => count($processedItems),
                'items_received' => $processedItems
            ];

        } catch (Exception $e) {
            if ($isLocalTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}

