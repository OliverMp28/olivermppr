<?php
require_once 'conectar.php';

$inputUsuario = $_POST['inputUsuario'];
$inputNombre = $_POST['inputNombre'];
$inputEmail = $_POST['inputEmail'];
$inputPais = $_POST['inputPais'];
$inputContraseña = $_POST['inputContraseña'];
//$inputContraseña2 = $_POST['inputContraseña2'];

$inputUsuario = mysqli_real_escape_string($conexion, $inputUsuario);
$inputNombre = mysqli_real_escape_string($conexion, $inputNombre);
$inputEmail = mysqli_real_escape_string($conexion, $inputEmail);
$inputPais = mysqli_real_escape_string($conexion, $inputPais);
$inputContraseña = mysqli_real_escape_string($conexion, $inputContraseña);
//$inputContraseña2 = mysqli_real_escape_string($conexion, $inputContraseña2);

$resultado = mysqli_query($conexion, 'INSERT INTO register (usuario, nombres, email, pais, contraseña) VALUES ("'.$inputUsuario.'", "'.$inputNombre.'","'.$inputEmail.'","'.$inputPais.'","'.$inputContraseña.'")');

/*
if($resultado)
    echo('Comentario enviado con exito');

else 
    echo('Error intentando enviar el comentario');
*/

header('Location: ../index.html');

mysqli_close($conexion)

?>