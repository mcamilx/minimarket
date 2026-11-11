<?php

namespace App\Core;

use PDO;

/*
 * Patrón Singleton: garantiza que toda la aplicación use UNA sola
 * conexión a la base de datos durante cada solicitud.
 */
class Database
{
    private static ?PDO $conexion = null;
    private static array $configuracion = [];

    /** El constructor es privado: nadie puede hacer "new Database()". */
    private function __construct()
    {
    }

    public static function configurar(array $configuracion): void
    {
        self::$configuracion = $configuracion;
    }

    /** Devuelve la única conexión; la crea la primera vez que se pide. */
    public static function obtenerConexion(): PDO
    {
        if (self::$conexion === null) {
            $config = self::$configuracion;
            $dsn = "mysql:host=" . $config["servidor"] .
                ";dbname=" . $config["nombre"] .
                ";charset=" . $config["charset"];

            self::$conexion = new PDO(
                $dsn,
                $config["usuario"],
                $config["contrasena"],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        }

        return self::$conexion;
    }

    /** Permite usar otra conexión (por ejemplo, una base de datos de pruebas). */
    public static function establecerConexion(PDO $conexion): void
    {
        self::$conexion = $conexion;
    }
}
