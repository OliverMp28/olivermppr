<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/register_login.css">

</head>
<body>
    <div id="contenedor-login">
        <form action="./procesar_register.php" method="POST" id="formularioRegister">
            <h1 id="titulo-login">BIENVENIDO</h1>
            
            <label for="inputUsuario" class="lbl-login">Nombre de Usuario</label> <br>
            <input name="inputUsuario" type="text" id="inputUsuario" class="input-login" placeholder=".........">
            <div class="separador-login"></div>

            <label for="inputNombre" class="lbl-login">Nombres </label> <br>
            <input name="inputNombre" type="text" id="inputNombre" class="input-login" placeholder=".........">
            <div class="separador-login"></div>

            <label for="inputEmail" class="lbl-login">Introduzca su correo electronico </label> <br>
            <input name="inputEmail" type="email" id="inputEmail" class="input-login" placeholder=".........">
            <div class="separador-login"></div>

            <label for="inputPais" class="lbl-login">Pais</label> <br>
            <input name="inputPais" list="lista-paises" id="inputPais" class="input-login" placeholder=".........">
            <datalist id="lista-paises">
                <option value="Afganistán">
                <option value="Albania">
                <option value="Alemania">
                <option value="Andorra">
                <option value="Angola">
                <option value="Antigua y Barbuda">
                <option value="Arabia Saudita">
                <option value="Argelia">
                <option value="Argentina">
                <option value="Armenia">
                <option value="Australia">
                <option value="Austria">
                <option value="Azerbaiyán">
                <option value="Bahamas">
                <option value="Bahréin">
                <option value="Bangladés">
                <option value="Barbados">
                <option value="Belice">
                <option value="Benín">
                <option value="Bielorrusia">
                <option value="Bélgica">
                <option value="Birmania (Myanmar)">
                <option value="Bolivia">
                <option value="Bosnia y Herzegovina">
                <option value="Botsuana">
                <option value="Brasil">
                <option value="Brunéi">
                <option value="Bulgaria">
                <option value="Burkina Faso">
                <option value="Burundi">
                <option value="Burkina Faso">
                <option value="Burundi">

                <!-- Parte 2 -->
                <option value="Bután">
                <option value="Cabo Verde">
                <option value="Camboya">
                <option value="Camerún">
                <option value="Canadá">
                <option value="Catar">
                <option value="Chad">
                <option value="Chile">
                <option value="China">
                <option value="Chipre">
                <option value="Colombia">
                <option value="Comoras">
                <option value="Corea del Norte">
                <option value="Corea del Sur">
                <option value="Costa de Marfil (Côte d'Ivoire)">
                <option value="Costa Rica">
                <option value="Croacia">
                <option value="Cuba">

                <!-- Parte 3 -->
                <option value="Dinamarca">
                <option value="Dominica">
                <option value="Ecuador">
                <option value="Egipto">
                <option value="El Salvador">
                <option value="Emiratos Árabes Unidos">
                <option value="Eritrea">
                <option value="Eslovaquia">
                <option value="Eslovenia">
                <option value="España">
                <option value="Estados Unidos">
                <option value="Estonia">

                <!-- Parte 4 -->
                <option value="Etiopía">
                <option value="Fiyi">
                <option value="Filipinas">
                <option value="Finlandia">
                <option value="Francia">
                <option value="Gabón">
                <option value="Gambia">
                <option value="Georgia">
                <option value="Ghana">
                <option value="Granada">
                <option value="Grecia">
                <option value="Guatemala">
                <option value="Guinea">
                <option value="Guinea-Bisáu">
                <option value="Guinea Ecuatorial">
                <option value="Guyana">

                <!-- Parte 5 -->
                <option value="Haití">
                <option value="Honduras">
                <option value="Hungría">
                <option value="India">
                <option value="Indonesia">
                <option value="Irak">
                <option value="Irán">
                <option value="Irlanda">
                <option value="Islandia">
                <option value="Islas Marshall">
                <option value="Islas Salomón">
                <option value="Israel">
                <option value="Italia">

                <!-- Parte 6 -->
                <option value="Jamaica">
                <option value="Japón">
                <option value="Jordania">
                <option value="Kazajistán">
                <option value="Kenia">
                <option value="Kirguistán">
                <option value="Kiribati">
                <option value="Kuwait">
                <option value="Laos">
                <option value="Lesoto">
                <option value="Letonia">
                <option value="Líbano">
                <option value="Liberia">
                <option value="Libia">
                <option value="Liechtenstein">

                    <!-- Parte 7 -->
                <option value="Lituania">
                <option value="Luxemburgo">
                <option value="Macedonia del Norte">
                <option value="Madagascar">
                <option value="Malasia">
                <option value="Malaui">
                <option value="Maldivas">
                <option value="Malí">
                <option value="Malta">
                <option value="Marruecos">
                <option value="Mauricio">
                <option value="Mauritania">
                <option value="México">
                <option value="Micronesia">
                <option value="Moldavia">

                <!-- Parte 8 -->
                <option value="Mónaco">
                <option value="Mongolia">
                <option value="Montenegro">
                <option value="Mozambique">
                <option value="Namibia">
                <option value="Nauru">
                <option value="Nepal">
                <option value="Nicaragua">
                <option value="Níger">
                <option value="Nigeria">
                <option value="Noruega">
                <option value="Nueva Zelanda">
                <option value="Omán">
                <option value="Países Bajos">
                <option value="Pakistán">

                <!-- Parte 9 -->
                <option value="Palaos">
                <option value="Palestina">
                <option value="Panamá">
                <option value="Papúa Nueva Guinea">
                <option value="Paraguay">
                <option value="Perú">
                <option value="Polonia">
                <option value="Portugal">
                <option value="Reino Unido">
                <option value="República Centroafricana">
                <option value="República Checa">
                <option value="República del Congo">
                <option value="República Democrática del Congo">
                <option value="República Dominicana">
                <option value="Ruanda">
                <option value="Rumania">

                <!-- Parte 10 -->
                <option value="Rusia">
                <option value="Samoa">
                <option value="San Cristóbal y Nieves">
                <option value="San Marino">
                <option value="Santa Lucía">
                <option value="San Vicente y las Granadinas">
                <option value="Senegal">
                <option value="Serbia">
                <option value="Seychelles">
                <option value="Sierra Leona">
                <option value="Singapur">
                <option value="Siria">
                <option value="Somalia">
                <option value="Sri Lanka">
                <option value="Suazilandia">
                <option value="Sudáfrica">
                <option value="Sudán">
                <option value="Sudán del Sur">
                <option value="Suecia">
                <option value="Suiza">
                <option value="Surinam">

                <!-- Parte final -->
                <option value="Tailandia">
                <option value="Taiwán">
                <option value="Tanzania">
                <option value="Tayikistán">
                <option value="Timor Oriental">
                <option value="Togo">
                <option value="Tonga">
                <option value="Trinidad y Tobago">
                <option value="Túnez">
                <option value="Turkmenistán">
                <option value="Turquía">
                <option value="Tuvalu">
                <option value="Ucrania">
                <option value="Uganda">
                <option value="Uruguay">
                <option value="Uzbekistán">
                <option value="Vanuatu">
                <option value="Vaticano">
                <option value="Venezuela">
                <option value="Vietnam">
                <option value="Yemen">
                <option value="Yibuti">
                <option value="Zambia">
                <option value="Zimbabue">
            </datalist>
            <div class="separador-login"></div>

            <label for="inputContraseña" class="lbl-login">Contraseña</label> <br>
            <input name="inputContraseña" type="password" id="inputContraseña" class="input-login" placeholder=".........">
            <div class="separador-login"></div>

            <!-- <label for="inputContraseña2" class="lbl-login">Vuelva a introducir contraseña</label> <br>
            <input name="inputContraseña2" type="password" id="inputContraseña2" class="input-login" placeholder=".........">
            <div class="separador-login"></div> -->

            <div id="contenedor-enlaces">
                <a href="./login.php" class="enlaces-login">Iniciar sesion</a>
            </div> <br>

            <input type="submit" value="Registrar" id="enviarLogin" name="enviarLogin">
            <!--
                nombres
                nombre de usuario
                correo
                pais
                contraseña
                vuelva a introducir contraseña                
            -->
        </form>

        <script>
            var enviar = document.getElementById("enviarLogin");
            enviar.addEventListener("click", function(){
               /* var nombre = document.getElementById("inputNombre").value;
                var comentario = document.getElementById("comentario").value;

                if(nombre == ""){
                    alert("debe introducir un nombre");
                    throw new Error("No ah insertado un nombre");
                }
                if(comentario == ""){
                    alert("debe introducir un comentario");
                    throw new Error("No ah insertado un comentario");
                } */

                var formulario = document.getElementById("formularioRegister");
                formulario.submit();
                alert("Se ah enviado tu comentario");
            });
        </script>
    </div> 
</body>
</html>