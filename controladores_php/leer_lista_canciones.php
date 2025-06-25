<?php
//validar que el id_cancion sea un entero
$idCancionCargar = filter_input(INPUT_GET, 'id_cancion_cargar', FILTER_VALIDATE_INT);

if ($idCancionCargar === false || $idCancionCargar === null) {
    //si no es un entero válido, devolver un error o un valor por defecto
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de canción no válido']);
    exit;
}

require_once '../controladores_php/conectar.php';


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