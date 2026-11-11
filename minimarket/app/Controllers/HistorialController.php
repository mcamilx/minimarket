<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sesion;
use App\Services\AuthService;
use App\Services\HistorialService;

/*
 * Historial de ventas (administrador y cajero; solo el administrador busca).
 */
class HistorialController extends Controller
{
    public function __construct(
        AuthService $auth,
        Sesion $sesion,
        private HistorialService $historial
    ) {
        parent::__construct($auth, $sesion);
    }

    /** GET ruta=historial */
    public function index(): void
    {
        $usuario = $this->auth->usuarioActual();
        $buscar = is_string($_GET["buscar"] ?? null) ? trim($_GET["buscar"]) : "";

        $this->vista("historial/index", [
            "buscar" => $usuario->esAdministrador() ? $buscar : "",
            "ventas" => $this->historial->listar($usuario, $buscar)
        ]);
    }
}
