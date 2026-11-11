<?php

namespace App\Core;

use App\Services\AuthService;

/*
 * Enrutador: recibe la ruta pedida (index.php?ruta=...) y el método HTTP,
 * comprueba los permisos y llama al método del controlador que corresponde.
 */
class Router
{
    private array $rutas = [];

    public function __construct(private Contenedor $contenedor)
    {
    }

    public function get(string $ruta, array $accion, array $roles = []): void
    {
        $this->rutas["GET"][$ruta] = [$accion, $roles];
    }

    public function post(string $ruta, array $accion, array $roles = []): void
    {
        $this->rutas["POST"][$ruta] = [$accion, $roles];
    }

    public function despachar(string $metodo, string $ruta): void
    {
        $ruta = trim($ruta, "/");
        $ruta = $ruta === "" ? "inicio" : $ruta;

        if (!isset($this->rutas[$metodo][$ruta])) {
            $this->mostrarError(404, "La página solicitada no existe.");
            return;
        }

        [[$claseControlador, $metodoControlador], $roles] = $this->rutas[$metodo][$ruta];

        if (count($roles) > 0 && !$this->tienePermiso($roles)) {
            return;
        }

        $controlador = $this->contenedor->obtener($claseControlador);
        $controlador->$metodoControlador();
    }

    private function tienePermiso(array $roles): bool
    {
        /** @var AuthService $auth */
        $auth = $this->contenedor->obtener(AuthService::class);
        $usuario = $auth->usuarioActual();

        if ($usuario === null) {
            header("Location: " . url("inicio"));
            exit;
        }

        if (!in_array($usuario->rol, $roles, true)) {
            $this->mostrarError(403, "No tienes permisos para acceder a esta sección.");
            return false;
        }

        return true;
    }

    private function mostrarError(int $codigo, string $mensaje): void
    {
        http_response_code($codigo);

        /** @var AuthService $auth */
        $auth = $this->contenedor->obtener(AuthService::class);

        Vista::renderizar("errores/mensaje", [
            "titulo" => $codigo === 404 ? "Página no encontrada" : "Acceso denegado",
            "mensaje" => $mensaje,
            "enlace" => url("inicio"),
            "textoEnlace" => "Volver al inicio",
            "usuarioActual" => $auth->usuarioActual(),
            "rutaActual" => "",
            "flash" => null
        ]);
    }
}
