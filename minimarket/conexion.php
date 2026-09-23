<?php

$servidor = "localhost";
$usuario = "minimarket_user";
$contrasena = "Minimarket123*";
$base_datos = "minimarket_db";

$conexion = new mysqli(
    $servidor,
    $usuario,
    $contrasena,
    $base_datos
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

?>