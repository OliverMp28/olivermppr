<?php
require_once('../controladores_php/conectar.php');


if(!empty($_POST["enviarLogin"])) {
    if (!empty($_POST["inputUsuario"]) and !empty($_POST["inputContraseña"])) {
                // Iniciar sesión si no está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $inputUsuario = $_POST["inputUsuario"];
        $inputContraseña = $_POST["inputContraseña"];

        //buscar al usuario por su nombre de usuario
        $stmt = $conexion->prepare('SELECT * FROM register WHERE usuario = ?');
        $stmt->bind_param('s', $inputUsuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($datos = $result->fetch_object()) {
            // verificar la contraseña hasheada
            if (password_verify($inputContraseña, $datos->contraseña)) {
            $_SESSION["id_usuario"] = $datos->id;
            $_SESSION["usuario"] = $datos->usuario;
            $_SESSION["nombre"] = $datos->nombres;
            $_SESSION["email"] = $datos->email;
            $_SESSION["pais"] = $datos->pais;
            //regenerar ID de sesión para prevenir fijación de sesión
            session_regenerate_id(true);
            echo '<script>window.location.href = "../php/index.php";</script>';
            } else {
                // Contraseña incorrecta
                $_SESSION['error_message'] = "Usuario o contraseña incorrectos.";
                header('Location: ../php/login.php');
                exit();
            }
        } else {
                        // Usuario no encontrado
            $_SESSION['error_message'] = "Usuario o contraseña incorrectos.";
            header('Location: ../php/login.php');
            exit();
        }
    
    } else {
                $_SESSION['error_message'] = "Por favor, rellena todos los campos.";
        header('Location: ../php/login.php');
        exit();
    }
}


/*
$inputUsuario = $_POST['inputUsuario'];
$inputNombre = $_POST['inputNombre'];
$inputEmail = $_POST['inputEmail'];
$inputPais = $_POST['inputPais'];
$inputContraseña = $_POST['inputContraseña'];
$inputContraseña2 = $_POST['inputContraseña2'];

$inputUsuario = mysqli_real_escape_string($conexion, $inputUsuario);
$inputNombre = mysqli_real_escape_string($conexion, $inputNombre);
$inputEmail = mysqli_real_escape_string($conexion, $inputEmail);
$inputPais = mysqli_real_escape_string($conexion, $inputPais);
$inputContraseña = mysqli_real_escape_string($conexion, $inputContraseña);
$inputContraseña2 = mysqli_real_escape_string($conexion, $inputContraseña2);

$resultado = mysqli_query($conexion, 'INSERT INTO register (usuario, nombres, email, pais, contraseña) VALUES ("'.$inputUsuario.'", "'.$inputNombre.'","'.$inputEmail.'","'.$inputPais.'","'.$inputContraseña.'")');
 */

/*
if($resultado)
    echo('Comentario enviado con exito');

else 
    echo('Error intentando enviar el comentario');
*/

// header('Location: ../index.html');

//mysqli_close($conexion)

?>