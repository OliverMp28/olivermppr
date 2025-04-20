<?php
session_start();
if (empty($_SESSION["id_usuario"])){
    header("Location: ./login.php");
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daino</title>
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


    <audio  id="audio" onplay="myFunctionAudio()"> 
        <source  type="audio/mpeg" id="cancion0" src="">
    </audio>

    <audio  id="final">
        <source src="../audios/quebendición.mp3"  type="audio/mpeg">
    </audio>

    <audio id="pipipi">
        <source src="../audios/pipipi.mp3" type="audio/mpeg">
    </audio>


    <div id="juegoYFondo">
   <div id="miLienzo"></div>
        <div class="recuperar"></div>
    <div id="juegazo">
        <div id="cabecera-juego">
            <a href="./index.php" id="volver">Volver</a>
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

        <script src="../p5/p5.min.js"></script>
        <script src="../p5/addons/p5.sound.js"></script>
        
        <?php include '../controladores_php/leer_lista_canciones.php'; ?>
        <script src="../js/juego.js"></script>
        <script src="../js/darkmode.js"></script>

   
    </div>

    </div> 

        <form action="../controladores_php/gestionar_progreso.php" method="POST" id="formularioProgreso">
            <input type="hidden" id="inputPorcentaje" name="inputPorcentaje">
            <input type="hidden" id="inputPts" name="inputPts">
            <input type="hidden" id="idCancionCargar" name="idCancionCargar">
        </form>
        
        <!-- <script src="../addons/p5.sound.js"></script> 
        <script src="../js/sketch.js"></script>-->
    </main>
    
    <footer id="pielogo">
        <div>
          <section class="seccionpie">
            <h1>Sitio Web</h1>
            <p><a href="./index.php">Inicio</a></p>
            <p><a href="https://forms.gle/D8NNqERVakWrsNkA9" target="_blank"> -> Comentarios <- <br> <span></span></a></p>
         <!--  <p><a href="/Contacto.html">  Contacto </a></p> --> 
          </section>
    
          <section class="seccionpie">
            <h1>Version</h1>
            <p><a href="contacto.html">3.0</a></p>
          </section>
    
          <section class="seccionpie">
            <address>Granada, España</address>
            <small>&copy; Derechos Reservados 2023</small>
          </section>
    
          <div class="recuperar"></div>
        </div>
      </footer>


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