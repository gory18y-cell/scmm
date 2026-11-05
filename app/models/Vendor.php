<?php
/**
 * ==============================================================================
 * MODEL: VENDOR (REKANAN LOGAM & MUTU ISO) - MODUL 1 (GORI)
 * ==============================================================================
 * Mengelola operasi basis data untuk entitas rekanan vendor logam otomotif
 * dengan 100% Prepared Statements PDO Strict Mode.
 * ==============================================================================
 */

require_once __DIR__ . '/../../config/database.php';

class Vendor {
    private $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    /**
     * Mengambil seluruh data rekanan vendor diurutkan dari yang terbaru
     *
     * @return array
     */
    public function getAll() {
        $sql = "SELECT * FROM `vendors` ORDER BY `id` DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil data satu vendor berdasarkan Primary Key (ID)
     *
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $sql = "SELECT * FROM `vendors` WHERE `id` = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    /**
     * Memeriksa apakah kode vendor sudah terdaftar untuk mencegah duplikasi
     *
     * @param string $kodeVendor
     * @param int|null $excludeId ID yang dikecualikan (digunakan saat update data)
     * @return bool
     */
    public function isKodeVendorExists($kodeVendor, $excludeId = null) {
        if ($excludeId !== null) {
            $sql = "SELECT COUNT(*) FROM `vendors` WHERE `kode_vendor` = :kode AND `id` != :exclude_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':kode'       => trim($kodeVendor),
                ':exclude_id' => (int)$excludeId
            ]);
        } else {
            $sql = "SELECT COUNT(*) FROM `vendors` WHERE `kode_vendor` = :kode";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':kode' => trim($kodeVendor)]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Menambahkan baris data vendor baru
     * ATURAN BISNIS: Pendaftaran baru WAJIB berkunci otomatis pada status 'Audit Pending'
     *
     * @param array $data Data vendor hasil validasi
     * @return int ID vendor yang baru dibuat
     */
    public function create(array $data) {
        $sql = "INSERT INTO `vendors` (
                    `kode_vendor`,
                    `nama_vendor`,
                    `spesialisasi_logam`,
                    `kontak_person`,
                    `email`,
                    `telepon`,
                    `alamat`,
                    `jenis_akreditasi`,
                    `no_sertifikat`,
                    `badan_sertifikasi`,
                    `masa_berlaku_iso`,
                    `status_verifikasi`,
                    `created_at`
                ) VALUES (
                    :kode_vendor,
                    :nama_vendor,
                    :spesialisasi_logam,
                    :kontak_person,
                    :email,
                    :telepon,
                    :alamat,
                    :jenis_akreditasi,
                    :no_sertifikat,
                    :badan_sertifikasi,
                    :masa_berlaku_iso,
                    'Audit Pending',
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':kode_vendor'        => trim($data['kode_vendor']),
            ':nama_vendor'        => trim($data['nama_vendor']),
            ':spesialisasi_logam' => trim($data['spesialisasi_logam']),
            ':kontak_person'      => trim($data['kontak_person']),
            ':email'              => trim($data['email']),
            ':telepon'            => trim($data['telepon']),
            ':alamat'             => trim($data['alamat']),
            ':jenis_akreditasi'   => $data['jenis_akreditasi'] ?? 'Non-ISO',
            ':no_sertifikat'      => !empty($data['no_sertifikat']) ? trim($data['no_sertifikat']) : null,
            ':badan_sertifikasi'  => !empty($data['badan_sertifikasi']) ? trim($data['badan_sertifikasi']) : null,
            ':masa_berlaku_iso'   => !empty($data['masa_berlaku_iso']) ? $data['masa_berlaku_iso'] : null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Memperbarui data umum rekanan vendor
     * ATURAN BISNIS: Form edit umum TIDAK BOLEH mengubah status verifikasi
     *
     * @param int $id
     * @param array $data Data perubahan hasil validasi
     * @return bool
     */
    public function update($id, array $data) {
        $sql = "UPDATE `vendors` SET
                    `kode_vendor`        = :kode_vendor,
                    `nama_vendor`        = :nama_vendor,
                    `spesialisasi_logam` = :spesialisasi_logam,
                    `kontak_person`      = :kontak_person,
                    `email`              = :email,
                    `telepon`            = :telepon,
                    `alamat`             = :alamat,
                    `jenis_akreditasi`   = :jenis_akreditasi,
                    `no_sertifikat`      = :no_sertifikat,
                    `badan_sertifikasi`  = :badan_sertifikasi,
                    `masa_berlaku_iso`   = :masa_berlaku_iso
                WHERE `id` = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'                 => (int)$id,
            ':kode_vendor'        => trim($data['kode_vendor']),
            ':nama_vendor'        => trim($data['nama_vendor']),
            ':spesialisasi_logam' => trim($data['spesialisasi_logam']),
            ':kontak_person'      => trim($data['kontak_person']),
            ':email'              => trim($data['email']),
            ':telepon'            => trim($data['telepon']),
            ':alamat'             => trim($data['alamat']),
            ':jenis_akreditasi'   => $data['jenis_akreditasi'] ?? 'Non-ISO',
            ':no_sertifikat'      => !empty($data['no_sertifikat']) ? trim($data['no_sertifikat']) : null,
            ':badan_sertifikasi'  => !empty($data['badan_sertifikasi']) ? trim($data['badan_sertifikasi']) : null,
            ':masa_berlaku_iso'   => !empty($data['masa_berlaku_iso']) ? $data['masa_berlaku_iso'] : null
        ]);
    }

    /**
     * Memperbarui status verifikasi vendor KHUSUS melalui proses verifikasi resmi
     *
     * @param int $id
     * @param string $status ('Terverifikasi', 'Audit Pending', 'Blacklist')
     * @return bool
     */
    public function updateStatus($id, $status) {
        $sql = "UPDATE `vendors` SET `status_verifikasi` = :status WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'     => (int)$id,
            ':status' => $status
        ]);
    }

    /**
     * Menghapus vendor berdasarkan ID
     *
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM `vendors` WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
    }
}
