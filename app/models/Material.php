<?php
/**
 * ==============================================================================
 * MODEL: MATERIAL (MASTER BAHAN BAKU LOGAM OTOMOTIF)
 * ==============================================================================
 * Mata Kuliah : Pemrograman Web Dasar (PHP Native MVC, OOP, PDO Security)
 * Target DB   : db_scm_otomotif (Tabel `materials`)
 * ==============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class Material {
    private $db;

    public function __construct(?PDO $pdo = null) {
        $this->db = $pdo ?? Database::connect();
    }

    /**
     * Mengambil instance PDO Database
     *
     * @return PDO
     */
    public function getDb() {
        return $this->db;
    }

    /**
     * Mengambil seluruh data master material logam otomotif beserta jumlah vendor pemasok
     *
     * @return array
     */
    public function getAll() {
        $sql = "SELECT m.`id`, m.`kode_material`, m.`nama_material`, m.`kategori`, m.`satuan`, 
                       m.`spesifikasi_teknis`, m.`stok_gudang`,
                       (SELECT COUNT(*) FROM `vendor_materials` vm WHERE vm.`material_id` = m.`id`) AS `vendor_count`
                FROM `materials` m
                ORDER BY m.`id` ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil satu data material berdasarkan ID primary key
     *
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT m.`id`, m.`kode_material`, m.`nama_material`, m.`kategori`, m.`satuan`, 
                       m.`spesifikasi_teknis`, m.`stok_gudang`,
                       (SELECT COUNT(*) FROM `vendor_materials` vm WHERE vm.`material_id` = m.`id`) AS `vendor_count`
                FROM `materials` m
                WHERE m.`id` = :id 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Mengambil satu data material berdasarkan kode material unik
     *
     * @param string $kodeMaterial
     * @return array|false
     */
    public function getByKode($kodeMaterial) {
        $sql = "SELECT `id`, `kode_material`, `nama_material`, `kategori`, `satuan`, 
                       `spesifikasi_teknis`, `stok_gudang`
                FROM `materials` 
                WHERE `kode_material` = :kode 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':kode' => trim($kodeMaterial)]);
        return $stmt->fetch();
    }

    /**
     * Memeriksa apakah kode material sudah digunakan oleh material lain
     *
     * @param string $kodeMaterial
     * @param int|null $excludeId
     * @return bool
     */
    public function isKodeExists($kodeMaterial, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM `materials` WHERE `kode_material` = :kode";
        $params = [':kode' => strtoupper(trim($kodeMaterial))];

        if ($excludeId !== null && (int)$excludeId > 0) {
            $sql .= " AND `id` != :exclude_id";
            $params[':exclude_id'] = (int)$excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ((int)$stmt->fetchColumn() > 0);
    }

    /**
     * Memeriksa apakah material terhubung dalam pemetaan pasokan vendor atau transaksi Purchase Order
     *
     * @param int $id
     * @return bool
     */
    public function isUsedInRelations($id) {
        $id = (int)$id;

        // 1. Cek relasi di vendor_materials
        $stmtVm = $this->db->prepare("SELECT COUNT(*) FROM `vendor_materials` WHERE `material_id` = :id");
        $stmtVm->execute([':id' => $id]);
        if ((int)$stmtVm->fetchColumn() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Menambahkan bahan baku material baru ke basis data
     *
     * @param array $data
     * @return int ID record baru
     * @throws InvalidArgumentException|RuntimeException
     */
    public function create(array $data) {
        $kodeMaterial       = strtoupper(trim($data['kode_material'] ?? ''));
        $namaMaterial       = trim($data['nama_material'] ?? '');
        $kategori           = trim($data['kategori'] ?? 'Plat Baja');
        $satuan             = trim($data['satuan'] ?? 'Kg');
        $spesifikasiTeknis  = trim($data['spesifikasi_teknis'] ?? '');
        $stokGudang         = (float)($data['stok_gudang'] ?? 0.00);

        if (empty($kodeMaterial)) {
            throw new InvalidArgumentException("Kode material wajib diisi.");
        }
        if (empty($namaMaterial)) {
            throw new InvalidArgumentException("Nama material wajib diisi.");
        }
        if ($this->isKodeExists($kodeMaterial)) {
            throw new InvalidArgumentException("Kode material '{$kodeMaterial}' sudah terdaftar dalam sistem.");
        }

        $allowedKategori = ['Plat Baja', 'Pipa Baja', 'Aluminium Alloy', 'Besi Tempa'];
        if (!in_array($kategori, $allowedKategori, true)) {
            throw new InvalidArgumentException("Kategori material '{$kategori}' tidak valid.");
        }

        if (empty($satuan)) {
            $satuan = 'Kg';
        }
        if ($stokGudang < 0) {
            throw new InvalidArgumentException("Stok gudang tidak boleh bernilai negatif.");
        }

        $sql = "INSERT INTO `materials` (`kode_material`, `nama_material`, `kategori`, `satuan`, `spesifikasi_teknis`, `stok_gudang`)
                VALUES (:kode, :nama, :kategori, :satuan, :spesifikasi, :stok)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':kode'        => $kodeMaterial,
            ':nama'        => $namaMaterial,
            ':kategori'    => $kategori,
            ':satuan'      => $satuan,
            ':spesifikasi' => !empty($spesifikasiTeknis) ? $spesifikasiTeknis : null,
            ':stok'        => number_format($stokGudang, 2, '.', '')
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Memperbarui data bahan baku material
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws InvalidArgumentException|RuntimeException
     */
    public function update($id, array $data) {
        $id = (int)$id;
        if ($id <= 0) {
            throw new InvalidArgumentException("ID material tidak valid.");
        }

        $kodeMaterial       = strtoupper(trim($data['kode_material'] ?? ''));
        $namaMaterial       = trim($data['nama_material'] ?? '');
        $kategori           = trim($data['kategori'] ?? 'Plat Baja');
        $satuan             = trim($data['satuan'] ?? 'Kg');
        $spesifikasiTeknis  = trim($data['spesifikasi_teknis'] ?? '');
        $stokGudang         = (float)($data['stok_gudang'] ?? 0.00);

        if (empty($kodeMaterial)) {
            throw new InvalidArgumentException("Kode material wajib diisi.");
        }
        if (empty($namaMaterial)) {
            throw new InvalidArgumentException("Nama material wajib diisi.");
        }
        if ($this->isKodeExists($kodeMaterial, $id)) {
            throw new InvalidArgumentException("Kode material '{$kodeMaterial}' sudah digunakan oleh material lain.");
        }

        $allowedKategori = ['Plat Baja', 'Pipa Baja', 'Aluminium Alloy', 'Besi Tempa'];
        if (!in_array($kategori, $allowedKategori, true)) {
            throw new InvalidArgumentException("Kategori material '{$kategori}' tidak valid.");
        }

        if (empty($satuan)) {
            $satuan = 'Kg';
        }
        if ($stokGudang < 0) {
            throw new InvalidArgumentException("Stok gudang tidak boleh bernilai negatif.");
        }

        $sql = "UPDATE `materials` 
                SET `kode_material` = :kode,
                    `nama_material` = :nama,
                    `kategori` = :kategori,
                    `satuan` = :satuan,
                    `spesifikasi_teknis` = :spesifikasi,
                    `stok_gudang` = :stok
                WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'          => $id,
            ':kode'        => $kodeMaterial,
            ':nama'        => $namaMaterial,
            ':kategori'    => $kategori,
            ':satuan'      => $satuan,
            ':spesifikasi' => !empty($spesifikasiTeknis) ? $spesifikasiTeknis : null,
            ':stok'        => number_format($stokGudang, 2, '.', '')
        ]);
    }

    /**
     * Menghapus data material dengan pemeriksaan relasi
     *
     * @param int $id
     * @return bool
     * @throws RuntimeException
     */
    public function delete($id) {
        $id = (int)$id;
        if ($id <= 0) {
            throw new InvalidArgumentException("ID material tidak valid.");
        }

        if ($this->isUsedInRelations($id)) {
            throw new RuntimeException("Material tidak dapat dihapus karena telah terhubung dengan data pemetaan vendor atau transaksi Purchase Order.");
        }

        $sql = "DELETE FROM `materials` WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
