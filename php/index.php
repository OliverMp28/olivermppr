<?php
session_start();
if (empty($_SESSION["id_usuario"])){
    header("Location: ./login.php");
}else{
    include('../controladores_php/conectar.php');
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
    <div class="recuperar"></div>

    <div>
    <?php 
        $id_usuario = $_SESSION["id_usuario"];
        
            $stmt = $conexion->prepare("SELECT c.id, c.nombre, c.autor, c.img, c.src, SUBSTRING_INDEX(c.duracion, ':', -2) as duracion, IFNULL(p.porcentaje, 0) as porcentaje FROM canciones c LEFT JOIN progreso p ON c.id = p.id_cancion AND p.id_usuario = ? ORDER BY c.duracion ASC");

            $stmt->bind_param('i', $id_usuario); 
            
            $stmt->execute();
            
            $stmt->bind_result($id, $nombre, $autor, $img, $src, $duracion, $porcentaje);
            

            ?>
                <ul class="opciones"> 
                <?php
                while ($stmt->fetch()) {
                    ?>
                    <li>
                        <div class="botones"  onclick="window.location.href='./juego.php?id_cancion_cargar=<?php echo($id); ?>';">
                            <img src="../img/<?php echo($img); ?>" alt="" class="circulo">
                            <div class="titulo-cancion">
                                <p class="subtitulo-cancion1"><?php echo($nombre); ?></p>
                                <p class="subtitulo-cancion2"> <?php echo($autor); ?> </p>
                            </div>
                            <p class="porcentaje"><?php echo($porcentaje); ?>% </p>
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

    <!--
     <div class="lista">
        <article class="contenedorCanciones" >
            <h2>Facil</h2>
            <ul class="opciones"> 
                <li>
                    <div id="boton1" class="botones"  onclick="window.location.href='./juego.php?id_cancion_cargar=1';">
                        <img src="img/fondo.png" alt="" class="circulo">
                        <div class="titulo-cancion">
                            <p class="subtitulo-cancion1" id="nombre-cancion1">Sugar Red</p>
                            <p class="subtitulo-cancion2"> subtitulo </p>
                        </div>
                        <p class="minuto">0:40</p>
                    </div>
                </li>
                <li> 
                    <div id="boton2" class="botones"   onclick="window.location.href='./juego.php?id_cancion_cargar=2';">
                        <img src="img/fondo.png" alt="" class="circulo">
                        <div class="titulo-cancion">
                            <p class="subtitulo-cancion1" id="nombre-cancion2">Hope</p>
                            <p class="subtitulo-cancion2"> subtitulo <b>1:19</b> </p>
                        </div>
                        <p class="minuto">1:19</p>
                    </div>
                </li>

            </ul>
        </article> 
        <article class="contenedorCanciones">
            <h2>Medio</h2>
            <ul class="opciones"> 
                <li>
                    <div id="boton3" class="botones"   onclick="window.location.href='./juego.php?id_cancion_cargar=3';">
                        <img src="img/fondo.png" alt="" class="circulo">
                        <div class="titulo-cancion">
                            <p class="subtitulo-cancion1" id="nombre-cancion3">Running With the wolves</p>
                            <p class="subtitulo-cancion2"> subtitulo </p>
                        </div>

                        <p class="minuto">2:43</p>
                    </div>
                </li>
                <li>
                    <div id="boton4" class="botones"   onclick="window.location.href='./juego.php?id_cancion_cargar=4';">
                        <img src="img/fondo.png" alt="" class="circulo">
                        <div class="titulo-cancion">
                            <p class="subtitulo-cancion1" id="nombre-cancion4">Time</p>
                            <p class="subtitulo-cancion2"> subtitulo </p>
                        </div>
                        <p class="minuto">2:46</p>
                    </div>
                </li>
            </ul>
        </article>
        <article  class="contenedorCanciones">
            <h2>Dificil</h2>
            <ul class="opciones">
                <li>
                    <div id="boton5" class="botones"   onclick="window.location.href='./juego.php?id_cancion_cargar=5';">
                        <img src="img/fondo.png" alt="" class="circulo">
                        <div class="titulo-cancion">
                            <p class="subtitulo-cancion1" id="nombre-cancion5">Mi Fiesta</p>
                            <p class="subtitulo-cancion2"> subtitulo </p>
                        </div>
                        <p class="minuto">3:13</p>
                    </div>
                </li>
                <li>
                    <div id="boton6" class="botones"   onclick="window.location.href='./juego.php?id_cancion_cargar=6';">
                        <img src="img/fondo.png" alt="" class="circulo">
                        <div class="titulo-cancion">
                            <p class="subtitulo-cancion1" id="nombre-cancion6">Dawn of Faith</p>
                            <p class="subtitulo-cancion2"> subtitulo </p>
                        </div>
                        <p class="minuto">3:16</p>
                    </div>
                </li>
                <li>
                    <div id="boton7" class="botones"   onclick="window.location.href='./juego.php?id_cancion_cargar=7';">
                        <img src="img/fondo.png" alt="" class="circulo">
                        <div class="titulo-cancion">
                            <p class="subtitulo-cancion1" id="nombre-cancion7">What's Up Danger</p>
                            <p class="subtitulo-cancion2"> subtitulo </p>
                        </div>
                        <p class="minuto">3:35</p>
                    </div>
                </li>
            </ul>
        </article>         
    </div> -->

    <!--
    <div id="juegazo">
        <div id="cabecera-juego">
            <a href="" id="volver">Volver</a>
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

            <div class="contenedor-boton"> <button id="play"> Play!! </button></div>
        </div>

        <div id="tiempox"> </div>
        <div id="comprobacion2"> </div>         
    </div> -->

    
    <script src="../js/darkmode.js"></script>
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
</body>
</html>