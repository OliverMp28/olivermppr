<?php
// require_once('../controladores_php/conectar.php');


if(!empty($_POST["enviarLogin"])) {
    $inputUsuario = $_POST['inputUsuario'];
    $inputNombre = $_POST['inputNombre'];
    $inputEmail = $_POST['inputEmail'];
   // $inputPais = $_POST['inputPais'];
    $inputContraseña = $_POST['inputContraseña'];
    $inputContraseña2 = $_POST['inputContraseña2'];

    // Comprobar si los campos están vacíos
    //iniciar sesión si no está iniciada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($inputUsuario) || empty($inputNombre) || empty($inputContraseña) || empty($inputEmail)) {
        $_SESSION['error_message'] = "Por favor, rellena todos los campos.";
        header('Location: ' . BASE_URL . '/registro');
        exit();
    }
    // Comprobar si las contraseñas coinciden
    elseif ($inputContraseña !== $inputContraseña2) {
        $_SESSION['error_message'] = "Las contraseñas no coinciden.";
        header('Location: ' . BASE_URL . '/registro');
        exit();
    }
    else{
        // Comprobar si el nombre de usuario ya existe
        $stmt = $conexion->prepare('SELECT * FROM register WHERE usuario = ?');
        $stmt->bind_param('s', $inputUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "El nombre de usuario ya existe.";
            header('Location: ' . BASE_URL . '/registro');
            exit();
        }

        // Hashear la contraseña antes de guardarla
        $hashedContraseña = password_hash($inputContraseña, PASSWORD_DEFAULT);

        $stmt = $conexion->prepare('INSERT INTO register (usuario, nombres, email, contraseña) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $inputUsuario, $inputNombre, $inputEmail, $hashedContraseña);
    
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "¡Registro completado! Ahora puedes iniciar sesión.";
            header('Location: ' . BASE_URL . '/login');
            exit();
        } else {
            $_SESSION['error_message'] = "Hubo un error al registrar al usuario.";
            header('Location: ' . BASE_URL . '/registro');
            exit();
        }
    }
}
?>