<?php
declare(strict_types=1);

/**
 * Bootstrap de Daino.
 *
 * Se ejecuta en cada request antes del router. Su responsabilidad es:
 *   - Cargar el autoloader (Composer si existe, fallback PSR-4 en su defecto).
 *   - Leer variables de entorno.
 *   - Configurar el timezone y error reporting según APP_ENV.
 *
 * El enrutado real (Router::dispatch) se añade cuando exista app/Core/Router.php.
 */

// --- 1. Autoload ---------------------------------------------------------

$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require $vendorAutoload;
} else {
    // Fallback mientras composer install aún no se haya ejecutado.
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

// Carga .env si vlucas/phpdotenv está disponible (tras composer install).
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
