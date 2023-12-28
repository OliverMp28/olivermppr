<?php include('conectar.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../estilos.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">
    <title>info</title>
    <style>
         #inputNombre{
            width: 390px;
            font-size: 15px;
            border: none;
            border-radius: 5px;
            border-color: rgb(95, 168, 158);
            padding: 5px;
        }
        #enviar{
            padding: 10px;
            width: 100%;
        }
        #comentario{
            width: 100%;
            border: none;
        }
        form{
            width: 400px;
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
        #titulo-info{
            text-align: center;
            font-size: 40px;
            margin-bottom: 2%;
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
        <div>
            <nav class="ventanas">
                <input type="checkbox" id="check">
                <label for="check" class="checkbtn">
                <i class="fas fa-bars"></i>
                </label>
                    <ul class="opciones-ventanas">
                        <li>
                            <a href="../index.html" class="enlaces-ventanas" >Inicio</a>
                        </li>
                        <li>
                            <a href="https://forms.gle/D8NNqERVakWrsNkA9" class="enlaces-ventanas" target="_blank">Comentarios</a>
                        </li>
                        <li>
                            <a href="/info.html" class="enlaces-ventanas">Info</a>
                        </li>
                    </ul>
            </nav> 
            <h1>Dino HTML</h1>

        </div>
    </header>

    <main id="cuerpo">

        <div id="section-darkmode">
            <div class="contenedor-darkmode">
                <input type="checkbox" id="check-darkmode" >
                <label for="check-darkmode" id="boton-darkmode"></label>
            </div>
        </div>
        

        <article>
            <h1 id="titulo-info">Informacion de actualizacion</h1>
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
            <form action="comentario.php" method="POST" id="formulario">
                <label for="inputNombre">Nombre</label> <br>
                <input name="inputNombre" type="text" id="inputNombre" placeholder="introduzca..."> <br>

                <label for="comentario">comentario</label> <br>
                <textarea name="comentario" id="comentario" cols="30" rows="5"></textarea>
            
                <input type="button" value="enviar" id="enviar">
            </form>

            <div id="contenedor_Comentarios">
                <?php 
                    $resultado = mysqli_query($conexion, 'SELECT * FROM  comentarios');

                    while($comentario = mysqli_fetch_object($resultado)){
                    ?>
                        <b id="nombre_usuario"> <?php echo($comentario->nombre); ?></b> (<?php echo($comentario->fecha);?>) dijo:
                        <br>
                        <p><?php echo($comentario->comentario);?></p>
                        <div class="lineaSeparador"></div>
                        <?php 
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
                var nombre = document.getElementById("inputNombre").value;
                var comentario = document.getElementById("comentario").value;

                if(nombre == ""){
                    alert("debe introducir un nombre");
                    throw new Error("No ah insertado un nombre");
                }
                if(comentario == ""){
                    alert("debe introducir un comentario");
                    throw new Error("No ah insertado un comentario");
                }

                var formulario = document.getElementById("formulario");
                formulario.submit();
                alert("Se ah enviado tu comentario");
            });
        </script>
    <!--
        Usuarios
        - nombre de usuario
        - nombres
        - contraseña

        Recorrido
        - Niveles pasados
        - ranking (obtiene el ultimo porcentaje cuando el usuario gane o pierda
                    y va acumulandolo como si fueran puntos y de esa manera
                     los va ordenando en el ranking)

        Comentarios
        - nombre de usuario
        - comentario
    -->
</body>
</html>