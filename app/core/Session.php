<?php
/**
 * ==============================================================================
 * SESSION & SECURITY HELPER - SISTEM SUPPLY CHAIN MANAGEMENT (SCM)
 * ==============================================================================
 * Mengelola sesi aman, notifikasi flash message, dan proteksi CSRF token.
 * ==============================================================================
 */

class Session {
    /**
     * Memulai sesi jika belum aktif
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Menetapkan pesan kilat (flash message)
     *
     * @param string $key Tipe pesan ('success', 'error', 'info', dll.)
     * @param string $message Isi pesan notifikasi
     */
    public static function setFlash($key, $message) {
        self::start();
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Mengambil dan menghapus pesan kilat
     *
     * @param string $key
     * @return string|null
     */
    public static function getFlash($key) {
        self::start();
        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }

    /**
     * Memeriksa apakah ada pesan kilat
     *
     * @param string $key
     * @return bool
     */
    public static function hasFlash($key) {
        self::start();
        return isset($_SESSION['flash'][$key]);
    }

    /**
     * Menghasilkan atau mengambil token CSRF aktif
     *
     * @return string
     */
    public static function generateCsrfToken() {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Memvalidasi token CSRF dari request form
     *
     * @param string $token Token yang dikirim dari form
     * @return bool
     */
    public static function validateCsrfToken($token) {
        self::start();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

