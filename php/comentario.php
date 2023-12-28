<?php
// guardar_mensaje.php

require_once 'conectar.php';

// Obtener el mensaje enviado desde el cliente

$inputNombre = $_POST['inputNombre'];
$comentario = $_POST['comentario'];

$inputNombre = mysqli_real_escape_string($conexion, $inputNombre);
$comentario = mysqli_real_escape_string($conexion, $comentario);
$resultado = mysqli_query($conexion, 'INSERT INTO comentarios (nombre, comentario) VALUES ("'.$inputNombre.'", "'.$comentario.'")');

/*
if($resultado)
    echo('Comentario enviado con exito');

else 
    echo('Error intentando enviar el comentario');
*/

header('Location: info.php');

mysqli_close($conexion)
/*
// Insertar el mensaje en la base de datos
$sql = "INSERT INTO messages (msg) VALUES (:mensaje)";
$stmt = $conexion->prepare($sql);
$stmt->bindParam(':mensaje', $mensaje);
$stmt->execute();

// Respuesta al cliente (puedes personalizar el mensaje de éxito o error)
$response = array('message' => 'Mensaje enviado exitosamente.4s');
echo json_encode($response);
*/
?>
