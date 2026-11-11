<?php

namespace App\Models;

/*
 * Modelo: una venta del historial. Puede ser una venta aprobada
 * (tabla ventas) o una venta en espera (tabla ventas_pendientes).
 */
class Venta
{
    public const APROBADA = "APROBADA";
    public const PENDIENTE = "PENDIENTE";

    public function __construct(
        public readonly int $id,
        public readonly string $fechaHora,
        public readonly float $total,
        public readonly string $metodoPago,
        public readonly float $montoPagado,
        public readonly string $estado,
        public readonly Cliente $cliente,
        public readonly bool $esPendiente
    ) {
    }

    public static function desdeFila(array $fila): self
    {
        return new self(
            (int) $fila["id"],
            $fila["fecha_hora"],
            (float) $fila["total"],
            (string) $fila["metodo_pago"],
            (float) $fila["monto_pagado"],
            $fila["estado"],
            new Cliente(
                (string) $fila["cliente_tipo_documento"],
                (string) $fila["cliente_documento"],
                (string) $fila["cliente_nombre"],
                (string) $fila["cliente_telefono"]
            ),
            (bool) $fila["es_pendiente"]
        );
    }

    /** Número que se muestra: 15 para una venta y P-3 para una pendiente. */
    public function numero(): string
    {
        return $this->esPendiente ? "P-" . $this->id : (string) $this->id;
    }
}
