<?php
//no se requiere sesión para esta página
include('../controladores_php/conectar.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daino - Juega sin registro</title>
    <link rel="stylesheet" href="../css/modelo.css">
    <link rel="stylesheet" href="../css/juego.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header>
    <?php include('./cabecera.php'); ?>
    </header>
    
    <main id="cuerpo">
    <div id="section-darkmode">
        <div class="contenedor-darkmode">
            <input type="checkbox" id="check-darkmode" >
            <label for="check-darkmode" id="boton-darkmode"></label>
        </div>
    </div>
    <div class="recuperar"></div>

    <!-- mensaje informativo para usuarios no registrados -->
    <div style="background: #aa5d5d; color: white; padding: 15px; margin: 20px; border-radius: 10px; text-align: center;">
        <h3>¡Juega gratis sin registro!</h3>
        <p>Puedes probar todos los niveles inmediatamente. Tu progreso se guarda localmente. Para guardarlo permanentemente y aparecer en el ranking, <a href="./register.php" style="color: #f4f4f4; font-weight: bold;">regístrate aquí</a> o <a href="./login.php" style="color: #f4f4f4; font-weight: bold;">inicia sesión</a>.</p>
        <div id="progreso-local-resumen" style="margin-top: 10px; font-size: 0.9em; opacity: 0.9;"></div>
    </div>

    <div>
        <?php 
        // Consulta para obtener todas las canciones sin filtrar por usuario
        $stmt = $conexion->prepare("SELECT id, nombre, autor, img, src, SUBSTRING_INDEX(duracion, ':', -2) as duracion FROM canciones ORDER BY duracion ASC");
        $stmt->execute();
        $stmt->bind_result($id, $nombre, $autor, $img, $src, $duracion);
        ?>
            <ul class="opciones"> 
            <?php
            while ($stmt->fetch()) {
                ?>
                <li>
                    <div class="botones" onclick="window.location.href='./juego.php?id_cancion_cargar=<?php echo($id); ?>';">
                        <img src="../img/<?php echo($img); ?>" alt="" class="circulo">
                        <div class="titulo-cancion">
                            <p class="subtitulo-cancion1"><?php echo($nombre); ?></p>
                            <p class="subtitulo-cancion2"> <?php echo($autor); ?> </p>
                        </div>
                        <p class="porcentaje" data-cancion-id="<?php echo($id); ?>">0% </p>
                        <p class="minuto"><?php echo($duracion); ?></p>
                    </div>
                </li>
                <?php  
            }
            ?>
            </ul>
            <div class="recuperar"></div>
        <?php                            
        $stmt->close();
        $conexion->close();
        ?>
    </div>
    <div class="recuperar"></div>
    
  
    </main>

    <script src="../js/darkmode.js"></script>
    
    <!-- Script para cargar progreso local en usuarios no logueados -->
    <script>
        //funcion para obtener progreso local de localStorage
        function obtenerProgresoLocal() {
            try {
                return JSON.parse(localStorage.getItem('dino_progreso_local') || '[]');
            } catch (error) {
                console.error('Error obteniendo progreso local:', error);
                return [];
            }
        }

        //funcion para actualizar porcentajes en la lista de canciones
        function actualizarPorcentajesLocales() {
            const progresoLocal = obtenerProgresoLocal();
            
            //actualizar cada porcentaje en la lista
            document.querySelectorAll('.porcentaje[data-cancion-id]').forEach(elemento => {
                const idCancion = elemento.getAttribute('data-cancion-id');
                const progresoCancion = progresoLocal.find(item => item.id_cancion == idCancion);
                
                if (progresoCancion) {
                    elemento.textContent = progresoCancion.porcentaje + '%';
                    elemento.style.color = '#4CAF50'; //verde para indicar progreso guardado
                }
            });
            
            //mostrar resumen de progreso total
            if (progresoLocal.length > 0) {
                const puntosTotal = progresoLocal.reduce((sum, item) => sum + item.puntos, 0);
                const promedioProgreso = Math.round(
                    progresoLocal.reduce((sum, item) => sum + item.porcentaje, 0) / progresoLocal.length
                );
                
                document.getElementById('progreso-local-resumen').innerHTML = 
                    ` Progreso local: ${progresoLocal.length} niveles jugados, ${puntosTotal} puntos totales, ${promedioProgreso}% promedio`;
            }
        }

        //ejecutar cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarPorcentajesLocales();
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>
