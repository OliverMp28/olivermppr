<?php
require_once 'conectar.php';
session_start();

if(!empty($_POST["enviarLogin"])) {
    if (!empty($_POST["inputUsuario"]) and !empty($_POST["inputContraseña"])) {
        # code...
        $inputUsuario = $_POST["inputUsuario"];
        $inputContraseña = $_POST["inputContraseña"];

        $sql = mysqli_query($conexion, 'SELECT * FROM register WHERE usuario="'.$inputUsuario.'" AND contraseña="'.$inputContraseña.'" ');  
        if ($datos=$sql->fetch_object()) {
            $_SESSION["id_usuario"] = $datos->id;
            $_SESSION["usuario"] = $datos->usuario;
            $_SESSION["nombre"] = $datos->nombres;
            $_SESSION["email"] = $datos->email;
            $_SESSION["pais"] = $datos->pais;
            header('Location: ./index.php');
        } else {
            echo "<div>Acceso denegado</div>";
        }
    
    
    } else {
        echo "Campos vacios";
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