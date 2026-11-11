<?php
/*
 * Configuración general del sistema.
 * Todos los datos que pueden cambiar entre equipos (servidor, usuario,
 * contraseña) se definen aquí y no dentro del código.
 */

return [
    "base_datos" => [
        "servidor" => "localhost",
        "nombre" => "minimarket_db",
        "usuario" => "minimarket_user",
        "contrasena" => "Minimarket123*",
        "charset" => "utf8mb4"
    ],
    "app" => [
        "nombre" => "Sistema Minimarket",
        "zona_horaria" => "America/Bogota"
    ]
];
