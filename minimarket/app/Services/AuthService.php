<?php

namespace App\Services;

use App\Core\Sesion;
use App\Models\Usuario;

/*
 * Maneja el ingreso por perfil (administrador o cajero) y la salida del sistema.
 */
class AuthService
{
    public function __construct(private Sesion $sesion)
    {
    }

    public function iniciarSesion(string $rol): bool
    {
        $usuario = Usuario::desdeRol($rol);

        if ($usuario === null) {
            return false;
        }

        $this->sesion->regenerar();
        $this->sesion->guardar("usuario", ["nombre" => $usuario->nombre, "rol" => $usuario->rol]);

        return true;
    }

    public function cerrarSesion(): void
    {
        $this->sesion->destruir();
    }

    public function usuarioActual(): ?Usuario
    {
        $datos = $this->sesion->obtener("usuario");

        if (!is_array($datos) || !isset($datos["rol"])) {
            return null;
        }

        return Usuario::desdeRol($datos["rol"]);
    }
}
