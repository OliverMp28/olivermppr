<?php
declare(strict_types=1);

/**
 * Bootstrap de Daino.
 *
 * Se ejecuta en cada request antes de que public/index.php cree el Router.
 * Responsabilidades:
 *   1. Autoload (Composer si existe, fallback PSR-4 mínimo).
 *   2. Variables de entorno vía vlucas/phpdotenv.
 *   3. Error reporting según APP_ENV / APP_DEBUG.
 *   4. Timezone.
 *   5. Handler global de excepciones (503 para PDO, 500 para el resto).
 *   6. Arrancar la sesión.
 */

// --- 1. Autoload ---------------------------------------------------------

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require $vendorAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

// --- 2. Variables de entorno --------------------------------------------

if (class_exists(\Dotenv\Dotenv::class)) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

// --- 3. Error reporting según entorno ------------------------------------

$env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
$debug = filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);

if ($env !== 'production' && $debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    // E_STRICT fue removido en PHP 8.4; E_ALL ya no lo incluye.
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// --- 4. Timezone ---------------------------------------------------------

date_default_timezone_set('UTC');

// --- 5. Handler global de excepciones ------------------------------------
// Convierte fallos no atrapados en respuestas HTTP controladas. PDO →
// 503 Service Unavailable (BD caída, credenciales mal). Resto → 500.

set_exception_handler(static function (\Throwable $e) use ($debug): void {
    $isPdo = $e instanceof \PDOException;
    $status = $isPdo ? 503 : 500;
    $reason = $isPdo ? 'Service Unavailable' : 'Internal Server Error';

    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }

    $payload = ['error' => $reason];
    if ($debug) {
        $payload['exception'] = $e::class;
        $payload['message']   = $e->getMessage();
        $payload['file']      = $e->getFile() . ':' . $e->getLine();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    error_log(sprintf('[daino] %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
});

// --- 6. Sesión ----------------------------------------------------------

\App\Core\Session::start();
