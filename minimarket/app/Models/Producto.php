<?php

namespace App\Models;

/*
 * Modelo: producto del minimarket (tabla productos).
 */
class Producto
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly float $precio,
        public readonly int $cantidad
    ) {
    }

    /** Crea un Producto a partir de una fila de la base de datos. */
    public static function desdeFila(array $fila): self
    {
        return new self(
            (int) $fila["id"],
            $fila["codigo"],
            $fila["nombre"],
            (float) $fila["precio"],
            (int) $fila["cantidad"]
        );
    }

    public function estaDisponible(): bool
    {
        return $this->cantidad > 0;
    }
}
