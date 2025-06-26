<?php
session_start();
require_once '../controladores_php/conectar.php';

header('Content-Type: application/json');

if (!isset($_SESSION["id_usuario"])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit();
}

$idUsuario = $_SESSION["id_usuario"];

//obtener los datos del POST
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data)) {
    echo json_encode(['status' => 'no_data', 'message' => 'No hay datos para migrar']);
    exit();
}

$progresoMigrado = false;

//iterar sobre cada progreso local
foreach ($data as $progresoLocal) {
    $idCancion = $progresoLocal['id_cancion'];
    $porcentajeLocal = $progresoLocal['porcentaje'];
    $puntosLocal = $progresoLocal['puntos'];

    //comprobar si ya existe progreso para esta canción
    $stmt = $conexion->prepare('SELECT * FROM progreso WHERE id_usuario = ? AND id_cancion = ?');
    $stmt->bind_param('ii', $idUsuario, $idCancion);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Ya existe, comparar y actualizar si el local es mejor
        $row = $result->fetch_assoc();
        if ($porcentajeLocal > $row['porcentaje']) {
            $stmt_update = $conexion->prepare('UPDATE progreso SET porcentaje = ?, pts = ? WHERE id_usuario = ? AND id_cancion = ?');
            $stmt_update->bind_param('iiii', $porcentajeLocal, $puntosLocal, $idUsuario, $idCancion);
            $stmt_update->execute();
            $progresoMigrado = true;
        }
    } else {
        // No existe, insertar nuevo progreso
        $stmt_insert = $conexion->prepare('INSERT INTO progreso (id_usuario, id_cancion, porcentaje, pts) VALUES (?, ?, ?, ?)');
        $stmt_insert->bind_param('iiii', $idUsuario, $idCancion, $porcentajeLocal, $puntosLocal);
        $stmt_insert->execute();
        $progresoMigrado = true;
    }
}

// 2. Si se migró al menos un progreso, recalcular el ranking
if ($progresoMigrado) {
    $stmt_rank = $conexion->prepare('SELECT SUM(pts) AS puntos_totales, AVG(porcentaje) AS promedio_porcentaje, COUNT(*) AS n_canciones FROM progreso WHERE id_usuario = ?');
    $stmt_rank->bind_param('i', $idUsuario);
    $stmt_rank->execute();
    $result_rank = $stmt_rank->get_result();
    $row_rank = $result_rank->fetch_assoc();
    
    $puntosTotales = $row_rank['puntos_totales'];
    $promedioPorcentaje = round($row_rank['promedio_porcentaje']);
    $nCanciones = $row_rank['n_canciones'];

    $stmt_check_rank = $conexion->prepare('SELECT * FROM ranking WHERE id_usuario = ?');
    $stmt_check_rank->bind_param('i', $idUsuario);
    $stmt_check_rank->execute();
    $result_check_rank = $stmt_check_rank->get_result();

    if ($result_check_rank->num_rows > 0) {
        $stmt_update_rank = $conexion->prepare('UPDATE ranking SET pts_total = ?, porcentaje_total = ?, n_canciones = ? WHERE id_usuario = ?');
        $stmt_update_rank->bind_param('iiii', $puntosTotales, $promedioPorcentaje, $nCanciones, $idUsuario);
        $stmt_update_rank->execute();
    } else {
        $stmt_insert_rank = $conexion->prepare('INSERT INTO ranking (id_usuario, porcentaje_total, pts_total, n_canciones) VALUES (?, ?, ?, ?)');
        $stmt_insert_rank->bind_param('iiii', $idUsuario, $promedioPorcentaje, $puntosTotales, $nCanciones);
        $stmt_insert_rank->execute();
    }
}

echo json_encode(['status' => 'success', 'message' => 'Migración completada.']);

?>
