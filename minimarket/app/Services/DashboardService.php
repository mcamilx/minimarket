<?php

namespace App\Services;

use App\Repositories\Contracts\ProductoRepositoryInterface;
use App\Repositories\Contracts\VentaRepositoryInterface;

/*
 * Datos del panel principal (tarjetas de resumen).
 */
class DashboardService
{
    public function __construct(
        private ProductoRepositoryInterface $productos,
        private VentaRepositoryInterface $ventas
    ) {
    }

    public function resumen(): array
    {
        return $this->productos->estadisticas() + ["total_ventas" => $this->ventas->contar()];
    }
}
