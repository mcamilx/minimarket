<?php

namespace App\Models;

/*
 * Modelo: venta guardada "en espera" que se puede retomar después.
 */
class VentaPendiente
{
    public function __construct(
        public readonly int $id,
        public readonly string $fechaHora,
        public readonly float $total,
        public readonly int $cantidadProductos
    ) {
    }

    public static function desdeFila(array $fila): self
    {
        return new self(
            (int) $fila["id"],
            $fila["fecha_hora"],
            (float) $fila["total"],
            (int) $fila["productos"]
        );
    }
}
