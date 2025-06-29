<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/..'));
}

require_once(ROOT_PATH . '/controladores_php/conectar.php');

if (!defined('BASE_URL')) {
    $base_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', ROOT_PATH);
    define('BASE_URL', sprintf(
        '%s://%s%s',
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['HTTP_HOST'],
        $base_path
    ));
}

if (isset($_POST['enviarLogin'])) {
    if (empty($_POST['inputUsuario']) || empty($_POST['inputContraseña'])) {
        $_SESSION['error_message'] = "Por favor, rellena todos los campos.";
        header('Location: ' . BASE_URL . '/login');
        exit();
    }

    $inputUsuario = $_POST['inputUsuario'];
    $inputContraseña = $_POST['inputContraseña'];

    $stmt = $conexion->prepare("SELECT * FROM register WHERE usuario = ?");
    if ($stmt === false) {
        error_log('Prepare failed: ' . $conexion->error);
        $_SESSION['error_message'] = "Error interno del servidor.";
        header('Location: ' . BASE_URL . '/login');
        exit();
    }

    $stmt->bind_param("s", $inputUsuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($inputContraseña, $row['contraseña'])) {
            session_regenerate_id(true);
            $_SESSION["id_usuario"] = $row['id'];
            $_SESSION["nombre_usuario"] = $row['usuario'];
            $_SESSION["usuario"] = $row['usuario'];
            $_SESSION["nombre"] = $row['nombres'];
            $_SESSION["email"] = $row['email'];
            $_SESSION["pais"] = $row['pais'];
            //regenerar ID de sesión para prevenir fijación de sesión
            header('Location: ' . BASE_URL . '/');
            exit();
        } else {
            $_SESSION['error_message'] = "Usuario o contraseña incorrectos.";
            header('Location: ' . BASE_URL . '/login');
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Usuario o contraseña incorrectos.";
        header('Location: ' . BASE_URL . '/login');
        exit();
    }

    $stmt->close();
    $conexion->close();
} else {
    header('Location: ' . BASE_URL . '/login');
    exit();
}
?>