<?php
/**
 * ==============================================================================
 * MODEL: PURCHASE_ORDER (HEADER PURCHASE ORDER & JADWAL LOGISTIK)
 * ==============================================================================
 * Target DB   : db_scm_otomotif (Tabel `purchase_orders`)
 * Modul       : Pengadaan Bahan Baku & Logistik
 * ==============================================================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/PurchaseOrderDetail.php';
require_once __DIR__ . '/VendorMaterial.php';

class PurchaseOrder
{
    private $db;

    /**
     * Nama unique index untuk nomor PO pada tabel purchase_orders
     * Terkonfirmasi dari database aktual (SHOW INDEX FROM purchase_orders)
     * dan database.sql: kolom `no_po` VARCHAR(30) NOT NULL UNIQUE -> Key_name: 'no_po'.
     */
    const UNIQUE_INDEX_NO_PO = 'no_po';

    public function __construct(?PDO $pdo = null)
    {
        $this->db = $pdo ?? Database::connect();
    }

    /**
     * Mengambil instance PDO aktif yang digunakan model
     *
     * @return PDO
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Memvalidasi format tanggal Y-m-d dan keabsahan kalender secara ketat
     *
     * @param string $tanggal
     * @param string $fieldName
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateDateStrict($tanggal, $fieldName = 'Tanggal PO')
    {
        if (empty($tanggal) || !is_string($tanggal)) {
            throw new InvalidArgumentException("{$fieldName} tidak boleh kosong.");
        }
        $d = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$d || $d->format('Y-m-d') !== $tanggal) {
            throw new InvalidArgumentException("{$fieldName} tidak valid: '{$tanggal}'. Format wajib YYYY-MM-DD dan merupakan tanggal kalender yang valid.");
        }
        return true;
    }

    /**
     * Memvalidasi kuantitas pesanan material dengan presisi DECIMAL(12,2) murni tanpa float
     * - Menolak nilai kosong, null, atau non-numerik
     * - Menolak nilai negatif, NAN, INF, dan nol
     * - Memperbolehkan nilai desimal hingga 2 angka di belakang koma (misal: 45.75, 1.50)
     * - Menolak kuantitas dengan lebih dari 2 angka desimal (misal: 1.234)
     * - Memvalidasi nilai positif dan batas DECIMAL(12,2) menggunakan perbandingan string desimal murni tanpa float
     * - Mengembalikan string desimal presisi ternormalisasi (format 'X.YY')
     *
     * @param mixed $jumlah
     * @return string Format 'X.YY'
     * @throws InvalidArgumentException
     */
    public function validateQuantity($jumlah)
    {
        if ($jumlah === null || $jumlah === '') {
            throw new InvalidArgumentException("Kuantitas pesanan tidak boleh kosong.");
        }

        // Tolak input bertipe float untuk mencegah risiko hilangnya presisi angka sebelum validasi
        if (is_float($jumlah)) {
            throw new InvalidArgumentException("Kuantitas pesanan tidak boleh bertipe float untuk mencegah hilangnya presisi angka. Gunakan format string desimal atau integer.");
        }

        if (!is_string($jumlah) && !is_int($jumlah)) {
            throw new InvalidArgumentException("Kuantitas pesanan harus berupa nilai numerik valid.");
        }

        $strVal = trim((string) $jumlah);

        // Tolak secara tegas tanda minus (nilai negatif) secara murni perbandingan string tanpa konversi float
        if (strpos($strVal, '-') !== false) {
            throw new InvalidArgumentException("Kuantitas pesanan tidak boleh bernilai negatif: '{$strVal}'.");
        }

        // Hanya menerima angka desimal positif standar tanpa karakter aneh
        if (!preg_match('/^\d+(\.\d+)?$/', $strVal)) {
            throw new InvalidArgumentException("Format kuantitas pesanan tidak valid: '{$strVal}'. Hanya angka positif yang diperbolehkan.");
        }

        // Validasi presisi desimal: maksimal 2 digit di belakang koma
        if (strpos($strVal, '.') !== false) {
            $decimals = strlen(substr(strrchr($strVal, "."), 1));
            if ($decimals > 2) {
                throw new InvalidArgumentException("Kuantitas pesanan tidak boleh memiliki lebih dari 2 angka desimal (diterima: '{$strVal}'). Batas presisi basis data adalah DECIMAL(12,2).");
            }
        }

        $parts = explode('.', $strVal);
        $intPart = ltrim($parts[0], '0') ?: '0';
        $decPart = isset($parts[1]) ? substr(str_pad($parts[1], 2, '0'), 0, 2) : '00';

        // Validasi harus bernilai lebih besar dari 0 secara murni perbandingan string desimal
        if ($intPart === '0' && $decPart === '00') {
            throw new InvalidArgumentException("Kuantitas pesanan harus lebih besar dari 0.");
        }

        // Batas maksimum DECIMAL(12,2) = 10 digit bilangan bulat + 2 digit desimal (9.999.999.999,99)
        // Panjang bagian integer tidak boleh melebihi 10 digit
        if (strlen($intPart) > 10) {
            throw new InvalidArgumentException("Kuantitas pesanan ('{$strVal}') melebihi batas maksimum basis data DECIMAL(12,2) (9.999.999.999,99).");
        }

        return $intPart . '.' . $decPart;
    }

    /**
     * Memvalidasi batas nilai uang terhadap kapasitas kolom basis data
     * - Menolak nilai bertipe float untuk mencegah risiko presisi hilang sebelum validasi
     * - Menolak nilai negatif dan non-numerik tanpa konversi float
     * - Rentang valid default: 0.00 sampai 9.999.999.999.999,99 (DECIMAL(15,2), maks 13 digit sebelum koma)
     * - Mendukung DECIMAL(14,2) (maks 12 digit sebelum koma) untuk harga satuan
     * - Menolak nilai dengan lebih dari 2 angka desimal
     * - Mengembalikan nilai dalam format string desimal 2 angka di belakang koma ('X.YY')
     *
     * @param mixed $amount
     * @param string $fieldName
     * @param int $maxIntegerDigits Batas maksimum digit sebelum koma (13 untuk DECIMAL(15,2), 12 untuk DECIMAL(14,2))
     * @return string Format string desimal 2 angka di belakang koma ('X.YY')
     * @throws InvalidArgumentException
     */
    public function validateAmountLimit($amount, $fieldName = 'Nilai', $maxIntegerDigits = 13)
    {
        if ($amount === null || $amount === '') {
            throw new InvalidArgumentException("{$fieldName} tidak boleh kosong.");
        }

        // 1. Tolak input bertipe float untuk mencegah risiko hilangnya presisi angka sebelum validasi
        if (is_float($amount)) {
            throw new InvalidArgumentException("{$fieldName} tidak boleh bertipe float untuk mencegah hilangnya presisi angka. Gunakan format string desimal atau integer.");
        }

        // 2. Validasi tipe numerik: string atau integer
        if (!is_string($amount) && !is_int($amount)) {
            throw new InvalidArgumentException("{$fieldName} harus berupa nilai numerik valid.");
        }

        $strVal = trim((string) $amount);

        // 3. Tolak secara tegas tanda minus (nilai negatif) secara murni perbandingan string tanpa konversi float
        if (strpos($strVal, '-') !== false) {
            throw new InvalidArgumentException("{$fieldName} tidak boleh bernilai negatif: '{$strVal}'.");
        }

        // 4. Format angka desimal non-negatif dengan regex
        if (!preg_match('/^\d+(\.\d+)?$/', $strVal)) {
            throw new InvalidArgumentException("Format {$fieldName} tidak valid: '{$strVal}'. Hanya angka positif atau nol yang diperbolehkan.");
        }

        // 5. Validasi presisi desimal: maksimal 2 digit di belakang koma
        if (strpos($strVal, '.') !== false) {
            $decimals = strlen(substr(strrchr($strVal, "."), 1));
            if ($decimals > 2) {
                throw new InvalidArgumentException("{$fieldName} tidak boleh memiliki lebih dari 2 angka desimal (diterima: '{$strVal}').");
            }
        }

        $parts = explode('.', $strVal);
        $intPart = ltrim($parts[0], '0') ?: '0';
        $decPart = isset($parts[1]) ? substr(str_pad($parts[1], 2, '0'), 0, 2) : '00';

        // 6. Batas kapasitas kolom basis data:
        // DECIMAL(14,2) = maksimal 12 digit bulat + 2 desimal (purchase_order_details.harga_satuan)
        // DECIMAL(15,2) = maksimal 13 digit bulat + 2 desimal (subtotal & total_nilai)
        $decimalSpec = ($maxIntegerDigits === 12) ? 'DECIMAL(14,2)' : 'DECIMAL(15,2)';
        if (strlen($intPart) > $maxIntegerDigits) {
            throw new InvalidArgumentException("{$fieldName} ('{$strVal}') melampaui batas maksimum kolom basis data {$decimalSpec}.");
        }

        return $intPart . '.' . $decPart;
    }

    /**
     * Memvalidasi harga satuan material sesuai spesifikasi kolom DECIMAL(14,2)
     * Maksimal 12 digit bilangan bulat sebelum koma dan 2 digit desimal.
     *
     * @param mixed $price
     * @param string $fieldName
     * @return string Format 'X.YY'
     * @throws InvalidArgumentException
     */
    public function validateUnitPrice($price, $fieldName = 'Harga satuan')
    {
        return $this->validateAmountLimit($price, $fieldName, 12);
    }

    /**
     * Memvalidasi standar lead time hari
     * Wajib berupa bilangan bulat positif > 0 dan tidak melebihi batas maksimum MySQL signed INT (2.147.483.647)
     * Menolak kosong, nol, negatif, desimal, dan teks non-numerik.
     *
     * @param mixed $leadTime
     * @param string $fieldName
     * @return int
     * @throws InvalidArgumentException
     */
    public function validateLeadTime($leadTime, $fieldName = 'Lead time')
    {
        if ($leadTime === null || $leadTime === '') {
            throw new InvalidArgumentException("{$fieldName} tidak boleh kosong.");
        }

        $strVal = trim((string) $leadTime);

        // Harus bilangan bulat murni tanpa titik desimal atau tanda minus
        if (!ctype_digit($strVal) || $strVal === '0' || ltrim($strVal, '0') === '') {
            throw new InvalidArgumentException("{$fieldName} harus berupa bilangan bulat positif > 0 (diterima: '{$strVal}').");
        }

        // Hapus leading zero untuk evaluasi numerik string
        $strClean = ltrim($strVal, '0');

        // Batas maksimum MySQL signed INT = 2.147.483.647 (panjang 10 digit)
        $maxSignedIntStr = '2147483647';
        if (strlen($strClean) > 10 || (strlen($strClean) === 10 && strcmp($strClean, $maxSignedIntStr) > 0)) {
            throw new InvalidArgumentException("{$fieldName} ('{$strVal}') melampaui batas maksimum kolom basis data MySQL INT (2.147.483.647).");
        }

        return (int) $strClean;
    }

    /**
     * Helper privat: Perkalian integer string panjang (algoritma grade-school)
     * Bebas overflow 64-bit integer dan floating point drift
     */
    private static function stringMultiplyInt($num1, $num2)
    {
        $num1 = ltrim((string) $num1, '0') ?: '0';
        $num2 = ltrim((string) $num2, '0') ?: '0';
        if ($num1 === '0' || $num2 === '0')
            return '0';

        $l1 = strlen($num1);
        $l2 = strlen($num2);
        $res = array_fill(0, $l1 + $l2, 0);

        for ($i = $l1 - 1; $i >= 0; $i--) {
            for ($j = $l2 - 1; $j >= 0; $j--) {
                $mul = (int) $num1[$i] * (int) $num2[$j];
                $p2 = $i + $j + 1;
                $p1 = $i + $j;
                $sum = $mul + $res[$p2];
                $res[$p2] = $sum % 10;
                $res[$p1] += intdiv($sum, 10);
            }
        }
        return ltrim(implode('', $res), '0') ?: '0';
    }

    /**
     * Helper privat: Penjumlahan integer string panjang
     */
    private static function stringAddInt($num1, $num2)
    {
        $num1 = ltrim((string) $num1, '0') ?: '0';
        $num2 = ltrim((string) $num2, '0') ?: '0';
        $max = max(strlen($num1), strlen($num2));
        $num1 = str_pad($num1, $max, '0', STR_PAD_LEFT);
        $num2 = str_pad($num2, $max, '0', STR_PAD_LEFT);
        $carry = 0;
        $res = '';

        for ($i = $max - 1; $i >= 0; $i--) {
            $sum = (int) $num1[$i] + (int) $num2[$i] + $carry;
            $res = ($sum % 10) . $res;
            $carry = intdiv($sum, 10);
        }
        if ($carry > 0)
            $res = $carry . $res;
        return $res;
    }

    /**
     * Mengonversi string nilai desimal menjadi string sen bulat (misal '45.75' -> '4575')
     */
    private static function toCentsString($str)
    {
        $parts = explode('.', trim((string) $str));
        $int = ltrim($parts[0], '0') ?: '0';
        $dec = isset($parts[1]) ? substr(str_pad($parts[1], 2, '0'), 0, 2) : '00';
        return ($int === '0' && $dec === '00') ? '0' : ($int . $dec);
    }

    /**
     * Mengonversi string sen bulat kembali ke string desimal 2 angka di belakang koma (misal '4575' -> '45.75')
     */
    private static function fromCentsString($cents)
    {
        $cents = ltrim((string) $cents, '0') ?: '0';
        $padded = str_pad($cents, 3, '0', STR_PAD_LEFT);
        return substr($padded, 0, -2) . '.' . substr($padded, -2);
    }

    /**
     * Perkalian presisi dua angka desimal tanpa float untuk kuantitas x harga
     * Melakukan validasi mandiri terhadap kuantitas dan harga sebelum kalkulasi
     * Mengembalikan string desimal presisi (misal '1303875.00')
     * Bebas dari ketergantungan wajib BCMath dan bebas integer/float overflow.
     *
     * @param mixed $qty
     * @param mixed $price
     * @return string Format 'X.YY'
     * @throws InvalidArgumentException
     */
    public function multiplyMoney($qty, $price)
    {
        // Validasi mandiri input kuantitas (DECIMAL(12,2)) dan harga satuan (DECIMAL(14,2))
        $validQty = $this->validateQuantity($qty);
        $validPrice = $this->validateUnitPrice($price, 'harga satuan');

        $qCents = self::toCentsString($validQty);
        $pCents = self::toCentsString($validPrice);

        // Perkalian integer string (menghasilkan angka dengan 4 desimal)
        $prod = self::stringMultiplyInt($qCents, $pCents);

        // Pembulatan konsisten half-up ke 2 desimal: tambah 50 lalu buang 2 digit terakhir
        $added = self::stringAddInt($prod, '50');
        $cents = strlen($added) > 2 ? substr($added, 0, -2) : '0';

        $subtotal = self::fromCentsString($cents);

        // Validasi batas kapasitas kolom subtotal DECIMAL(15,2) (maksimal 13 digit sebelum koma)
        return $this->validateAmountLimit($subtotal, 'subtotal', 13);
    }

    /**
     * Penjumlahan presisi dua angka desimal tanpa float untuk akumulasi total nilai uang
     * Melakukan validasi mandiri terhadap kedua nominal sebelum penjumlahan
     * Mengembalikan string desimal presisi (misal '1303875.00')
     *
     * @param mixed $amount1
     * @param mixed $amount2
     * @return string Format 'X.YY'
     * @throws InvalidArgumentException
     */
    public function addMoney($amount1, $amount2)
    {
        // Validasi mandiri kedua nominal berkapasitas DECIMAL(15,2)
        $validA1 = $this->validateAmountLimit($amount1, 'nominal 1', 13);
        $validA2 = $this->validateAmountLimit($amount2, 'nominal 2', 13);

        $c1 = self::toCentsString($validA1);
        $c2 = self::toCentsString($validA2);

        $sum = self::fromCentsString(self::stringAddInt($c1, $c2));

        // Validasi batas kapasitas kolom akumulasi DECIMAL(15,2) (maksimal 13 digit sebelum koma)
        return $this->validateAmountLimit($sum, 'total akumulasi', 13);
    }

    /**
     * Memverifikasi keabsahan vendor di basis data
     * ATURAN BISNIS KETAT: Hanya vendor dengan status_verifikasi = 'Terverifikasi' yang boleh dibuatkan PO.
     * Vendor berstatus 'Audit Pending' dan 'Blacklist' ditolak tegas.
     *
     * @param int $vendorId
     * @param bool $lockInShareMode
     * @return array Data vendor
     * @throws RuntimeException
     */
    public function verifyVendorEligibility($vendorId, $lockInShareMode = false)
    {
        $sql = "SELECT `id`, `kode_vendor`, `nama_vendor`, `status_verifikasi` 
                FROM `vendors` 
                WHERE `id` = :id 
                LIMIT 1";

        if ($lockInShareMode && $this->db->inTransaction()) {
            $sql .= " LOCK IN SHARE MODE";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int) $vendorId]);
        $vendor = $stmt->fetch();

        if (!$vendor) {
            throw new RuntimeException("Vendor dengan ID {$vendorId} tidak ditemukan di sistem.");
        }

        if ($vendor['status_verifikasi'] !== 'Terverifikasi') {
            throw new RuntimeException("Vendor '{$vendor['nama_vendor']}' berstatus '{$vendor['status_verifikasi']}'. Pembuatan PO hanya diperbolehkan untuk vendor yang berstatus 'Terverifikasi'.");
        }

        return $vendor;
    }

    /**
     * Memverifikasi keberadaan akun user petugas pengadaan dan memvalidasi role
     * ATURAN BISNIS: Hanya akun dengan role 'Admin' dan 'Purchasing' yang berwenang membuat PO.
     * Role lain (misalnya 'QC_Auditor') ditolak secara tegas.
     *
     * @param int $userId
     * @param bool $lockInShareMode
     * @return array Data user
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function verifyUserExistence($userId, $lockInShareMode = false)
    {
        if (empty($userId) || (int) $userId <= 0) {
            throw new InvalidArgumentException("User ID petugas pengadaan tidak boleh kosong.");
        }

        $sql = "SELECT `id`, `username`, `nama_lengkap`, `role` 
                FROM `users` 
                WHERE `id` = :id 
                LIMIT 1";

        if ($lockInShareMode && $this->db->inTransaction()) {
            $sql .= " LOCK IN SHARE MODE";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int) $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new InvalidArgumentException("User ID petugas '{$userId}' tidak ditemukan di sistem autentikasi.");
        }

        // Pemeriksaan Otorisasi Role Petugas
        $allowedRoles = ['Admin', 'Purchasing'];
        if (!in_array($user['role'], $allowedRoles, true)) {
            throw new RuntimeException("User '{$user['nama_lengkap']}' memiliki role '{$user['role']}'. Hanya role 'Admin' dan 'Purchasing' yang berwenang membuat Purchase Order.");
        }

        return $user;
    }

    /**
     * Mengambil seluruh data Purchase Order lengkap dengan nama vendor dan user pembuat
     *
     * @return array
     */
    public function getAll()
    {
        $sql = "SELECT 
                    po.`id`,
                    po.`no_po`,
                    po.`vendor_id`,
                    po.`user_id`,
                    po.`tanggal_po`,
                    po.`lead_time_hari`,
                    po.`estimasi_kedatangan`,
                    po.`total_nilai`,
                    po.`status_po`,
                    po.`catatan_pengiriman`,
                    po.`created_at`,
                    v.`kode_vendor`,
                    v.`nama_vendor`,
                    v.`jenis_akreditasi`,
                    v.`status_verifikasi`,
                    u.`username`,
                    u.`nama_lengkap` AS `nama_user`,
                    u.`role` AS `role_user`
                FROM `purchase_orders` po
                INNER JOIN `vendors` v ON po.`vendor_id` = v.`id`
                INNER JOIN `users` u ON po.`user_id` = u.`id`
                ORDER BY po.`no_po` DESC, po.`id` DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil detail satu Purchase Order berdasarkan ID primary key
     *
     * @param int $id
     * @return array|false
     */
    public function getById($id)
    {
        $sql = "SELECT 
                    po.`id`,
                    po.`no_po`,
                    po.`vendor_id`,
                    po.`user_id`,
                    po.`tanggal_po`,
                    po.`lead_time_hari`,
                    po.`estimasi_kedatangan`,
                    po.`total_nilai`,
                    po.`status_po`,
                    po.`catatan_pengiriman`,
                    po.`created_at`,
                    v.`kode_vendor`,
                    v.`nama_vendor`,
                    v.`spesialisasi_logam`,
                    v.`kontak_person`,
                    v.`email` AS `vendor_email`,
                    v.`telepon` AS `vendor_telepon`,
                    v.`alamat` AS `vendor_alamat`,
                    v.`jenis_akreditasi`,
                    v.`status_verifikasi`,
                    u.`username`,
                    u.`nama_lengkap` AS `nama_user`,
                    u.`role` AS `role_user`
                FROM `purchase_orders` po
                INNER JOIN `vendors` v ON po.`vendor_id` = v.`id`
                INNER JOIN `users` u ON po.`user_id` = u.`id`
                WHERE po.`id` = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int) $id]);
        return $stmt->fetch();
    }

    /**
     * Mengambil detail satu Purchase Order berdasarkan nomor PO unik
     *
     * @param string $noPo
     * @return array|false
     */
    public function getByNoPo($noPo)
    {
        $sql = "SELECT po.*, v.`nama_vendor`, u.`nama_lengkap` AS `nama_user`
                FROM `purchase_orders` po
                INNER JOIN `vendors` v ON po.`vendor_id` = v.`id`
                INNER JOIN `users` u ON po.`user_id` = u.`id`
                WHERE po.`no_po` = :no_po
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':no_po' => trim($noPo)]);
        return $stmt->fetch();
    }

    /**
     * Menghasilkan nomor PO unik baru dengan format PO-YYYYMM-XXXX
     * Contoh: PO-202610-0001
     *
     * VALIDASI TANGGAL KETAT & PERBAIKAN URUTAN:
     * - Jika $tanggalPo diberikan, divalidasi ketat dengan validateDateStrict().
     * - Tanggal tidak valid langsung ditolak (throw InvalidArgumentException).
     * - Jika $tanggalPo tidak diberikan, otomatis menggunakan tanggal saat ini.
     * - Penentuan nomor terakhir MURNI diurutkan berdasarkan `no_po` DESC (bukan berdasarkan `id`).
     * - Mendukung penguncian baris transaksi 'FOR UPDATE' saat proses pembuatan PO.
     *
     * @param string|null $tanggalPo Tanggal acuan (format Y-m-d)
     * @param bool $forUpdate Apakah menggunakan penguncian baris transaksi (pessimistic lock)
     * @return string
     * @throws InvalidArgumentException
     */
    public function generatePoNumber($tanggalPo = null, $forUpdate = false)
    {
        if (!empty($tanggalPo)) {
            $this->validateDateStrict($tanggalPo, 'Tanggal PO');
            $time = strtotime($tanggalPo);
        } else {
            $time = time();
        }

        $prefix = 'PO-' . date('Ym', $time) . '-';

        // Urutkan strictly berdasarkan no_po DESC
        $sql = "SELECT `no_po` 
                FROM `purchase_orders` 
                WHERE `no_po` LIKE :prefix 
                ORDER BY `no_po` DESC 
                LIMIT 1";

        if ($forUpdate && $this->db->inTransaction()) {
            $sql .= " FOR UPDATE";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $lastPo = $stmt->fetchColumn();

        if ($lastPo) {
            $lastSequence = (int) substr($lastPo, strlen($prefix));
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . sprintf('%04d', $nextSequence);
    }

    /**
     * Memeriksa apakah nomor PO sudah ada di basis data
     *
     * @param string $noPo
     * @param int|null $excludeId ID untuk pengecualian saat update
     * @return bool
     */
    public function isNoPoExists($noPo, $excludeId = null)
    {
        if ($excludeId !== null) {
            $sql = "SELECT COUNT(*) FROM `purchase_orders` WHERE `no_po` = :no_po AND `id` != :exclude_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':no_po' => trim($noPo),
                ':exclude_id' => (int) $excludeId
            ]);
        } else {
            $sql = "SELECT COUNT(*) FROM `purchase_orders` WHERE `no_po` = :no_po";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':no_po' => trim($noPo)]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Memeriksa apakah PDOException merupakan error duplikasi unique index tertentu (Error 1062)
     * Mencocokkan secara spesifik terhadap nama unique index eksplisit (bukan sekadar substring generik)
     *
     * @param PDOException $e
     * @param string $indexName Nama unique index eksplisit (default: self::UNIQUE_INDEX_NO_PO)
     * @return bool
     */
    public function isDuplicateIndexError(PDOException $e, $indexName = self::UNIQUE_INDEX_NO_PO)
    {
        $errCode = $e->errorInfo[1] ?? 0;
        if ($errCode !== 1062) {
            return false;
        }

        $errMsg = $e->getMessage();
        // Cocokkan error duplicate-key secara spesifik berdasarkan nama unique index eksplisit
        // Format MySQL: key 'no_po' atau key 'purchase_orders.no_po' (dengan kutip tunggal, ganda, backtick, atau tanpa kutip)
        $pattern = "/\bkey\s+['`\"]?(?:[a-zA-Z0-9_]+\.)?" . preg_quote($indexName, '/') . "['`\"]?/i";
        return (bool) preg_match($pattern, $errMsg);
    }

    /**
     * Menyimpan baris data header Purchase Order baru
     *
     * ATURAN KETAT STATUS AWAL, LEAD TIME, & VALIDASI NOMINAL:
     * - Semua PO baru WAJIB dibuat dengan status 'Draft'.
     * - Jika pemanggil mengirimkan status selain 'Draft', input tersebut ditolak secara tegas.
     * - Lead time hari wajib bilangan bulat positif > 0 dan <= 2.147.483.647 (menolak kosong, nol, negatif, desimal, teks, dan overflow).
     * - Nominal total_nilai divalidasi ketat (tidak boleh negatif atau invalid).
     *
     * @param array $data Data header PO
     * @return int ID primary key yang baru dibuat
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createHeader(array $data)
    {
        if (!isset($data['no_po'], $data['vendor_id'], $data['user_id'], $data['tanggal_po'], $data['lead_time_hari'], $data['estimasi_kedatangan'])) {
            throw new InvalidArgumentException("Data header PO tidak lengkap.");
        }

        // 1. Validasi Status Awal: Wajib 'Draft'
        if (isset($data['status_po']) && $data['status_po'] !== 'Draft') {
            throw new InvalidArgumentException("Status awal dokumen PO baru wajib 'Draft'. Status '{$data['status_po']}' ditolak saat inisialisasi PO baru.");
        }
        $statusPo = 'Draft';

        // 2. Validasi Tanggal PO dan Estimasi Kedatangan
        $this->validateDateStrict($data['tanggal_po'], 'Tanggal PO');
        $this->validateDateStrict($data['estimasi_kedatangan'], 'Estimasi Kedatangan');

        // 3. Validasi Akun User (Petugas) & Role
        $this->verifyUserExistence((int) $data['user_id']);

        // 4. Validasi Kelayakan Status Rekanan Vendor
        $this->verifyVendorEligibility((int) $data['vendor_id']);

        // 5. Validasi Ketat Lead Time Hari (wajib integer positif > 0 dan <= 2.147.483.647, tolak 0, -1, 1.5, teks)
        $leadTimeHari = $this->validateLeadTime($data['lead_time_hari'], 'Lead time hari');

        // 6. Validasi Nominal Total Nilai (Menolak negatif, NAN, INF, dan > batas DECIMAL(15,2))
        $totalNilai = isset($data['total_nilai'])
            ? $this->validateAmountLimit($data['total_nilai'], 'total nilai PO')
            : '0.00';

        $sql = "INSERT INTO `purchase_orders` (
                    `no_po`,
                    `vendor_id`,
                    `user_id`,
                    `tanggal_po`,
                    `lead_time_hari`,
                    `estimasi_kedatangan`,
                    `total_nilai`,
                    `status_po`,
                    `catatan_pengiriman`,
                    `created_at`
                ) VALUES (
                    :no_po,
                    :vendor_id,
                    :user_id,
                    :tanggal_po,
                    :lead_time_hari,
                    :estimasi_kedatangan,
                    :total_nilai,
                    :status_po,
                    :catatan_pengiriman,
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':no_po' => trim($data['no_po']),
            ':vendor_id' => (int) $data['vendor_id'],
            ':user_id' => (int) $data['user_id'],
            ':tanggal_po' => $data['tanggal_po'],
            ':lead_time_hari' => $leadTimeHari,
            ':estimasi_kedatangan' => $data['estimasi_kedatangan'],
            ':total_nilai' => $totalNilai,
            ':status_po' => $statusPo,
            ':catatan_pengiriman' => !empty($data['catatan_pengiriman']) ? trim($data['catatan_pengiriman']) : null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Memperbarui total nilai uang pada header PO dengan validasi ketat
     *
     * @param int $id
     * @param mixed $totalNilai
     * @return bool
     * @throws InvalidArgumentException
     */
    public function updateTotalNilai($id, $totalNilai)
    {
        $validatedTotal = $this->validateAmountLimit($totalNilai, 'total nilai PO');

        $sql = "UPDATE `purchase_orders` 
                SET `total_nilai` = :total_nilai 
                WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':total_nilai' => $validatedTotal,
            ':id' => (int) $id
        ]);
    }

    /**
     * Memperbarui status Purchase Order dengan proteksi konkurensi (Row Locking) dan matriks transisi ketat
     *
     * ATURAN MATRIKS TRANSISI STATUS BISNIS PENGADAAN:
     * 1. 'Draft'            -> 'Disetujui', 'Dibatalkan'
     * 2. 'Disetujui'        -> 'Dalam Pengiriman', 'Dibatalkan'
     * 3. 'Dalam Pengiriman' -> 'Selesai', 'Dibatalkan'
     * 4. 'Selesai'          -> FINAL (Terkunci permanen, tidak boleh diubah kembali ke status aktif)
     * 5. 'Dibatalkan'       -> FINAL (Terkunci permanen, tidak boleh diubah kembali ke status aktif)
     *
     * @param int $id
     * @param string $statusPo
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function updateStatus($id, $statusPo)
    {
        $validStatuses = ['Draft', 'Disetujui', 'Dalam Pengiriman', 'Sebagian Diterima', 'Selesai', 'Dibatalkan'];
        if (!in_array($statusPo, $validStatuses, true)) {
            throw new InvalidArgumentException("Status PO tidak valid: {$statusPo}");
        }

        $isLocalTx = false;

        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $isLocalTx = true;
            }

            // Kunci baris PO secara eksklusif dengan FOR UPDATE
            $sqlLock = "SELECT `id`, `status_po` 
                        FROM `purchase_orders` 
                        WHERE `id` = :id 
                        FOR UPDATE";

            $stmtLock = $this->db->prepare($sqlLock);
            $stmtLock->execute([':id' => (int) $id]);
            $current = $stmtLock->fetch();

            if (!$current) {
                throw new RuntimeException("Purchase Order dengan ID {$id} tidak ditemukan.");
            }

            $currStatus = $current['status_po'];

            // Jika status tujuan identik dengan status saat ini, tidak ada operasi
            if ($currStatus === $statusPo) {
                if ($isLocalTx && $this->db->inTransaction()) {
                    $this->db->commit();
                }
                return true;
            }

            // Status Selesai dan Dibatalkan adalah status terminal / final
            if (in_array($currStatus, ['Selesai', 'Dibatalkan'], true)) {
                throw new RuntimeException("PO berstatus '{$currStatus}' bersifat final dan tidak dapat diubah kembali ke status aktif ('{$statusPo}').");
            }

            // Matriks alur transisi status yang diperbolehkan
            $allowedTransitions = [
                'Draft'             => ['Disetujui', 'Dibatalkan'],
                'Disetujui'         => ['Dalam Pengiriman', 'Dibatalkan'],
                'Dalam Pengiriman'  => ['Sebagian Diterima', 'Selesai', 'Dibatalkan'],
                'Sebagian Diterima' => ['Sebagian Diterima', 'Selesai', 'Dibatalkan']
            ];

            if (!isset($allowedTransitions[$currStatus]) || !in_array($statusPo, $allowedTransitions[$currStatus], true)) {
                throw new RuntimeException("Transisi status tidak sah: dokumen PO tidak dapat berpindah dari '{$currStatus}' ke '{$statusPo}'.");
            }

            // Jika ingin mengubah ke 'Selesai', pastikan seluruh item fisik telah diterima di gudang
            if ($statusPo === 'Selesai') {
                $stmtUnreceived = $this->db->prepare("SELECT SUM(`jumlah_pesanan` - `jumlah_diterima`) AS unreceived 
                                                      FROM `purchase_order_details` 
                                                      WHERE `po_id` = :po_id");
                $stmtUnreceived->execute([':po_id' => (int)$id]);
                $unreceived = (float)($stmtUnreceived->fetchColumn() ?? 0);
                if ($unreceived > 0.0001) {
                    throw new RuntimeException("Status PO tidak dapat diselesaikan karena masih terdapat kuantitas pesanan yang belum diterima fisik oleh gudang (" . number_format($unreceived, 2) . "). Silakan proses penerimaan barang terlebih dahulu melalui formulir penerimaan gudang.");
                }
            }

            $sql = "UPDATE `purchase_orders` 
                    SET `status_po` = :status_po 
                    WHERE `id` = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':status_po' => $statusPo,
                ':id' => (int) $id
            ]);

            if ($isLocalTx && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return true;

        } catch (Exception $e) {
            if ($isLocalTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Membatalkan dokumen Purchase Order dengan penguncian baris (FOR UPDATE)
     *
     * @param int $id
     * @param string|null $alasan
     * @return bool
     * @throws RuntimeException
     */
    public function cancel($id, $alasan = null)
    {
        $isLocalTx = false;

        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $isLocalTx = true;
            }

            $sqlLock = "SELECT `id`, `status_po`, `catatan_pengiriman` 
                        FROM `purchase_orders` 
                        WHERE `id` = :id 
                        FOR UPDATE";

            $stmtLock = $this->db->prepare($sqlLock);
            $stmtLock->execute([':id' => (int) $id]);
            $current = $stmtLock->fetch();

            if (!$current) {
                throw new RuntimeException("Purchase Order dengan ID {$id} tidak ditemukan.");
            }

            $currStatus = $current['status_po'];

            if ($currStatus === 'Dibatalkan') {
                if ($isLocalTx && $this->db->inTransaction()) {
                    $this->db->commit();
                }
                return true;
            }

            if ($currStatus === 'Selesai') {
                throw new RuntimeException("PO berstatus 'Selesai' bersifat final dan tidak dapat dibatalkan.");
            }

            $catatanBaru = $current['catatan_pengiriman'];
            if (!empty($alasan)) {
                $catatanBaru = trim(($catatanBaru ? $catatanBaru . " | " : "") . "Pembatalan: " . trim($alasan));
            }

            $sql = "UPDATE `purchase_orders` 
                    SET `status_po` = 'Dibatalkan', 
                        `catatan_pengiriman` = :catatan 
                    WHERE `id` = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':catatan' => $catatanBaru,
                ':id' => (int) $id
            ]);

            if ($isLocalTx && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return true;

        } catch (Exception $e) {
            if ($isLocalTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * TRANSAKSI ATOMIK UTAMA (ACID):
     * Menyimpan seluruh berkas PO (Header + Rincian Detail Multi-Item) secara utuh.
     * Menggunakan beginTransaction(), commit(), dan rollBack().
     *
     * Dilengkapi pemulihan otomatis jika terjadi Deadlock InnoDB (MySQL error 1213)
     * atau duplicate entry no_po (MySQL error 1062 pada key no_po).
     * Error duplicate key lain (seperti uq_vendor_material) langsung dilempar sebagai error.
     *
     * @param array $headerData ['vendor_id', 'user_id', 'tanggal_po', 'catatan_pengiriman', ?'no_po']
     * @param array $items Array of ['vendor_material_id' => int, 'jumlah_pesanan' => mixed]
     * @return array Ringkasan PO berhasil ['po_id' => int, 'no_po' => string, 'total_nilai' => string]
     * @throws Exception
     */
    public function createWithDetails(array $headerData, array $items)
    {
        // Method ini wajib mengelola transaksinya sendiri secara penuh
        if ($this->db->inTransaction()) {
            throw new RuntimeException("createWithDetails() harus mengelola transaksinya sendiri. Ditemukan transaksi basis data PDO yang sudah aktif.");
        }

        if (empty($items) || !is_array($items)) {
            throw new InvalidArgumentException("Daftar item material Purchase Order tidak boleh kosong.");
        }

        if (empty($headerData['vendor_id']) || empty($headerData['user_id']) || empty($headerData['tanggal_po'])) {
            throw new InvalidArgumentException("Informasi header PO (Vendor, Petugas, Tanggal PO) wajib diisi lengkap.");
        }

        // Penolakan tegas status awal non-Draft
        if (isset($headerData['status_po']) && $headerData['status_po'] !== 'Draft') {
            throw new InvalidArgumentException("Status awal dokumen PO baru wajib 'Draft'. Status '{$headerData['status_po']}' ditolak saat inisialisasi PO baru.");
        }

        $vendorId = (int) $headerData['vendor_id'];
        $userId = (int) $headerData['user_id'];
        $tanggalPo = trim($headerData['tanggal_po']);

        // 1. Validasi Awal Format Tanggal PO
        $this->validateDateStrict($tanggalPo, 'Tanggal PO');

        // Loop penanganan transaksi untuk ketahanan terhadap deadlock multi-proses paralel (hingga 3 percobaan)
        $maxTxAttempts = 3;
        $txAttempt = 0;

        while ($txAttempt < $maxTxAttempts) {
            $txAttempt++;
            $txStarted = false;

            try {
                // 2. Mulai Transaksi Basis Data Atomik (ACID)
                $this->db->beginTransaction();
                $txStarted = true;
                // A. Verifikasi Kelayakan Rekanan Vendor Terverifikasi dengan Lock In Share Mode
                $vendor = $this->verifyVendorEligibility($vendorId, true);

                // B. Verifikasi Keberadaan Petugas Pengadaan & Otorisasi Role (Admin/Purchasing)
                $user = $this->verifyUserExistence($userId, true);

                // C. Validasi Detail Item dan Pengambilan Harga Resmi Langsung dari Database
                $detailItems = [];
                $maxLeadTime = 0;
                $totalNilai = '0.00';

                // Prepared statement untuk membaca katalog material terkunci
                $sqlVm = "SELECT 
                            vm.`id` AS `vendor_material_id`,
                            vm.`vendor_id`,
                            vm.`material_id`,
                            vm.`harga_penawaran`,
                            vm.`standar_lead_time`,
                            vm.`status_pasokan`,
                            m.`nama_material`
                          FROM `vendor_materials` vm
                          INNER JOIN `materials` m ON vm.`material_id` = m.`id`
                          WHERE vm.`id` = :vmid 
                            AND vm.`vendor_id` = :vid 
                          LIMIT 1 
                          LOCK IN SHARE MODE";

                $stmtVm = $this->db->prepare($sqlVm);

                foreach ($items as $idx => $item) {
                    $vmId = (int) ($item['vendor_material_id'] ?? 0);
                    $rawQty = $item['jumlah_pesanan'] ?? null;

                    if ($vmId <= 0) {
                        throw new InvalidArgumentException("Item ke-" . ($idx + 1) . " tidak memiliki referensi material vendor yang valid.");
                    }

                    // Validasi Presisi Kuantitas Desimal dan Batas DECIMAL(12,2) murni string tanpa float
                    $jumlah = $this->validateQuantity($rawQty);

                    // Baca katalog vendor material aktual dari DB
                    $stmtVm->execute([
                        ':vmid' => $vmId,
                        ':vid' => $vendorId
                    ]);
                    $vmData = $stmtVm->fetch();

                    if (!$vmData) {
                        throw new RuntimeException("Material penawaran dengan ID {$vmId} tidak terdaftar pada vendor '{$vendor['nama_vendor']}'.");
                    }

                    if ($vmData['status_pasokan'] !== 'Tersedia') {
                        throw new RuntimeException("Pasokan material '{$vmData['nama_material']}' saat ini berstatus '{$vmData['status_pasokan']}'. Pemesanan ditolak.");
                    }

                    // Validasi ketat harga penawaran dari database sesuai kolom DECIMAL(14,2) (maksimal 12 digit sebelum koma)
                    $hargaSatuanRaw = $vmData['harga_penawaran'];
                    $hargaSatuan = $this->validateUnitPrice($hargaSatuanRaw, "harga penawaran material '{$vmData['nama_material']}'");

                    // Validasi ketat standar lead time dari database: wajib integer positif > 0 dan <= 2.147.483.647 (tanpa fallback)
                    $leadTime = $this->validateLeadTime($vmData['standar_lead_time'], "Standar lead time material '{$vmData['nama_material']}'");
                    if ($leadTime > $maxLeadTime) {
                        $maxLeadTime = $leadTime;
                    }

                    // Kalkulasi subtotal menggunakan perkalian presisi murni tanpa float (dengan validasi mandiri)
                    $subtotal = $this->multiplyMoney($jumlah, $hargaSatuan);

                    // Akumulasi total nilai menggunakan penjumlahan presisi murni tanpa float (dengan validasi mandiri)
                    $totalNilai = $this->addMoney($totalNilai, $subtotal);

                    $detailItems[] = [
                        'vendor_material_id' => $vmId,
                        'material_id'        => (int)$vmData['material_id'],
                        'jumlah_pesanan'     => $jumlah, // string desimal murni
                        'harga_satuan'       => $hargaSatuan, // string desimal murni
                        'subtotal'           => $subtotal // string desimal murni
                    ];
                }

                // Hitung estimasi kedatangan logistik dari lead time terbesar yang valid
                $estimasiKedatangan = date('Y-m-d', strtotime("{$tanggalPo} + {$maxLeadTime} days"));
                $this->validateDateStrict($estimasiKedatangan, 'Estimasi Kedatangan');

                // D. Pembuatan Nomor PO dengan Mekanisme Anti-Konkurensi
                $noPo = !empty($headerData['no_po'])
                    ? trim($headerData['no_po'])
                    : $this->generatePoNumber($tanggalPo, true);

                $headerInsertData = [
                    'no_po' => $noPo,
                    'vendor_id' => $vendorId,
                    'user_id' => $userId,
                    'tanggal_po' => $tanggalPo,
                    'lead_time_hari' => $maxLeadTime,
                    'estimasi_kedatangan' => $estimasiKedatangan,
                    'total_nilai' => '0.00',
                    'status_po' => 'Draft', // Terkunci wajib Draft
                    'catatan_pengiriman' => $headerData['catatan_pengiriman'] ?? null
                ];

                $poId = $this->createHeader($headerInsertData);

                // E. Simpan Rincian Detail dengan Vendor ID Konsisten (Composite Foreign Key)
                $podModel = new PurchaseOrderDetail($this->db);

                foreach ($detailItems as $detail) {
                    $podModel->create([
                        'po_id'              => $poId,
                        'vendor_id'          => $vendorId, // Konsisten dengan uq_po_vendor & uq_vm_vendor
                        'vendor_material_id' => $detail['vendor_material_id'],
                        'material_id'        => $detail['material_id'],
                        'jumlah_pesanan'     => $detail['jumlah_pesanan'],
                        'jumlah_diterima'    => '0.00',
                        'harga_satuan'       => $detail['harga_satuan'],
                        'subtotal'           => $detail['subtotal']
                    ]);
                }

                // F. Perbarui Akumulasi Total Nilai pada Header PO
                $this->updateTotalNilai($poId, $totalNilai);

                // G. Komit Transaksi Penuh
                $this->db->commit();
                $txStarted = false;

                return [
                    'po_id' => $poId,
                    'no_po' => $noPo,
                    'vendor_id' => $vendorId,
                    'lead_time_hari' => $maxLeadTime,
                    'estimasi_kedatangan' => $estimasiKedatangan,
                    'total_nilai' => $totalNilai
                ];

            } catch (PDOException $e) {
                if ($txStarted && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $txStarted = false;

                $errCode = $e->errorInfo[1] ?? 0;
                $errMsg = $e->getMessage();

                // 1. Kasus Deadlock InnoDB (Error 1213): selalu coba ulang transaksi
                if ($errCode === 1213 && $txAttempt < $maxTxAttempts) {
                    usleep(mt_rand(15000, 50000)); // Jitter 15-50ms sebelum mencoba kembali transaksi
                    continue;
                }

                // 2. Kasus Duplicate Entry (Error 1062):
                // Retry HANYA jika konflik terjadi pada unique index nomor PO yang dibuat otomatis
                $isAutoGenerated = empty($headerData['no_po']);
                if ($this->isDuplicateIndexError($e, self::UNIQUE_INDEX_NO_PO) && $isAutoGenerated && $txAttempt < $maxTxAttempts) {
                    usleep(mt_rand(15000, 50000)); // Jitter 15-50ms sebelum mencoba kembali transaksi
                    continue;
                }

                // Jika duplicate key pada constraint lain atau nomor PO manual, langsung lempar error!
                throw $e;

            } catch (Exception $e) {
                if ($txStarted && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $txStarted = false;
                throw $e;
            }
        }

    }
}
