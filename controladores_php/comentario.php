<?php
session_start();
// guardar_mensaje.php
require_once '../controladores_php/conectar.php';




// Obtener el mensaje enviado desde el cliente

$comentario = $_POST['comentario'];
$idUsuario = $_SESSION["id_usuario"];
$visible = isset($_POST['visible']) ? true : false;

$comentario = mysqli_real_escape_string($conexion, $comentario);
$resultado = mysqli_query($conexion, 'INSERT INTO comentarios (id_usuario, comentario, visible) VALUES ("'.$idUsuario.'", "'.$comentario.'","'.$visible.'")');

/*
if($resultado)
    echo('Comentario enviado con exito');

else 
    echo('Error intentando enviar el comentario');
*/

header('Location: ../php/info.php');

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