<?php
session_start();

// Verificar si el usuario ya ha iniciado sesión
if (isset($_SESSION['id_usuario'])) {
    // Si ya ha iniciado sesión, redirigir a la página de inicio
    header("Location: ../php/index.php");
    exit; // Asegura que el script se detenga después de redirigir
}?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daino</title>
    <link rel="stylesheet" href="../css/register_login.css">
  
</head>
<body><div id="contenedor-login">
        <form action="" method="POST">
            <h1 id="titulo-login">BIENVENIDO.</h1>
            <?php 
                include "../controladores_php/procesar_login.php"
            ?> <br>
            <label for="inputUsuario" class="lbl-login">Usuario</label> <br>
            <input name="inputUsuario" type="text" id="inputUsuario" class="input-login" placeholder="nombre de usuario">
            <div class="separador-login"></div>

            <label for="inputContraseña" class="lbl-login">Contraseña</label> <br>
            <input name="inputContraseña" type="password" id="inputContraseña" class="input-login" placeholder="introduzca su contraseña">
            <div class="separador-login"></div>

            <div id="contenedor-enlaces">
              <!--  <a href="" class="enlaces-login">Olvide mi contraseña</a> -->
                <a href="./register.php" class="enlaces-login">Registrar</a>
            </div>

            <input type="submit" value="Iniciar sesion" id="enviarLogin" name="enviarLogin">
        </form></div></body>
</html>