<?php

namespace App\Models;

/*
 * Modelo: persona que usa el sistema (administrador o cajero).
 */
class Usuario
{
    public const ADMIN = "admin";
    public const CAJERO = "cajero";

    public const ROLES = [
        self::ADMIN => "Administrador",
        self::CAJERO => "Cajero"
    ];

    public function __construct(
        public readonly string $nombre,
        public readonly string $rol
    ) {
    }

    public static function desdeRol(string $rol): ?self
    {
        if (!isset(self::ROLES[$rol])) {
            return null;
        }

        return new self(self::ROLES[$rol], $rol);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === self::ADMIN;
    }

    public function esCajero(): bool
    {
        return $this->rol === self::CAJERO;
    }
}
