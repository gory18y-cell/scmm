<?php
/**
 * ==============================================================================
 * SISTEM SUPPLY CHAIN MANAGEMENT (SCM) INDUSTRI MANUFAKTUR OTOMOTIF
 * KELAS KONEKSI DATABASE PDO (STRICT MODE & ANTI SQL INJECTION)
 * ==============================================================================
 */

class Database {
    private static $host    = '127.0.0.1';
    private static $db      = 'db_scm_otomotif';
    private static $user    = 'root';
    private static $pass    = '';
    private static $charset = 'utf8mb4';
    private static $pdo     = null;

    /**
     * Mendapatkan instance koneksi PDO tunggal (Singleton pattern)
     * 
     * @return PDO
     */
    public static function connect() {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
                self::$pdo = new PDO($dsn, self::$user, self::$pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false // Mencegah SQL Injection mutlak
                ]);
            } catch (PDOException $e) {
                die("Koneksi basis data gagal: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}

