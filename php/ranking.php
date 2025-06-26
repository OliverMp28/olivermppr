<?php
session_start();
include('../controladores_php/conectar.php'); 

$usuario_logueado = !empty($_SESSION["id_usuario"]);
// Ya no redirigimos automáticamente, manejamos ambos casos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - Daino</title>
    <link rel="stylesheet" href="../css/modelo.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" rel="stylesheet">

    <style>
       
        #section_ranking{
            margin-bottom: 20px !important;
        }        

        #lista_podio{
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: flex-end;
            align-content: stretch;

            box-sizing: border-box;
        }
       
        #lista_podio li:nth-child(1) {
            width: 33%;
            display: block;
            flex-grow: 0;
            flex-shrink: 1;
            flex-basis: auto;
            align-self: auto;
            
            order: 1;

            z-index: 0;
        }

        #lista_podio li:nth-child(2) {
            width: 33%;
            display: block;
            flex-grow: 0;
            flex-shrink: 1;
            flex-basis: auto;
            align-self: auto;
            order: 0;
        }

        #lista_podio li:nth-child(3) {
            width: 33%;
            display: block;
            flex-grow: 0;
            flex-shrink: 1;
            flex-basis: auto;
            align-self: auto;
            order: 2;
        }
        #lista_podio li:nth-child(1) .podio_cuerpo{
            height: 240px;
        }
        #lista_podio li:nth-child(2) .podio_cuerpo{
            height: 195px;
        }
        #lista_podio li:nth-child(3) .podio_cuerpo{
            height: 150px;
        }

        #lista_podio .puesto{
            margin: auto;
            color: rgb(255, 255, 255);
            
            text-align: center;
        }
        .podio_encabezado{
            width: 100%;
            min-height: 80px;
            margin-top: 20px;


            list-style-type: none;
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            justify-content: center;
            align-items: flex-end;
            align-content: stretch;

        } 
        #lista_podio .usuario_ranking{
            width: 100%;
            max-width: 190px;
            
            text-align: center;
            
       
            margin-bottom: 5px;
            font-family: Comic Sans MS;
            letter-spacing: 1px;
            word-spacing: 0px;
            font-weight: 500;
            color: #000000;
            text-decoration: none;
            font-style: normal;
            font-variant: normal;
            text-transform: none;

            word-wrap: break-word;
            white-space: initial;
            line-height: 1.1;
        }
        #lista_podio .puntos_ranking{
            margin: 10px auto;

            font-family: Comic Sans MS;
            letter-spacing: 1px;
            word-spacing: 0px;
            color: #000000;
            text-decoration: none;
            font-style: normal;
            font-variant: normal;
            text-transform: none;
            text-align: center;
        }
       /* .podio{
            margin-top: 20px;
        }*/
        .podio .podio_cuerpo{
            border: solid 1px rgb(227, 227, 227);
            border-radius: 5px;


            background-color: rgb(226, 220, 220);
            -webkit-box-shadow: 0px 0px 27px 0px rgba(109, 43, 43, 0.98);
            -moz-box-shadow: 0px 0px 27px 0px rgba(111, 47, 47, 0.98);
            box-shadow: 0px 1px 27px 0px rgba(99, 48, 48, 0.98);
        }
        .forma{
            border-radius: 50% 50% 65% 35% / 36% 46% 54% 64% ;
            background-color: rgb(165, 35, 59);
            padding: 20px;

            margin: auto;
            margin-top: 10px;

            justify-content: center;
        }





        #lista_ranking{
            margin: auto;
            margin-bottom: 35px;
            
            padding: 0;
            
            list-style-type: none;
            display: flex;
            gap: 20px;
            
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
        }
        #lista_ranking li{
            display: block;
            width: 90%;
        }
        #lista_ranking div{
            margin: 0 auto;
            display: flex;
        }
        
        #lista_ranking .puesto{
            margin: auto;
            margin-left: 1px;
            margin-right: 1px;
            float: left;
            border-radius: 5px;

            
            text-align: center;
            background-color:  rgb(165, 35, 59);

            color: rgb(246, 246, 246);
            font-size: 28px;
        }
        #lista_ranking .usuario_ranking{
            height: min-content;
            line-height: 56px;
            word-wrap: break-word;
            white-space: initial;
            line-height: 1.1;
            margin: auto;

            font-family: Comic Sans MS;
            font-size: 22px;
            letter-spacing: 1px;
            font-weight: 500;
            color: #000000;
            text-decoration: none;
            font-style: normal;
            font-variant: normal;
            text-transform: none;
        }
        #lista_ranking .contenedor_puntos_ranking{
            margin: auto;
            margin-right: 1px;
            margin-left: 0%;
            padding: 2spx;
            border-radius: 50%;
            border: solid 1px rgb(99, 2, 100);
            display: grid;
            float: right;
        }
        #lista_ranking p{
            height: 18px;
            font-family: Comic Sans MS;
            font-size: 16px;
            letter-spacing: 1px;
            font-weight: 500;
            color: #000000;
            text-decoration: none;
            text-align: center;
            text-transform: none;
        }
        #lista_ranking .puntos_ranking{
            width: 100%;
            font-family: Comic Sans MS;
            letter-spacing: 1px;
            font-weight: 500;
            color: #000000;
            text-decoration: none;
            text-align: center;
            text-transform: none;
            vertical-align: text-bottom;
        }
        .otros_puestos{
            border-radius: 5px;
            height: 48px;
            background-color: rgb(226, 220, 220);
        }
        /*movil*/
        @media screen and (max-width:768px){
            #section_ranking{
                margin: auto;
                width: 95%;
        
                background-color: rgb(169, 63, 59);
                border-radius: 10px;
            }
            #lista_podio{
                width: 98%;
                height: 340px; /* Cambiado a min-height para adaptarse al contenido */
                margin: auto;
                margin-bottom: 4%;
            }
            .forma{
                height: 30px;
                width: 40px;
            }
            #lista_podio .usuario_ranking{
                font-size: 18px;
            }
            #lista_podio .puntos_ranking{
                font-size: 18px;
            }
            #lista_podio .puesto{
                line-height: 30px;   
                font-size: 30px;
            }

            #lista_ranking{
                width: 90%;
            }
            #lista_ranking .puesto{
                width: 46px !important;
                height: 46px;
                line-height: 46px;
            }
            #lista_ranking .usuario_ranking{
                width: 60%;
                max-width: 390px;
                height: min-content;
                
                font-size: 18px;
            }
           
        }

        /*tablet*/
        @media screen and (min-width:768px){
            #section_ranking{
                margin: auto;
                width: 80%;
            
                background-color: rgb(169, 63, 59);
                border-radius: 10px;
            }
            #lista_podio{
                width: 90%;
                height: 340px; /* Cambiado a min-height para adaptarse al contenido */
                margin: auto;
                margin-left: 5%;
                margin-right: 5%;
                margin-bottom: 5%;
            }
            .forma{
                height: 40px;
                width: 50px;
            }
            #lista_podio .usuario_ranking{
                font-size: 22px;
            }
            #lista_podio .puntos_ranking{
                
                font-size: 20px;
            }
            #lista_podio .puesto{
                line-height: 40px;   
                font-size: 40px;
            }

            #lista_ranking{
                width: 90%;
            }
            #lista_ranking li{
                margin: 0 auto;
                width: 80%;
            }
            #lista_ranking .puesto{
                width: 50px !important;
                height: 50px;
                line-height: 50px;
            }
            #lista_ranking .usuario_ranking{
                width: 70%;
                max-width: 390px;

                height: min-content;
            }
            #lista_ranking .puntos_ranking{
                height: 25px;
                font-size: 20px;
            }
        }

        /*escritorio */
        @media screen and (min-width: 990px){
            #section_ranking{
                margin: auto;
                width: 650px;

                background-color: rgb(169, 63, 59);
                border-radius: 10px;
            }
            #lista_podio{
                width: 90%;
                height: 340px; /* Cambiado a min-height para adaptarse al contenido */
                margin: auto;
                margin-left: 5%;
                margin-right: 5%;
                margin-bottom: 4%;
            }
        }


        /*darkmode*/
        .dark #lista_podio .podio_cuerpo{
            background-color: #302b2b;
            border-color: rgb(26, 25, 25);

            -webkit-box-shadow: 0px 0px 27px 0px rgba(41, 14, 14, 0.98);
            -moz-box-shadow: 0px 0px 27px 0px rgba(41, 14, 14, 0.98);
            box-shadow: 0px 1px 27px 0px rgba(41, 14, 14, 0.98);
        }
        .dark #lista_podio p{
            color: aliceblue;
        }
        .dark #lista_ranking .otros_puestos{
            background-color: #272424;
        }
        .dark #lista_ranking p{
            color: rgb(240, 240, 240);
        }
        .dark #lista_ranking .contenedor_puntos_ranking{
            border-color: #79414a;
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
    <div class="recuperar"></div>
    
    <?php if (!$usuario_logueado): ?>
        <!--página de aviso para usuarios no logueados -->
        <section id="section_ranking">
            <div style="text-align: center; padding: 40px 20px; background: #aa5d5d; color: white; border-radius: 12px; margin: 20px; box-shadow: 0 4px 15px rgba(170, 93, 93, 0.3);">
                <h1 style="font-size: 2.2em; margin-bottom: 20px; font-weight: 300;">🏆 Ranking de Jugadores</h1>
                <h2 style="color: #f4f4f4; margin-bottom: 20px; font-weight: 400;">¡Compite con otras personas!</h2>
                <p style="font-size: 1.1em; line-height: 1.6; margin-bottom: 30px; opacity: 0.9;">
                    El ranking muestra a los mejores jugadores y sus puntuaciones.<br>
                    <strong>Regístrate para aparecer en el ranking.</strong>
                </p>
                
                <div style="background: rgba(0,0,0,0.15); padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #f4f4f4;">
                    <h3 style="color: #f4f4f4; margin-bottom: 15px; font-weight: 400;">En el ranking verás:</h3>
                    <ul style="list-style: none; padding: 0; font-size: 1em; text-align: left; max-width: 400px; margin: 0 auto;">
                        <li style="margin: 8px 0; padding: 5px 0;">• Los 3 mejores jugadores en el podio</li>
                        <li style="margin: 8px 0; padding: 5px 0;">• Puntuaciones totales de cada jugador</li>
                        <li style="margin: 8px 0; padding: 5px 0;">• Tu posición cuando te registres</li>
                    </ul>
                </div>
                
                <!-- <div style="background: rgba(0,0,0,0.1); padding: 15px; border-radius: 6px; margin: 20px 0;">
                    <p style="font-size: 0.95em; margin: 0; opacity: 0.9;">
                        <strong>💡 Tip:</strong> Solo los usuarios registrados aparecen en el ranking. 
                        ¡Juega, mejora tus puntuaciones y sube posiciones!
                    </p>
                </div> -->
                
                <div style="margin-top: 30px;">
                    <a href="./register.php" style="background: #f4f4f4; color: #aa5d5d; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 1.1em; margin: 8px; display: inline-block; transition: all 0.3s;">
                        Crear Cuenta
                    </a>
                    <a href="./login.php" style="background: transparent; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: 500; font-size: 1.1em; margin: 8px; display: inline-block; border: 2px solid white; transition: all 0.3s;">
                        Iniciar Sesión
                    </a>
                </div>
                
                <p style="margin-top: 25px; font-size: 0.9em; opacity: 0.8;">
                    ¿Ya tienes cuenta? <a href="./login.php" style="color: #f4f4f4; text-decoration: underline;">Inicia sesión aquí</a>
                </p>
            </div>
            <div class="recuperar"></div>
        </section>
    <?php else: ?>
        <!-- Ranking normal para usuarios logueados -->
        <section id="section_ranking">
        <?php 
                $sql = "SELECT register.usuario, ranking.pts_total 
                FROM ranking 
                INNER JOIN register ON ranking.id_usuario = register.id 
                ORDER BY ranking.pts_total DESC";

                $stmt = $conexion->prepare($sql);

                $stmt->execute();

                $stmt->bind_result($usuario, $pts_total);

                $orden = 0;

                if($orden <= 3){
                    ?><ul id="lista_podio"><?php

                    while ($stmt->fetch()) {
                        $orden++;
                            ?>
                                <li class="podio">
                                    <div class="podio_encabezado">
                                        <p class="usuario_ranking"><?php echo($usuario); ?></p>
                                    </div>
                                    <div class="podio_cuerpo">
                                        <div class="forma">
                                            <p class="puesto"><?php echo($orden);  ?></p>
                                        </div>
                                        <p class="puntos_ranking"><?php echo $pts_total . " pts";?> </p>
                                    </div>
                                </li>
                            <?php
                            if($orden == 3){
                                break;
                            }
                    }   

                    ?></ul><?php
                }
                //con esta condicion gestiono los usuarios del resto del ranking
                if($orden >= 3){
                    ?><ul id="lista_ranking"><?php

                    while ($stmt->fetch()) {
                        $orden++;
                            ?>
                                <li>
                                    <div class="otros_puestos">
                                        <p class="puesto"><?php echo($orden);  ?></p>
                                        <p class="usuario_ranking"><?php echo($usuario); ?></p>
                                        <div class="contenedor_puntos_ranking">
                                            <p class="puntos_ranking"><?php echo($pts_total);?></p>
                                            <p>pts</p>
                                        </div>
                                    </div>
                                </li>
                            <?php
                    }   

                    ?></ul><?php
                }else{
                    echo("error desconocido");
                }

        ?>
        <div class="recuperar"></div>
        </section>
    <?php endif; ?>
    <script src="../js/darkmode.js"></script>
    <div class="recuperar"></div>
    </main>

    
    <?php include 'footer.php'; ?>
      
</body>
</html>