<?php

namespace App\Models;

/*
 * Modelo: resultado de una venta registrada, con todo lo que muestra la factura.
 */
class Factura
{
    /** @param ItemCarrito[] $items */
    public function __construct(
        public readonly int $ventaId,
        public readonly array $items,
        public readonly Cliente $cliente,
        public readonly string $metodoPago,
        public readonly float $montoPagado,
        public readonly float $total,
        public readonly float $cambio,
        public readonly string $fecha
    ) {
    }
}
