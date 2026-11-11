<?php

namespace App\Repositories\Contracts;

use App\Models\Cliente;
use App\Models\ItemCarrito;
use App\Models\Venta;

interface VentaRepositoryInterface
{
    public function crear(float $total, string $metodoPago, float $montoPagado, Cliente $cliente): int;

    public function agregarDetalle(int $ventaId, ItemCarrito $item): void;

    public function contar(): int;

    /** @return Venta[] Ventas aprobadas y pendientes, filtradas si hay búsqueda. */
    public function historial(string $busqueda = ""): array;
}
