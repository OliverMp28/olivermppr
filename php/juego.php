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
    <title>Juego - Daino</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/foto_dino2.2.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/modelo.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/juego.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">

</head>
<body>
    <header>
        <?php include(ROOT_PATH . '/php/cabecera.php'); ?>
    </header>
    


    <main id="cuerpo">
    <div id="section-darkmode">
        <div class="contenedor-darkmode">
            <input type="checkbox" id="check-darkmode" >
            <label for="check-darkmode" id="boton-darkmode"></label>
        </div>
    </div>


    <audio  id="audio" onplay="myFunctionAudio()"> 
        <source  type="audio/mpeg" id="cancion0" src="">
    </audio>

    <audio  id="final">
        <source src="<?php echo BASE_URL; ?>/audios/quebendicion.mp3"  type="audio/mpeg">
    </audio>

    <audio id="pipipi">
        <source src="<?php echo BASE_URL; ?>/audios/pipipi.mp3" type="audio/mpeg">
    </audio>


    <div id="juegoYFondo">
   <div id="miLienzo"></div>
        <div class="recuperar"></div>
    <div id="juegazo">
        <div id="cabecera-juego">
            <a href="<?php echo BASE_URL; ?>/" id="volver">Volver</a>
            <p id="nombreJuego">j</p>

        </div>
        <div class="contenedor">
            <div class="suelo"></div>

            <div class="dino"></div>

            <div class="score">0</div>

            <div class="game-over">GAME OVER</div>

            <div class="you-win">¡¡¡Win!!!!</div>

            <div class="jugar-denuevo">
                <button  id="repetir" class="try-again">
                    Try Again
                </button>
            </div>

            <div class="contenedor-boton"> 
                <div id="mensaje-carga" style="display: block;">Cargando...</div>
                <button id="play"> Play!! </button>
            </div>
        </div>

        <div id="tiempox"> </div>
    <!--<div id="comprobacion1"> </div>
        <div id="comprobacion2"> </div> 

        <div id="tiempoDuracion">166</div> -->

        <script src="<?php echo BASE_URL; ?>/p5/p5.min.js"></script>
        <script src="<?php echo BASE_URL; ?>/p5/addons/p5.sound.js"></script>
        
        <?php include(ROOT_PATH.'/controladores_php/leer_lista_canciones.php'); ?>
        <script>
            // Variable global para JavaScript que indica si el usuario está logueado
                        window.usuarioLogueado = <?php echo $usuario_logueado ? 'true' : 'false'; ?>;
            window.BASE_URL = '<?php echo BASE_URL; ?>';
        </script>
        <script src="<?php echo BASE_URL; ?>/js/juego.js"></script>
        <script src="<?php echo BASE_URL; ?>/js/darkmode.js"></script>

   
    </div>

    </div> 

        <form action="<?php echo BASE_URL; ?>/api/progreso" method="POST" id="formularioProgreso">
            <input type="hidden" id="inputPorcentaje" name="inputPorcentaje">
            <input type="hidden" id="inputPts" name="inputPts">
            <input type="hidden" id="idCancionCargar" name="idCancionCargar">
        </form>
        
        <!-- <script src="../addons/p5.sound.js"></script> 
        <script src="../js/sketch.js"></script>-->
    </main>
    
    <?php include 'footer.php'; ?>


      <!--
        -problema, ya no usare un archivo js como db para las canciones, ahora lo pasare a sql para usarlo despues al guardar el progreso del avance de los usuarios
        -
--> 
    <!--
        -la base de datos de haber una tablla que guarde la id del usuario y la id de la cancion
        -
--> 


</body>
</html>