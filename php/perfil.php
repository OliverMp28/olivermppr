<?php 
// Verificar si el usuario está logueado
$usuario_logueado = isset($_SESSION['id_usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Daino</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/foto_dino2.2.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/modelo.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/perfil.css">

</head>
<body>
    <header>
        <?php include(ROOT_PATH . '/php/cabecera.php'); ?>

            <!-- <h1>Perfil</h1> -->
            <div id="contenedor_foto_perfil">
                <img id="foto" src="<?php echo BASE_URL; ?>/img/foto_dino2.2.png" alt="">
            </div> 


            
        </div>
    </header>
    

    <main id="cuerpo">
    <div id="section-darkmode">
        <div class="contenedor-darkmode">
            <input type="checkbox" id="check-darkmode" >
            <label for="check-darkmode" id="boton-darkmode"></label>
        </div>
    </div>
    <div class="recuperar"></div>

    <?php if (!$usuario_logueado): ?>
        <!-- Página de aviso para usuarios no logueados -->
        <section id="seccion_perfil">
            <div style="text-align: center; padding: 40px 20px; background: #aa5d5d; color: white; border-radius: 12px; margin: 20px; box-shadow: 0 4px 15px rgba(170, 93, 93, 0.3);">
                <h1 style="font-size: 2.2em; margin-bottom: 20px; font-weight: 300;">Tu Perfil Personal</h1>
                <h2 style="color: #f4f4f4; margin-bottom: 20px; font-weight: 400;">Crea tu perfil de jugador</h2>
                <p style="font-size: 1.1em; line-height: 1.6; margin-bottom: 30px; opacity: 0.9;">
                    Tu perfil personal es donde puedes ver todas tus estadísticas.<br>
                    <strong>Regístrate para tener tu propio perfil.</strong>
                </p>
                
                <div style="background: rgba(0,0,0,0.15); padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #f4f4f4;">
                    <h3 style="color: #f4f4f4; margin-bottom: 15px; font-weight: 400;">En tu perfil verás:</h3>
                    <ul style="list-style: none; padding: 0; font-size: 1em; text-align: left; max-width: 400px; margin: 0 auto;">
                        <li style="margin: 8px 0; padding: 5px 0;">Tu posición actual en el ranking</li>
                        <li style="margin: 8px 0; padding: 5px 0;">• Total de puntos acumulados</li>
                        <li style="margin: 8px 0; padding: 5px 0;">• Número de niveles completados</li>
                        <li style="margin: 8px 0; padding: 5px 0;">• Estadísticas detalladas de progreso</li>
                    </ul>
                </div>
                
                <div style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: 6px; margin: 20px 0;">
                    <p style="font-size: 0.95em; margin: 0; opacity: 0.9;">
                        <strong>💡:</strong> Mientras juegas sin registro, puedes ver tu progreso temporal, 
                        pero solo registrándote podrás guardarlo permanentemente.
                    </p>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="./registro" style="background: #f4f4f4; color: #aa5d5d; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 1.1em; margin: 8px; display: inline-block; transition: all 0.3s;">
                        Crear Mi Perfil
                    </a>
                    <a href="./login" style="background: transparent; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 1.1em; margin: 8px; display: inline-block; border: 2px solid white; transition: all 0.3s;">
                        Iniciar Sesión
                    </a>
                </div>
<!--                 
                <p style="margin-top: 25px; font-size: 0.9em; opacity: 0.8;">
                    ¿Ya tienes perfil? <a href="./login.php" style="color: #f4f4f4; text-decoration: underline;">Inicia sesión aquí</a>
                </p> -->
            </div>
        </section>
    <?php else: ?>
        <!-- Perfil normal para usuarios logueados -->   
        <section id="seccion_perfil">
        

        <?php 
            $id_usuario = $_SESSION["id_usuario"];
            
            $stmt1 = $conexion->prepare("SELECT usuario, nombres, email 
            FROM register WHERE id = ?");
            $stmt1->bind_param("i", $id_usuario); 
            $stmt1->execute();
            $stmt1->bind_result($usuario, $nombres, $email);
            $resultados1 = [];
            if ($stmt1->fetch()) {
                $resultados1 = ['usuario' => $usuario, 'nombres' => $nombres, 'email' => $email];
            }
            $stmt1->close();

          /*  $stmt2 = $conexion->prepare("SELECT n_canciones, pts_total FROM ranking WHERE id_usuario = ?");
            $stmt2->bind_param("i", $id_usuario);
            $stmt2->execute();
            // Vincular las variables a las columnas del resultado
            $stmt2->bind_result($n_canciones, $pts_total);*/

            $stmt2 = $conexion->prepare("SELECT n_canciones, pts_total, (SELECT COUNT(*) FROM ranking r 
            WHERE r.pts_total > ranking.pts_total) + 1 
            AS orden 
            FROM ranking WHERE id_usuario = ?");
            $stmt2->bind_param("i", $id_usuario);
            $stmt2->execute();
            // Vincular las variables a las columnas del resultado
            $stmt2->bind_result($n_canciones, $pts_total, $orden);


            while ($stmt2->fetch()) {
                ?>
                    <div id="informacion">
                        <p id="nombre_usuario"><?php echo($resultados1['usuario']); ?></p>
                        <p><?php echo($resultados1['nombres']); ?></p>
                        <p><?php echo($resultados1['email']); ?></p>
                    </div>
                    <div id="adicional">
                        <p> Puesto <?php echo($orden); ?> en el ranking</p> 
                        <p><?php echo($pts_total); ?> puntos totales</p>
                        <p><?php echo($n_canciones); ?> niveles jugados</p>
                    </div>

                <?php
            }

          
            $stmt2->close();

            $conexion->close();
                    
        ?>
        </section>
    <?php endif; ?>

    <div class="recuperar"></div>
    <script src="<?php echo BASE_URL; ?>/js/darkmode.js"></script>


    </main>

    
    <?php include 'footer.php'; ?>


</body>
</html>