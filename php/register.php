<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daino - Registrarse</title>
    <style>
        .back-button {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 20px;
            padding: 10px 0;
            background-color: rgba(0, 0, 0, 0.3); /* Fondo oscuro para contraste */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .back-button:hover {
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>
    <link rel="stylesheet" href="../css/register_login.css">

</head>
<body>
    <div id="contenedor-login">
        <form action="../controladores_php/procesar_register.php" method="POST" id="formularioRegister">
            <h1 id="titulo-login">BIENVENIDO</h1>
            <?php 
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            if (isset($_SESSION['error_message'])):
            ?>
                <div class="alert alert-danger" style="color: #D8000C; background-color: #FFD2D2; margin-bottom: 15px; padding: 10px; border-radius: 5px;"><?php echo $_SESSION['error_message']; ?></div>
            <?php 
                unset($_SESSION['error_message']);
            endif;
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
        <a href="./index_publico.php" class="back-button">Volver al inicio</a>

    </div> 
    <!-- <?php include 'footer.php'; ?> -->
</body>
</html>