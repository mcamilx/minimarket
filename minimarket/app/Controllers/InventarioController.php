<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sesion;
use App\Services\AuthService;
use App\Services\ProductoService;

/*
 * Consulta del inventario (administrador y cajero).
 */
class InventarioController extends Controller
{
    public function __construct(
        AuthService $auth,
        Sesion $sesion,
        private ProductoService $productos
    ) {
        parent::__construct($auth, $sesion);
    }

    /** GET ruta=inventario */
    public function index(): void
    {
        $buscar = is_string($_GET["buscar"] ?? null) ? trim($_GET["buscar"]) : "";

        $this->vista("inventario/index", [
            "buscar" => $buscar,
            "productos" => $this->productos->buscar($buscar)
        ]);
    }
}
