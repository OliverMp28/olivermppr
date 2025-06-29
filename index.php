<?php
// Iniciar la sesión en el punto de entrada único de la aplicación.
// Esto asegura que la sesión esté disponible para todas las rutas.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir una constante para la ruta raíz del proyecto
define('ROOT_PATH', __DIR__);

// Definir una constante para la URL base del sitio.
// Útil para crear enlaces absolutos en las vistas.
// La detección de https y el host es para que funcione tanto en local como en producción.
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
define('BASE_URL', $protocol . '://' . $host . $base_dir);

// Incluir el enrutador que manejará la petición.
require ROOT_PATH . '/router.php';

exit;
?>
