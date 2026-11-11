<?php

namespace App\Models;

/*
 * Modelo: un producto agregado a la venta en curso, con su cantidad.
 */
class ItemCarrito
{
    public function __construct(
        public readonly int $productoId,
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly float $precio,
        public readonly int $cantidad
    ) {
    }

    /** Regla de negocio: subtotal = precio x cantidad. */
    public function subtotal(): float
    {
        return $this->precio * $this->cantidad;
    }

    public function conCantidad(int $cantidad): self
    {
        return new self($this->productoId, $this->codigo, $this->nombre, $this->precio, $cantidad);
    }

    public static function desdeProducto(Producto $producto, int $cantidad): self
    {
        return new self($producto->id, $producto->codigo, $producto->nombre, $producto->precio, $cantidad);
    }

    /** La sesión guarda arreglos simples; estos dos métodos convierten en ambos sentidos. */
    public function aArreglo(): array
    {
        return [
            "producto_id" => $this->productoId,
            "codigo" => $this->codigo,
            "nombre" => $this->nombre,
            "precio" => $this->precio,
            "cantidad" => $this->cantidad
        ];
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            (int) $datos["producto_id"],
            $datos["codigo"],
            $datos["nombre"],
            (float) $datos["precio"],
            (int) $datos["cantidad"]
        );
    }
}
