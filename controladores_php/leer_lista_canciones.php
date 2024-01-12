<?php 

$idCancionCargar = $_GET['id_cancion_cargar'];  // 1

require_once './conectar.php';

// Comprobar si el nombre de usuario ya existe
$stmt = $conexion->prepare('SELECT * FROM canciones WHERE id = ?');
$stmt->bind_param('i', $idCancionCargar);
$stmt->execute();
$result = $stmt->get_result();
if ($datos=$result->fetch_object()) {
    $srcCancion = $datos->src;
    $nombreCancion = $datos->nombre;
}
?>
<script>
window.srcCancionElegida = <?php echo json_encode($srcCancion); ?>;
window.nombreCancionElegida = <?php echo json_encode($nombreCancion); ?>;
window.idCancionElegida = <?php echo json_encode($idCancionCargar); ?>;
</script>