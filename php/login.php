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
        <form action="../controladores_php/procesar_login.php" method="POST">
            <h1 id="titulo-login">BIENVENIDO.</h1>
            <?php 
            if (isset($_SESSION['error_message'])):
            ?>
                <div class="alert alert-danger" style="color: #D8000C; background-color: #FFD2D2; margin-bottom: 15px; padding: 10px; border-radius: 5px;"><?php echo $_SESSION['error_message']; ?></div>
            <?php 
                unset($_SESSION['error_message']);
            endif;

            if (isset($_SESSION['success_message'])):
            ?>
                <div class="alert alert-success" style="color: #4F8A10; background-color: #DFF2BF; margin-bottom: 15px; padding: 10px; border-radius: 5px;"><?php echo $_SESSION['success_message']; ?></div>
            <?php 
                unset($_SESSION['success_message']);
            endif;
            ?>

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
        </form>
    </div>
    <!-- <?php include 'footer.php'; ?> -->
</body>
</html>