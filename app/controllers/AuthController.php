<?php
/**
 * ==============================================================================
 * CONTROLLER: AUTENTIKASI PENGGUNA (LOGIN & LOGOUT)
 * ==============================================================================
 * Mengelola proses masuk (login) dengan verifikasi Bcrypt password_verify(),
 * otorisasi sesi peran (Admin, Purchasing, QC_Auditor), dan proses keluar (logout).
 * ==============================================================================
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../../config/database.php';

class AuthController extends Controller {
    private $db;

    public function __construct() {
        Session::start();
        $this->db = Database::connect();
    }

    /**
     * Halaman Login / Proses Verifikasi Kredensial
     */
    public function login() {
        // Jika sudah login, langsung alihkan ke dashboard
        if (!empty($_SESSION['user_id'])) {
            header('Location: /scm/');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!Session::validateCsrfToken($csrfToken)) {
                Session::setFlash('error', 'Token keamanan CSRF tidak valid atau telah kedaluwarsa.');
                header('Location: /scm/login');
                exit;
            }

            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                Session::setFlash('error', 'Username dan password wajib diisi.');
                header('Location: /scm/login');
                exit;
            }

            // Ambil data pengguna berdasarkan username
            $stmt = $this->db->prepare("SELECT `id`, `username`, `password`, `nama_lengkap`, `role` FROM `users` WHERE `username` = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            // Verifikasi kecocokan password menggunakan Bcrypt hash
            if ($user && password_verify($password, $user['password'])) {
                // Regenerasi session ID untuk mencegah session fixation
                session_regenerate_id(true);

                // Buang hash password dari data sesi untuk keamanan
                unset($user['password']);

                $_SESSION['user']    = $user;
                $_SESSION['user_id'] = (int)$user['id'];

                Session::setFlash('success', "Selamat datang, {$user['nama_lengkap']}! Anda masuk sebagai {$user['role']}.");
                
                $redirectUrl = $_SESSION['redirect_after_login'] ?? '/scm/';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirectUrl);
                exit;
            }

            Session::setFlash('error', 'Kombinasi username atau password tidak valid.');
            header('Location: /scm/login');
            exit;
        }

        // Tampilkan halaman login
        $data = [
            'pageTitle'    => 'Login Sistem SCM Otomotif',
            'csrfToken'    => Session::generateCsrfToken(),
            'flashSuccess' => Session::getFlash('success'),
            'flashError'   => Session::getFlash('error')
        ];

        // View login dibuat standalone (tanpa wrapper layout dashboard penuh)
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Proses Keluar (Logout)
     */
    public function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        Session::start();
        Session::setFlash('success', 'Anda telah berhasil keluar dari sistem SCM Otomotif.');
        header('Location: /scm/login');
        exit;
    }
}

