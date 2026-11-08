<?php
/**
 * ==============================================================================
 * BASE CONTROLLER - SISTEM SUPPLY CHAIN MANAGEMENT (SCM)
 * ==============================================================================
 * Controller dasar yang mewarisi fungsi render tampilan, pengecekan RBAC,
 * dan manajemen otorisasi pengguna.
 * ==============================================================================
 */

require_once __DIR__ . '/Session.php';

class Controller {
    /**
     * Mengambil informasi akun user yang sedang aktif dari session
     * Konsisten membaca dari $_SESSION['user']
     * TIDAK BOLEH auto-login / bypass ke user di database jika belum login
     *
     * @return array|null
     */
    protected function getAuthUser() {
        Session::start();
        if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        return null;
    }

    /**
     * Memastikan pengguna telah login ke sistem
     * Jika belum login, diarahkan ke form login
     *
     * @param string|null $redirectUrl
     * @return array Data pengguna yang login
     */
    protected function requireAuth($redirectUrl = null) {
        $user = $this->getAuthUser();
        if (!$user) {
            Session::start();
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            }
            Session::setFlash('error', 'Silakan login terlebih dahulu untuk mengakses fitur ini.');
            header('Location: /scm/login');
            exit;
        }
        return $user;
    }

    /**
     * Memastikan pengguna memiliki salah satu role yang diizinkan (RBAC)
     * Jika tidak memiliki izin, ditolak dengan HTTP 403 Forbidden
     *
     * @param array $allowedRoles Daftar role yang diizinkan (contoh: ['Admin', 'Purchasing'])
     * @param string|null $fallbackRedirect Jika request POST dan parameter ini diisi, redirect dengan flash error 403
     * @return array Data pengguna yang lolos otorisasi
     */
    protected function requireRole(array $allowedRoles, $fallbackRedirect = null) {
        $user = $this->requireAuth($fallbackRedirect);
        if (!in_array($user['role'], $allowedRoles, true)) {
            http_response_code(403);
            if ($fallbackRedirect !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
                Session::setFlash('error', "Akses Ditolak (403): Operasi ini membutuhkan role " . implode(' atau ', $allowedRoles) . ". Role akun Anda saat ini ('{$user['role']}') tidak memiliki izin.");
                header('Location: ' . $fallbackRedirect);
                exit;
            }
            // Tampilkan halaman 403 Forbidden resmi
            $this->renderForbidden($allowedRoles, $user['role']);
            exit;
        }
        return $user;
    }

    /**
     * Menampilkan halaman error 403 Forbidden
     *
     * @param array $requiredRoles
     * @param string $currentRole
     */
    protected function renderForbidden(array $requiredRoles = [], $currentRole = 'Tamu') {
        http_response_code(403);
        $data = [
            'pageTitle'     => '403 - Akses Ditolak (Forbidden)',
            'requiredRoles' => $requiredRoles,
            'currentRole'   => $currentRole
        ];
        $file = __DIR__ . '/../views/errors/403.php';
        if (file_exists($file)) {
            require $file;
        } else {
            die("<h1>403 Forbidden</h1><p>Akses ditolak: Anda tidak memiliki wewenang untuk mengakses halaman ini.</p>");
        }
        exit;
    }

    /**
     * Memuat dan merender berkas view serta mengirimkan data dinamis
     *
     * @param string $viewPath Lokasi view relatif terhadap app/views/ (contoh: 'home/index')
     * @param array $data Data asosiatif yang diekstrak menjadi variabel di view
     * @param bool $useLayout Apakah view dibungkus dengan layout global (header, sidebar, footer)
     * @return void
     */
    public function render($viewPath, $data = [], $useLayout = true) {
        // Otomatis inject data pengguna aktif jika belum didefinisikan secara eksplisit
        if (!isset($data['authUser'])) {
            $data['authUser'] = $this->getAuthUser();
        }

        // Ekstrak data array agar menjadi variabel mandiri di dalam view
        if (!empty($data) && is_array($data)) {
            extract($data);
        }

        // Sanitasi path untuk mencegah Directory Traversal
        $sanitizedPath = str_replace(['../', '..\\'], '', $viewPath);
        $file = __DIR__ . '/../views/' . trim($sanitizedPath, '/\\') . '.php';

        if (!file_exists($file)) {
            http_response_code(500);
            die("Kesalahan Sistem: Berkas view '<b>" . htmlspecialchars($viewPath, ENT_QUOTES, 'UTF-8') . "</b>' tidak ditemukan.");
        }

        if ($useLayout) {
            require_once __DIR__ . '/../views/layouts/header.php';
            require $file;
            require_once __DIR__ . '/../views/layouts/footer.php';
        } else {
            require $file;
        }
    }
}
