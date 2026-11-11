<?php

namespace App\Core;

use App\Services\AuthService;

/*
 * Clase base de todos los controladores.
 * Reúne lo que todos necesitan: mostrar una vista, redirigir y leer
 * datos del formulario.
 */
abstract class Controller
{
    public function __construct(
        protected AuthService $auth,
        protected Sesion $sesion
    ) {
    }

    protected function vista(string $nombre, array $datos = [], int $codigoHttp = 200): void
    {
        http_response_code($codigoHttp);

        $datos["usuarioActual"] = $this->auth->usuarioActual();
        $datos["rutaActual"] = $_GET["ruta"] ?? "inicio";
        $datos["flash"] = $this->sesion->tomarFlash();

        Vista::renderizar($nombre, $datos);
    }

    protected function redirigir(string $ruta = "inicio"): void
    {
        header("Location: " . url($ruta));
        exit;
    }

    /** Lee un texto del formulario (POST) y le quita los espacios de los extremos. */
    protected function texto(string $campo): string
    {
        $valor = $_POST[$campo] ?? null;

        return is_string($valor) ? trim($valor) : "";
    }

    protected function entero(string $campo): int
    {
        return (int) ($_POST[$campo] ?? 0);
    }
}
