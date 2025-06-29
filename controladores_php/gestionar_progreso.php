<?php
// Iniciar sesión de forma segura, ya que este script se llama vía AJAX.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir la conexión a la base de datos.
// La ruta es relativa al script que hace el 'include' (el router en la raíz).
require_once 'controladores_php/conectar.php';

// Verificar que se recibieron los datos necesarios por POST
if (isset($_POST['inputPts'], $_POST['inputPorcentaje'], $_POST['idCancionCargar'])) {
    
    $inputPts = $_POST['inputPts'];
    $inputPorcentaje = $_POST['inputPorcentaje'];
    $idCancionCargar = $_POST['idCancionCargar'];

    // Caso 1: El usuario está logueado
    if (isset($_SESSION["id_usuario"])) {
        $idUsuario = $_SESSION["id_usuario"];

        // --- Actualizar o Insertar en la tabla 'progreso' ---
        $stmt = $conexion->prepare('SELECT porcentaje FROM progreso WHERE id_usuario = ? AND id_cancion = ?');
        $stmt->bind_param('ii', $idUsuario, $idCancionCargar);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Ya existe un registro, actualizar si el nuevo puntaje es mayor
            $row = $result->fetch_assoc();
            if ($inputPorcentaje > $row['porcentaje']) {
                $updateStmt = $conexion->prepare('UPDATE progreso SET porcentaje = ?, pts = ? WHERE id_usuario = ? AND id_cancion = ?');
                $updateStmt->bind_param('diii', $inputPorcentaje, $inputPts, $idUsuario, $idCancionCargar);
                $updateStmt->execute();
            }
        } else {
            // No existe registro, insertar uno nuevo
            $insertStmt = $conexion->prepare('INSERT INTO progreso (id_usuario, id_cancion, porcentaje, pts) VALUES (?, ?, ?, ?)');
            $insertStmt->bind_param('iidi', $idUsuario, $idCancionCargar, $inputPorcentaje, $inputPts);
            $insertStmt->execute();
        }

        // --- Actualizar o Insertar en la tabla 'ranking' ---
        // Recalcular totales para el ranking
        $rankStmt = $conexion->prepare('SELECT SUM(pts) AS puntos_totales, AVG(porcentaje) AS promedio_porcentaje, COUNT(*) AS n_canciones FROM progreso WHERE id_usuario = ?');
        $rankStmt->bind_param('i', $idUsuario);
        $rankStmt->execute();
        $rankResult = $rankStmt->get_result()->fetch_assoc();
        
        $puntosTotales = $rankResult['puntos_totales'] ?? 0;
        $promedioPorcentaje = $rankResult['promedio_porcentaje'] ?? 0;
        $nCanciones = $rankResult['n_canciones'] ?? 0;

        // Comprobar si ya existe una fila para el usuario en la tabla ranking
        $checkRankStmt = $conexion->prepare('SELECT id FROM ranking WHERE id_usuario = ?');
        $checkRankStmt->bind_param('i', $idUsuario);
        $checkRankStmt->execute();
        $checkRankResult = $checkRankStmt->get_result();

        if ($checkRankResult->num_rows > 0) {
            // Ya existe, actualizar
            $updateRankStmt = $conexion->prepare('UPDATE ranking SET pts_total = ?, porcentaje_total = ?, n_canciones = ? WHERE id_usuario = ?');
            $updateRankStmt->bind_param('idii', $puntosTotales, $promedioPorcentaje, $nCanciones, $idUsuario);
            $updateRankStmt->execute();
        } else {
            // No existe, insertar
            $insertRankStmt = $conexion->prepare('INSERT INTO ranking (id_usuario, porcentaje_total, pts_total, n_canciones) VALUES (?, ?, ?, ?)');
            $insertRankStmt->bind_param('idii', $idUsuario, $promedioPorcentaje, $puntosTotales, $nCanciones);
            $insertRankStmt->execute();
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Progreso guardado en la base de datos.']);

    } else {
        // Caso 2: El usuario no está logueado, guardar en la sesión
        if (!isset($_SESSION['local_progress'])) {
            $_SESSION['local_progress'] = [];
        }

        // Obtener progreso local actual para la canción, si existe
        $current_local_progress = $_SESSION['local_progress'][$idCancionCargar] ?? ['porcentaje' => 0];

        // Actualizar solo si el nuevo porcentaje es mayor
        if ($inputPorcentaje > $current_local_progress['porcentaje']) {
            $_SESSION['local_progress'][$idCancionCargar] = [
                'porcentaje' => $inputPorcentaje,
                'pts' => $inputPts
            ];
        }
        echo json_encode(['status' => 'success', 'message' => 'Progreso guardado localmente.']);
    }
} else {
    // Datos POST no recibidos
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
}
?>