<?php

namespace App\Services;

use App\Core\Transaccion;
use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\ItemCarrito;
use App\Repositories\Contracts\ProductoRepositoryInterface;
use App\Repositories\Contracts\VentaPendienteRepositoryInterface;
use App\Repositories\Contracts\VentaRepositoryInterface;
use App\Services\Pagos\MetodoPagoFactory;

/*
 * Caso de uso F-01 Registrar venta.
 * Valida el pago y guarda la venta, sus detalles y el descuento de
 * inventario dentro de UNA transacción: o se guarda todo o no se guarda nada.
 */
class VentaService
{
    public function __construct(
        private VentaRepositoryInterface $ventas,
        private ProductoRepositoryInterface $productos,
        private VentaPendienteRepositoryInterface $pendientes,
        private MetodoPagoFactory $metodosPago,
        private CalculadoraVenta $calculadora,
        private Transaccion $transaccion
    ) {
    }

    /** @param ItemCarrito[] $items */
    public function registrar(
        array $items,
        Cliente $cliente,
        string $metodoPago,
        float $montoPagado,
        int $pendienteId = 0
    ): Factura {
        $total = $this->calculadora->total($items);

        // 1. Validar el pago con la estrategia del método elegido.
        $estrategia = $this->metodosPago->crear($metodoPago);
        $estrategia->validarPago($total, $montoPagado);
        $cambio = $estrategia->calcularCambio($total, $montoPagado);

        // 2. Guardar todo dentro de una transacción.
        $ventaId = $this->transaccion->ejecutar(
            function () use ($items, $cliente, $metodoPago, $montoPagado, $total, $pendienteId) {
                $ventaId = $this->ventas->crear($total, $metodoPago, $montoPagado, $cliente);

                foreach ($items as $item) {
                    $stock = $this->productos->obtenerStockBloqueado($item->productoId);

                    if ($stock === null) {
                        throw new ReglaNegocioException("Uno de los productos ya no existe.");
                    }

                    if ($stock < $item->cantidad) {
                        throw new ReglaNegocioException(
                            "No hay suficiente inventario de " . $item->nombre . "."
                        );
                    }

                    $this->ventas->agregarDetalle($ventaId, $item);
                    $this->productos->descontarStock($item->productoId, $item->cantidad);
                }

                if ($pendienteId > 0 && !$this->pendientes->eliminar($pendienteId)) {
                    throw new ReglaNegocioException(
                        "La venta pendiente ya no existe; no se pudo confirmar el cobro."
                    );
                }

                return $ventaId;
            }
        );

        return new Factura(
            $ventaId,
            $items,
            $cliente,
            $metodoPago,
            $montoPagado,
            $total,
            $cambio,
            date("d/m/Y H:i")
        );
    }
}
