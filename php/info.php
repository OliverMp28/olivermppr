<?php 
session_start();
include('../controladores_php/conectar.php'); 

if (empty($_SESSION["id_usuario"])){
    header("Location: ./login.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/modelo.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">
    <title>info</title>
    <style>
        #enviar{
            padding: 10px;
            width: 100%;
            border-radius: 10px;
            border: solid 1px rgb(74, 6, 92);
        }
        #comentario{
            width: 100%;
            border: none;
        }
        form{
            width: 100%;
            max-width: 500px;
        }

        .dark article p, .dark li, .dark #contenedor_Comentarios{
            color: rgb(243, 240, 241);
        }
        .dark article h1, .dark article h2{
            color: rgb(207, 206, 206);
        }
        .dark label{
            color: rgb(244, 241, 242);
        }
        .lineaSeparador{
            width: 100%;
            border-top: 1.2px solid rgb(131, 131, 131);
            height: 2px;
            padding: 0;
            margin: 20px auto 20px auto;
        }
        .titulo-info{
            text-align: center;
            font-size: 40px;
            margin-bottom: 2%;
            margin-top: 2%;
        }
        #contenedor_Comentarios{
            width: 400px;
        }
        #nombre_usuario{
            font-weight: bold;
        }
    </style>  
    

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
        

        <article>
            <h1 class="titulo-info">Informacion de actualizacion</h1>
            <h2 id="version">Version 3.0</h2>
            <p>Se han añadido mejoras en rendimiento y algunas pocas en diseño, asi como tambien se hizo alguna correcciones de bugs en la jugabilidad y diseño. <br> Las mejoras especificas son:</p>
            <ul id="lista-mejoras">
                <li>Se ah optimizado el codigo para mayor adaptabilidad a distintos dispositivos</li>
                <li>Ahora el juego inicia sin necesidad de volver a cargar la pagina para mayor fluidez</li>
                <li>Se añadio un boton "Play", para controlar cada vez que se quiera iniciar</li>
                <li>Se optimizó el diseño de los botones y el contenedor del juego</li>
                <li>Se habilito la funcion "saltar" en el juego con 2 teclas más, las cuales son: flecha arriba y click izquierdo</li>
                <li>Se añadio el modo oscuro</li>
            </ul>
        </article>
        <article>
            <h1 class="titulo-info">Comentarios</h1>
            <form action="../controladores_php/comentario.php" method="POST" id="formulario">

                <label for="comentario">comentar</label> <br>
                <textarea name="comentario" id="comentario" cols="30" rows="5"></textarea>

                <input type="checkbox" id="visible" name="visible" value="true" checked>
                <label for="visible">Comentario visible al mundo</label> <br>
            
                <input type="button" value="enviar" id="enviar">
            </form>

            <div id="contenedor_Comentarios">
                <?php 
                    // Preparar la consulta
                    $stmt = $conexion->prepare("SELECT comentarios.comentario, comentarios.fecha, comentarios.visible, register.usuario FROM comentarios INNER JOIN register ON comentarios.id_usuario = register.id");

                    // Ejecutar la consulta
                    $stmt->execute();

                    // Vincular las variables a las columnas del resultado
                    $stmt->bind_result($comentario, $fecha, $visible, $usuario);

                    while ($stmt->fetch()) {
                        if($visible == 1){
                            ?>
                            <b id="nombre_usuario"> <?php echo($usuario); ?></b> (<?php echo($fecha);?>) dijo:
                            <br>
                            <p><?php echo($comentario);?></p>
                            <div class="lineaSeparador"></div>
                            <?php 
                        }                    
                    }
                    
                ?>
            </div>

        </article>
     
        
        <!--<div class="recuperar"></div> -->
    </main>
    
    <footer id="pielogo"> 
        <div>
            <section class="seccionpie">
              <h1>Sitio Web</h1>
              <p><a href="/index.html">Inicio</a></p>
              <p><a href="https://forms.gle/D8NNqERVakWrsNkA9" target="_blank"> -> Comentarios <- <br> <span></span></a></p>
              <p><a href="/Contacto.html">  Contacto </a></p>
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

        <script >
        /*---------------MODO OSCURO -------------------- */
        let botonDark=document.getElementById("boton-darkmode");
        let body=document.body;

        botonDark.addEventListener("click", function(){
            let val=body.classList.toggle("dark");
            localStorage.setItem("modo",val)
        })

        let valor=localStorage.getItem("modo")

        if (valor=="true") {
            body.classList.add("dark")
        } else {
            body.classList.remove("dark")
        }

        var enviar = document.getElementById("enviar");
            enviar.addEventListener("click", function(){
                var comentario = document.getElementById("comentario").value;

                if(comentario == ""){
                    alert("debe introducir un comentario");
                    throw new Error("No ah insertado un comentario");
                }

                var formulario = document.getElementById("formulario");
                formulario.submit();
                alert("Se ah enviado tu comentario");
            });
        </script>
</body>
</html>