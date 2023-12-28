<?php
// conexion.php

//CREATE USER 'usr_dino'@'localhost' IDENTIFIED BY 'dino';
//GRANT ALL PRIVILEGES ON dinohtml.* TO 'usr_dino'@'localhost';


$host = 'localhost'; // La dirección del servidor de la base de datos (puede ser diferente si estás trabajando con un servidor remoto)
$dbname = 'dinohtml'; // El nombre de la base de datos que creaste en PhpMyAdmin
$username = 'usr_dino'; // El nombre de usuario de la base de datos
$password = 'dino'; // La contraseña de la base de datos

$conexion = mysqli_connect($host, $username, $password, $dbname);

//echo("conectado");
// Verificar si hay algún error de conexión
if (mysqli_connect_errno()) {
    die('Error al conectar con la base de datos: ' . mysqli_connect_error());
}
?>