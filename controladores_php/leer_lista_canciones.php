<?php
//validar que el id_cancion sea un entero
$idCancionCargar = filter_input(INPUT_GET, 'id_cancion_cargar', FILTER_VALIDATE_INT);

if ($idCancionCargar === false || $idCancionCargar === null) {
    //si no es un entero válido, devolver un error o un valor por defecto
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de canción no válido']);
    exit;
}

// require_once(ROOT_PATH . '/controladores_php/conectar.php');

$rutaCompleta = '';
$srcCancion = '';
$nombreCancion = '';

$stmt = $conexion->prepare('SELECT * FROM canciones WHERE id = ?');
$stmt->bind_param('i', $idCancionCargar);
$stmt->execute();
$result = $stmt->get_result();
if ($datos=$result->fetch_assoc()) {
    $srcCancion = $datos['src'];
    $nombreCancion = $datos['nombre'];
}

$rutaCompleta =  BASE_URL . "/audios/" . $srcCancion;
// var_dump($rutaCompleta);
// die("jola" . $rutaCompleta . "   , " );
?>
<script>
// window.srcCancionElegida = <?php echo json_encode($srcCancion); ?>;
window.srcCancionElegida = <?php echo json_encode($rutaCompleta); ?>;
window.nombreCancionElegida = <?php echo json_encode($nombreCancion); ?>;
window.idCancionElegida = <?php echo json_encode($idCancionCargar); ?>;
</script>