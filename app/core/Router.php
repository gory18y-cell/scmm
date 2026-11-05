<?php
/**
 * ==============================================================================
 * ROUTER - SISTEM SUPPLY CHAIN MANAGEMENT (SCM)
 * ==============================================================================
 * Mengelola routing berbasis HTTP Method (GET, POST), memetakan URL statis dan
 * dinamis (contoh: /vendor/edit/{id}) ke Controller dan Method terkait, serta
 * menangani galat HTTP 404 (Not Found).
 * ==============================================================================
 */

class Router {
    private $routes = [
        'GET'  => [],
        'POST' => []
    ];

    private $basePath = '/scm';

    /**
     * Konstruktor Router
     *
     * @param string $basePath Path dasar URL aplikasi (contoh: '/scm')
     */
    public function __construct($basePath = '/scm') {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Mendaftarkan rute HTTP GET
     *
     * @param string $path URL path relatif
     * @param array|callable $handler [ControllerClass, 'methodName'] atau Closure
     * @return void
     */
    public function get($path, $handler) {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Mendaftarkan rute HTTP POST
     *
     * @param string $path URL path relatif
     * @param array|callable $handler [ControllerClass, 'methodName'] atau Closure
     * @return void
     */
    public function post($path, $handler) {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Menambahkan rute ke koleksi internal
     */
    private function addRoute($method, $path, $handler) {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath !== '/') {
            $normalizedPath = rtrim($normalizedPath, '/');
        }
        $this->routes[$method][$normalizedPath] = $handler;
    }

    /**
     * Menjalankan router berdasarkan permintaan HTTP yang masuk
     *
     * @return void
     */
    public function run() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Hapus base path (misal: /scm) dari awal URI jika ada
        if (!empty($this->basePath) && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        // Normalisasi URI yang diminta
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        // 1. Cek kecocokan rute statis terlebih dahulu
        if (isset($this->routes[$method][$uri])) {
            $this->dispatch($this->routes[$method][$uri], []);
            return;
        }

        // 2. Cek kecocokan rute dinamis dengan pola {param} (misal: /vendor/edit/{id})
        foreach ($this->routes[$method] as $routePattern => $handler) {
            if (strpos($routePattern, '{') !== false) {
                // Ubah pola {param} menjadi regex capture group ([a-zA-Z0-9_-]+)
                $patternRegex = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $routePattern);
                $patternRegex = '#^' . $patternRegex . '$#';

                if (preg_match($patternRegex, $uri, $matches)) {
                    // Ambil argumen hasil tangkapan regex
                    $params = array_slice($matches, 1);
                    $this->dispatch($handler, $params);
                    return;
                }
            }
        }

        // Jika rute tidak ditemukan, arahkan ke handler 404
        $this->notFound($uri);
    }

    /**
     * Mengeksekusi handler rute (Controller atau Callable)
     *
     * @param array|callable $handler
     * @param array $params
     * @return void
     */
    private function dispatch($handler, $params = []) {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $actionMethod] = $handler;

            $controllerFile = __DIR__ . '/../controllers/' . $controllerClass . '.php';

            if (file_exists($controllerFile)) {
                require_once $controllerFile;

                if (class_exists($controllerClass)) {
                    $controllerInstance = new $controllerClass();

                    if (method_exists($controllerInstance, $actionMethod)) {
                        call_user_func_array([$controllerInstance, $actionMethod], $params);
                        return;
                    }
                }
            }
        }

        $this->notFound();
    }

    /**
     * Menangani halaman 404 Not Found
     *
     * @param string $requestedUri URI yang gagal ditemukan
     * @return void
     */
    public function notFound($requestedUri = '') {
        http_response_code(404);

        $notFoundView = __DIR__ . '/../views/errors/404.php';
        if (file_exists($notFoundView)) {
            $requestedPath = $requestedUri;
            require_once $notFoundView;
        } else {
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>404 Not Found</title></head>';
            echo '<body style="font-family: sans-serif; text-align: center; padding: 50px;">';
            echo '<h1 style="color: #0F2C59;">404 - Halaman Tidak Ditemukan</h1>';
            echo '<p>Halaman yang Anda minta tidak tersedia pada sistem SCM.</p>';
            echo '<a href="' . htmlspecialchars($this->basePath, ENT_QUOTES, 'UTF-8') . '/">Kembali ke Beranda</a>';
            echo '</body></html>';
        }
    }
}
