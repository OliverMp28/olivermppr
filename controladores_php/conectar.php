<?php

// Definir BASE_URL si no está definida, para que los controladores puedan usarla en redirecciones.
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    // Asumimos que la carpeta del proyecto es 'dino_HTML' si el script está en 'controladores_php'
    $base_dir = rtrim(str_replace('controladores_php', '', str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']))), '/');
    define('BASE_URL', $protocol . '://' . $host . $base_dir);
}
// conexion.php

//CREATE USER 'usr_dino'@'localhost' IDENTIFIED BY 'dino';
//GRANT ALL PRIVILEGES ON dinohtml.* TO 'usr_dino'@'localhost';


$host = 'localhost'; // La dirección del servidor de la base de datos (puede ser diferente si estás trabajando con un servidor remoto)
$dbname = 'dinohtml'; // El nombre de la base de datos que creaste en PhpMyAdmin
$username = 'root'; // El nombre de usuario de la base de datos
$password = ''; // La contraseña de la base de datos

//habilita el reporte de errores de mysqli para que lance excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = mysqli_connect($host, $username, $password, $dbname);
    //establece el juego de caracteres a utf8mb4 para soportar emojis y caracteres especiales
    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500); // Internal Server Error
    die('Error de conexión con la base de datos. Por favor, inténtelo más tarde.');
}
?>