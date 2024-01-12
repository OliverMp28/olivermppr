<?php 
require_once './conectar.php';

/*
$inputPorcentaje = $_POST["inputPorcentaje"];
$inputPts = $_POST["inputPts"];

session_start();
$idUsuario = $_SESSION["id_usuario"];

$stmt = $conexion->prepare('INSERT INTO progreso (id_usuario, id_cancion, porcentaje, pts) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', , 2, 3, 4);
$stmt->execute();
*/

session_start();


if (isset($_POST['inputPts']) && isset($_POST['inputPorcentaje'])) {
    $inputPts = $_POST['inputPts'];
    $inputPorcentaje = $_POST['inputPorcentaje'];
    $idCancionCargar = $_POST['idCancionCargar'];
    $idUsuario = $_SESSION["id_usuario"]; //esto lo saco de la session

    $stmt = $conexion->prepare('SELECT * FROM progreso WHERE id_usuario = ? AND id_cancion = ?');
    $stmt->bind_param('ii', $idUsuario, $idCancionCargar);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Si ya existe una fila, hacer un UPDATE si el nuevo porcentaje es mayor
        $row = $result->fetch_assoc();
        if ($inputPorcentaje > $row['porcentaje']) {
            $stmt = $conexion->prepare('UPDATE progreso SET porcentaje = ?, pts = ? WHERE id_usuario = ? AND id_cancion = ?');
            $stmt->bind_param('iiii', $inputPorcentaje, $inputPts, $idUsuario, $idCancionCargar);
            $stmt->execute();
        }
    } else {
        // Si no existe una fila, hacer un INSERT
        $stmt = $conexion->prepare('INSERT INTO progreso (id_usuario, id_cancion, porcentaje, pts) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiii', $idUsuario, $idCancionCargar, $inputPorcentaje, $inputPts);
        $stmt->execute();
    }

    // Calcular los nuevos puntos totales, el promedio de porcentaje y el número de canciones
    $stmt = $conexion->prepare('SELECT SUM(pts) AS puntos_totales, AVG(porcentaje) AS promedio_porcentaje, COUNT(*) AS n_canciones FROM progreso WHERE id_usuario = ?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $puntosTotales = $row['puntos_totales'];
    $promedioPorcentaje = $row['promedio_porcentaje'];
    $nCanciones = $row['n_canciones'];

    // Comprobar si ya existe una fila para el usuario en la tabla ranking
    $stmt = $conexion->prepare('SELECT * FROM ranking WHERE id_usuario = ?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Si ya existe una fila, hacer un UPDATE
        $stmt = $conexion->prepare('UPDATE ranking SET pts_total = ?, porcentaje_total = ?, n_canciones = ? WHERE id_usuario = ?');
        $stmt->bind_param('iiii', $puntosTotales, $promedioPorcentaje, $nCanciones, $idUsuario);
        $stmt->execute();
    } else {
        // Si no existe una fila, hacer un INSERT
        $stmt = $conexion->prepare('INSERT INTO ranking (id_usuario, porcentaje_total, pts_total, n_canciones) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiii', $idUsuario, $promedioPorcentaje, $puntosTotales, $nCanciones);
        $stmt->execute();
    }
} 

?>