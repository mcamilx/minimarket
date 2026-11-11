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
        public readonly int $cantidad,
        public readonly bool $activo = true
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
            (int) $fila["cantidad"],
            (bool) ($fila["activo"] ?? true)
        );
    }

    /** Disponible = se sigue vendiendo y tiene unidades. */
    public function estaDisponible(): bool
    {
        return $this->activo && $this->cantidad > 0;
    }

    public function estaDescontinuado(): bool
    {
        return !$this->activo;
    }

    /** Estado que se muestra en pantalla. */
    public function estado(): string
    {
        if ($this->estaDescontinuado()) {
            return "Descontinuado";
        }

        return $this->cantidad > 0 ? "Disponible" : "Agotado";
    }
}
