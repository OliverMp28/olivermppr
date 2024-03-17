<?php include('../controladores_php/conectar.php'); ?>
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
    <title>Dino html</title>
    <link rel="stylesheet" href="../css/modelo.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">

    <style>
        header {
            position: relative;
            /* Ajusta el padding inferior según tus necesidades */
            padding-bottom: 100px;
        }
        #contenedor_foto_perfil{
            position: absolute;
            /* Ajusta el top y left según tus necesidades */
            top: calc(100% - 100px);
            left: 50%;
            transform: translateX(-50%);
            margin: auto;
            width: 200px;
            height: 200px;
            border: 1px solid rgba(255, 0, 0, 0.297);
            border-radius: 100%;
            align-items: center;
        }
        #foto{
            width: 100%;
            height: 100%;
            border-radius: 100%;
        }
        #seccion_perfil {
            position: static;
            margin: auto;
            margin-bottom: 20px;
       
            width: 70%;
            /* Ajusta el padding según tus necesidades */
            padding: 20px;
            /* Bordes redondeados solo en la parte inferior */
            border-radius: 20px;
            /* Colores para los modos claro y oscuro */
            background-color: rgba(231, 139, 11, 0.33);
            color: rgb(31, 29, 29);
            /* Sombra para resaltar el contenedor */
            box-shadow: 0px 10px 15px rgba(0, 0, 0, 0.1);

            display: flex;
            justify-content: space-between;
        }

        .dark #seccion_perfil{
            background-color: rgba(222, 114, 78, 0.33);
        }

        #informacion{
            width: 50%;
            margin-left: 10px;
            margin-top: 30px;
        }
        #informacion p{
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 20px;
        }
        #nombre_usuario{
            font-size: 50px !important;
            margin-bottom: 15px;
        }
        #adicional{
            width: 48%;
            margin-top: 100px !important;
            float: right;
            margin-top: 0;
        }
        #adicional p{
            text-align: right;
            
            font-size: 20px;
        }
        .dark #seccion_perfil p{
            color: aliceblue;
        }
        /*movil*/
        @media screen and (max-width:768px){
            #seccion_perfil {
                padding: 10px;
                width: 90%;
            }
            #informacion{
                margin-left: 0px;
            }
            #informacion p{
                font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
                font-size: 17px;
            }
            #adicional p{
                text-align: right;
                
                font-size: 17px;
            }
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
                            <a href="./index.php" class="enlaces-ventanas" >Inicio</a>
                        </li>
                        <li>
                            <a href="./ranking.php" class="enlaces-ventanas">Ranking</a>
                        </li>
                       <li>
                            <a href="./info.php" class="enlaces-ventanas">Info</a>
                        </li>
                        <li>
                            <a href="./perfil.php" class="enlaces-ventanas">Perfil</a>
                        </li>
                        <li>
                            <a href="../controladores_php/cerrar_login.php" class="enlaces-ventanas">Cerrar Sesion</a>
                        </li>
                    </ul>
            </nav> 

            <h1>Perfil</h1>
            <div id="contenedor_foto_perfil">
                <img id="foto" src="../img/foto_dino2.2.png" alt="">
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
            /* 

            while ($stmt2->fetch()) {
                if($visible == 1){
                     ?>
                     <b id="nombre_usuario"> <?php echo($usuario); ?></b> (<?php echo($fecha);?>) dijo:
                    <br>
                    <p><?php echo($comentario);?></p>
                    <div class="lineaSeparador"></div>
                    <?php 
                }                    
            }*/
                    
        ?>
    </section>

    <div class="recuperar"></div>
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