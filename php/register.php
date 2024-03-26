<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daino</title>
    <link rel="stylesheet" href="../css/register_login.css">

</head>
<body>
    <div id="contenedor-login">
        <form action="" method="POST" id="formularioRegister">
            <h1 id="titulo-login">BIENVENIDO</h1><?php 
                include "../controladores_php/procesar_register.php"
            ?>
            
            <label for="inputUsuario" class="lbl-login">Nombre de Usuario</label> <br>
            <input name="inputUsuario" type="text" id="inputUsuario" class="input-login" placeholder=".........">
            <div class="separador-login"></div>

            <label for="inputNombre" class="lbl-login">Nombres </label> <br>
            <input name="inputNombre" type="text" id="inputNombre" class="input-login" placeholder=".........">
            <div class="separador-login"></div>

            <label for="inputEmail" class="lbl-login">Introduzca su correo electronico </label> <br>
            <input name="inputEmail" type="email" id="inputEmail" class="input-login" placeholder=".........">
            <div class="separador-login"></div>

            <label for="inputContraseña" class="lbl-login">Contraseña</label> <br>
            <input name="inputContraseña" type="password" id="inputContraseña" class="input-login" placeholder=".........">
            <div class="separador-login"></div>

            <label for="inputContraseña2" class="lbl-login">Vuelva a introducir contraseña</label> <br>
            <input name="inputContraseña2" type="password" id="inputContraseña2" class="input-login" placeholder=".........">
            <div class="separador-login"></div>

            <div id="contenedor-enlaces">
                <a href="./login.php" class="enlaces-login">Iniciar sesion</a>
            </div> <br>

            <input type="submit" value="Registrar" id="enviarLogin" name="enviarLogin">
        </form>

    </div> 
</body>
</html>