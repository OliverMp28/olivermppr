<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// guardar_mensaje.php
// require_once 'controladores_php/conectar.php'; //se quita por que el enrutador ya se encarga d eponerlo




// Obtener el mensaje enviado desde el cliente

$comentario = $_POST['comentario'];
$idUsuario = $_SESSION["id_usuario"];
$visible = isset($_POST['visible']) ? true : false;

// Usar consultas preparadas para prevenir inyección SQL
$stmt = $conexion->prepare('INSERT INTO comentarios (id_usuario, comentario, visible) VALUES (?, ?, ?)');
// Convertir booleano a entero (0 o 1) para la base de datos
$visible_int = $visible ? 1 : 0;
$stmt->bind_param('isi', $idUsuario, $comentario, $visible_int);
$resultado = $stmt->execute();

/*
if($resultado)
    echo('Comentario enviado con exito');

else 
    echo('Error intentando enviar el comentario');
*/

header('Location: ' . BASE_URL . '/info');

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