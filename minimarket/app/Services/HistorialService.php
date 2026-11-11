<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Venta;
use App\Repositories\Contracts\VentaRepositoryInterface;

/*
 * Consulta del historial de ventas. Solo el administrador puede buscar.
 */
class HistorialService
{
    public function __construct(private VentaRepositoryInterface $ventas)
    {
    }

    /** @return Venta[] */
    public function listar(Usuario $usuario, string $busqueda): array
    {
        if (!$usuario->esAdministrador()) {
            $busqueda = "";
        }

        return $this->ventas->historial($busqueda);
    }
}
