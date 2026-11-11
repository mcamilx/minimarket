<?php

namespace App\Services;

use App\Core\Sesion;
use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use App\Models\ItemCarrito;
use App\Repositories\Contracts\ProductoRepositoryInterface;

/*
 * Maneja la venta en curso (el "carrito"), que vive en la sesión del cajero.
 */
class CarritoService
{
    public function __construct(
        private Sesion $sesion,
        private ProductoRepositoryInterface $productos,
        private CalculadoraVenta $calculadora
    ) {
    }

    /** @return ItemCarrito[] indexados por id de producto */
    public function items(): array
    {
        $items = [];

        foreach ($this->sesion->obtener("carrito", []) as $datos) {
            $item = ItemCarrito::desdeArreglo($datos);
            $items[$item->productoId] = $item;
        }

        return $items;
    }

    public function estaVacio(): bool
    {
        return count($this->items()) === 0;
    }

    public function total(): float
    {
        return $this->calculadora->total($this->items());
    }

    public function agregar(int $productoId, int $cantidad): void
    {
        $producto = $this->productos->buscarPorId($productoId);

        if ($producto === null) {
            throw new ReglaNegocioException("El producto no existe.");
        }

        if ($producto->estaDescontinuado()) {
            throw new ReglaNegocioException("El producto está descontinuado y ya no se vende.");
        }

        if ($cantidad <= 0) {
            throw new ReglaNegocioException("La cantidad debe ser mayor que cero.");
        }

        if ($cantidad > $producto->cantidad) {
            throw new ReglaNegocioException("No hay suficientes unidades disponibles.");
        }

        $items = $this->items();
        $cantidadEnCarrito = isset($items[$productoId]) ? $items[$productoId]->cantidad : 0;

        if ($cantidadEnCarrito + $cantidad > $producto->cantidad) {
            throw new ReglaNegocioException("La cantidad solicitada supera el inventario disponible.");
        }

        $items[$productoId] = ItemCarrito::desdeProducto($producto, $cantidadEnCarrito + $cantidad);
        $this->reemplazar($items);
    }

    public function eliminar(int $productoId): void
    {
        $items = $this->items();
        unset($items[$productoId]);
        $this->reemplazar($items);
    }

    /** @param ItemCarrito[] $items */
    public function reemplazar(array $items): void
    {
        $datos = [];

        foreach ($items as $item) {
            $datos[$item->productoId] = $item->aArreglo();
        }

        $this->sesion->guardar("carrito", $datos);
    }

    /** Número de la venta pendiente que se está retomando (0 si no hay). */
    public function ventaPendienteId(): int
    {
        return (int) $this->sesion->obtener("venta_pendiente_id", 0);
    }

    public function marcarVentaPendiente(int $id): void
    {
        $this->sesion->guardar("venta_pendiente_id", $id);
    }

    public function recordarCliente(Cliente $cliente): void
    {
        $this->sesion->guardar("datos_cliente", $cliente->aArreglo());
    }

    public function clienteRecordado(): Cliente
    {
        return Cliente::desdeArreglo($this->sesion->obtener("datos_cliente", []));
    }

    /** Deja la venta en curso vacía. */
    public function vaciar(): void
    {
        $this->sesion->guardar("carrito", []);
        $this->sesion->eliminar("venta_pendiente_id", "datos_cliente");
    }
}
