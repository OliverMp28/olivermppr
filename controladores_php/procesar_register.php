<?php
require_once '../controladores_php\conectar.php';

if(!empty($_POST["enviarLogin"])) {
    $inputUsuario = $_POST['inputUsuario'];
    $inputNombre = $_POST['inputNombre'];
    $inputEmail = $_POST['inputEmail'];
   // $inputPais = $_POST['inputPais'];
    $inputContraseña = $_POST['inputContraseña'];
    $inputContraseña2 = $_POST['inputContraseña2'];

    // Comprobar si los campos están vacíos
    if (empty($inputUsuario) || empty($inputNombre) || empty($inputContraseña) || empty($inputEmail)) {
        echo "<div>Por favor, rellena todos los campos.</div>";
    }
    // Comprobar si las contraseñas coinciden
    elseif ($inputContraseña !== $inputContraseña2) {
        echo "<div>Las contraseñas no coinciden.</div>";
    }
    else{
        // Comprobar si el nombre de usuario ya existe
        $stmt = $conexion->prepare('SELECT * FROM register WHERE usuario = ?');
        $stmt->bind_param('s', $inputUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo "<div>El nombre de usuario ya existe.</div>";
            return;
        }

        $stmt = $conexion->prepare('INSERT INTO register (usuario, nombres, email, contraseña) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $inputUsuario, $inputNombre,$inputEmail, $inputContraseña);
    
        if ($stmt->execute()) {
            header('Location: ../php/index.php');
        } else {
            echo "<div>Hubo un error al registrar al usuario.</div>";
        }
    }
}

?>