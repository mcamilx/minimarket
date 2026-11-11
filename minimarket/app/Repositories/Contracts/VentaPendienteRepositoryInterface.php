<?php

namespace App\Repositories\Contracts;

use App\Models\ItemCarrito;
use App\Models\VentaPendiente;

interface VentaPendienteRepositoryInterface
{
    /** @return VentaPendiente[] */
    public function listar(): array;

    public function existe(int $id): bool;

    /** Bloquea la venta pendiente hasta terminar la transacción. */
    public function bloquear(int $id): bool;

    /** @return ItemCarrito[] */
    public function obtenerItems(int $id): array;

    public function crear(float $total): int;

    public function actualizarTotal(int $id, float $total): void;

    public function eliminarItems(int $id): void;

    public function agregarItem(int $id, ItemCarrito $item): void;

    /** Devuelve true si eliminó exactamente una venta pendiente. */
    public function eliminar(int $id): bool;
}
