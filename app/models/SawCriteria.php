<?php
/**
 * ==============================================================================
 * MODEL: SAW_CRITERIA (KRITERIA & BOBOT METODE SIMPLE ADDITIVE WEIGHTING)
 * ==============================================================================
 * Mengelola kriteria keputusan, bobot persentase, dan tipe kriteria (Benefit/Cost)
 * Target DB : db_scm_otomotif (Tabel `saw_criteria`)
 * Modul     : SPK Pembobotan Kriteria
 * ==============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class SawCriteria {
    private $db;

    public function __construct(?PDO $pdo = null) {
        $this->db = $pdo ?? Database::connect();
    }

    /**
     * Mengambil instance PDO aktif
     *
     * @return PDO
     */
    public function getDb() {
        return $this->db;
    }

    /**
     * Mengambil seluruh kriteria keputusan SAW
     *
     * @param bool $activeOnly Apakah hanya kriteria yang aktif (is_active = 1)
     * @return array
     */
    public function getAll($activeOnly = false) {
        if ($activeOnly) {
            $sql = "SELECT * FROM `saw_criteria` WHERE `is_active` = 1 ORDER BY `kode_kriteria` ASC, `id` ASC";
        } else {
            $sql = "SELECT * FROM `saw_criteria` ORDER BY `kode_kriteria` ASC, `id` ASC";
        }
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil satu baris kriteria berdasarkan ID primary key
     *
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT * FROM `saw_criteria` WHERE `id` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Mengambil kriteria berdasarkan kode kriteria unik (contoh: 'C1')
     *
     * @param string $kode
     * @return array|false
     */
    public function getByCode($kode) {
        $sql = "SELECT * FROM `saw_criteria` WHERE `kode_kriteria` = :kode LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':kode' => trim($kode)]);
        return $stmt->fetch();
    }

    /**
     * Memeriksa apakah kode kriteria sudah ada untuk mencegah duplikasi
     *
     * @param string $kode
     * @param int|null $excludeId ID yang dikecualikan (saat update)
     * @return bool
     */
    public function isCodeExists($kode, $excludeId = null) {
        if ($excludeId !== null) {
            $sql = "SELECT COUNT(*) FROM `saw_criteria` WHERE `kode_kriteria` = :kode AND `id` != :exclude_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':kode'       => trim($kode),
                ':exclude_id' => (int)$excludeId
            ]);
        } else {
            $sql = "SELECT COUNT(*) FROM `saw_criteria` WHERE `kode_kriteria` = :kode";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':kode' => trim($kode)]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Menghitung total persentase bobot dari kriteria yang aktif
     *
     * @param int|null $excludeId ID kriteria yang dikecualikan (saat simulasi update)
     * @return float
     */
    public function getTotalWeight($excludeId = null) {
        if ($excludeId !== null) {
            $sql = "SELECT COALESCE(SUM(`bobot`), 0) FROM `saw_criteria` WHERE `is_active` = 1 AND `id` != :exclude_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':exclude_id' => (int)$excludeId]);
        } else {
            $sql = "SELECT COALESCE(SUM(`bobot`), 0) FROM `saw_criteria` WHERE `is_active` = 1";
            $stmt = $this->db->query($sql);
        }
        return (float)$stmt->fetchColumn();
    }

    /**
     * Memeriksa apakah total bobot kriteria aktif saat ini TEPAT 100.00%
     *
     * @return bool
     */
    public function isTotalWeightExact100() {
        $total = $this->getTotalWeight();
        // Toleransi floating point 0.001
        return abs($total - 100.0) < 0.001;
    }

    /**
     * Menambahkan kriteria pembobotan baru
     *
     * @param array $data ['kode_kriteria', 'nama_kriteria', 'bobot', 'tipe', ?'is_active']
     * @return int ID yang baru dibuat
     * @throws InvalidArgumentException
     */
    public function create(array $data) {
        $kode   = strtoupper(trim($data['kode_kriteria'] ?? ''));
        $nama   = trim($data['nama_kriteria'] ?? '');
        $bobot  = (float)($data['bobot'] ?? 0);
        $tipe   = trim($data['tipe'] ?? 'Benefit');
        $active = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;

        if (empty($kode) || empty($nama)) {
            throw new InvalidArgumentException("Kode kriteria dan nama kriteria wajib diisi.");
        }

        if (!in_array($tipe, ['Benefit', 'Cost'], true)) {
            throw new InvalidArgumentException("Tipe kriteria tidak valid. Hanya diperbolehkan 'Benefit' atau 'Cost'.");
        }

        if ($bobot <= 0 || $bobot > 100.0) {
            throw new InvalidArgumentException("Bobot kriteria harus bernilai lebih besar dari 0% dan maksimal 100%.");
        }

        if ($this->isCodeExists($kode)) {
            throw new InvalidArgumentException("Kode kriteria '{$kode}' sudah terdaftar di sistem.");
        }

        // Jika kriteria aktif, validasi agar akumulasi total bobot tidak melebihi 100%
        if ($active === 1) {
            $currentTotal = $this->getTotalWeight();
            if (($currentTotal + $bobot) > 100.001) {
                $sisa = 100.0 - $currentTotal;
                throw new InvalidArgumentException("Total bobot kriteria aktif akan melebihi 100% (saat ini: {$currentTotal}%, maksimal penambahan: {$sisa}%).");
            }
        }

        $sql = "INSERT INTO `saw_criteria` (`kode_kriteria`, `nama_kriteria`, `bobot`, `tipe`, `is_active`) 
                VALUES (:kode, :nama, :bobot, :tipe, :active)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':kode'   => $kode,
            ':nama'   => $nama,
            ':bobot'  => number_format($bobot, 2, '.', ''),
            ':tipe'   => $tipe,
            ':active' => $active
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Memperbarui data kriteria pembobotan
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function update($id, array $data) {
        $current = $this->getById($id);
        if (!$current) {
            throw new RuntimeException("Kriteria SAW dengan ID {$id} tidak ditemukan.");
        }

        $kode   = strtoupper(trim($data['kode_kriteria'] ?? $current['kode_kriteria']));
        $nama   = trim($data['nama_kriteria'] ?? $current['nama_kriteria']);
        $bobot  = isset($data['bobot']) ? (float)$data['bobot'] : (float)$current['bobot'];
        $tipe   = trim($data['tipe'] ?? $current['tipe']);
        $active = isset($data['is_active']) ? (int)(bool)$data['is_active'] : (int)$current['is_active'];

        if (empty($kode) || empty($nama)) {
            throw new InvalidArgumentException("Kode kriteria dan nama kriteria wajib diisi.");
        }

        if (!in_array($tipe, ['Benefit', 'Cost'], true)) {
            throw new InvalidArgumentException("Tipe kriteria tidak valid. Hanya diperbolehkan 'Benefit' atau 'Cost'.");
        }

        if ($bobot <= 0 || $bobot > 100.0) {
            throw new InvalidArgumentException("Bobot kriteria harus bernilai lebih besar dari 0% dan maksimal 100%.");
        }

        if ($this->isCodeExists($kode, $id)) {
            throw new InvalidArgumentException("Kode kriteria '{$kode}' sudah digunakan oleh kriteria lain.");
        }

        // Jika kriteria diaktifkan, validasi agar akumulasi bobot tidak melebihi 100%
        if ($active === 1) {
            $otherTotal = $this->getTotalWeight($id);
            if (($otherTotal + $bobot) > 100.001) {
                $sisa = 100.0 - $otherTotal;
                throw new InvalidArgumentException("Total bobot kriteria aktif akan melebihi 100% (bobot kriteria aktif lain: {$otherTotal}%, batas maksimal: {$sisa}%).");
            }
        }

        $sql = "UPDATE `saw_criteria` 
                SET `kode_kriteria` = :kode,
                    `nama_kriteria` = :nama,
                    `bobot`         = :bobot,
                    `tipe`          = :tipe,
                    `is_active`     = :active 
                WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':kode'   => $kode,
            ':nama'   => $nama,
            ':bobot'  => number_format($bobot, 2, '.', ''),
            ':tipe'   => $tipe,
            ':active' => $active,
            ':id'     => (int)$id
        ]);
    }

    /**
     * Menghapus kriteria keputusan
     *
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM `saw_criteria` WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
    }

    /**
     * Mengubah status aktif kriteria (Toggle Active)
     *
     * @param int $id
     * @return bool
     * @throws RuntimeException
     */
    public function toggleActive($id) {
        $current = $this->getById($id);
        if (!$current) {
            throw new RuntimeException("Kriteria dengan ID {$id} tidak ditemukan.");
        }

        $newActive = $current['is_active'] ? 0 : 1;

        // Jika diaktifkan, pastikan tidak membuat total bobot > 100%
        if ($newActive === 1) {
            $currentTotal = $this->getTotalWeight();
            $kriteriaBobot = (float)$current['bobot'];
            if (($currentTotal + $kriteriaBobot) > 100.001) {
                throw new RuntimeException("Kriteria tidak dapat diaktifkan karena total bobot akan menjadi " . ($currentTotal + $kriteriaBobot) . "% (melebihi 100%).");
            }
        }

        $sql = "UPDATE `saw_criteria` SET `is_active` = :active WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':active' => $newActive,
            ':id'     => (int)$id
        ]);
    }
}

