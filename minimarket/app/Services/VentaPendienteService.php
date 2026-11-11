<?php

namespace App\Services;

use App\Core\Transaccion;
use App\Exceptions\ReglaNegocioException;
use App\Models\VentaPendiente;
use App\Repositories\Contracts\VentaPendienteRepositoryInterface;

/*
 * Permite guardar una venta "en espera" y retomarla después.
 */
class VentaPendienteService
{
    public function __construct(
        private VentaPendienteRepositoryInterface $pendientes,
        private CarritoService $carrito,
        private Transaccion $transaccion
    ) {
    }

    /** @return VentaPendiente[] */
    public function listar(): array
    {
        return $this->pendientes->listar();
    }

    /** Guarda el carrito como venta pendiente y devuelve su número. */
    public function guardar(): int
    {
        if ($this->carrito->estaVacio()) {
            throw new ReglaNegocioException("Agrega productos antes de guardar una venta en espera.");
        }

        $items = $this->carrito->items();
        $total = $this->carrito->total();
        $pendienteId = $this->carrito->ventaPendienteId();

        $pendienteId = $this->transaccion->ejecutar(function () use ($items, $total, $pendienteId) {
            if ($pendienteId > 0) {
                if (!$this->pendientes->bloquear($pendienteId)) {
                    throw new ReglaNegocioException("La venta pendiente ya no existe.");
                }

                $this->pendientes->eliminarItems($pendienteId);
                $this->pendientes->actualizarTotal($pendienteId, $total);
            } else {
                $pendienteId = $this->pendientes->crear($total);
            }

            foreach ($items as $item) {
                $this->pendientes->agregarItem($pendienteId, $item);
            }

            return $pendienteId;
        });

        $this->carrito->vaciar();

        return $pendienteId;
    }

    public function retomar(int $pendienteId): void
    {
        if (!$this->carrito->estaVacio()) {
            throw new ReglaNegocioException("Termina o guarda la venta actual antes de retomar otra.");
        }

        if ($pendienteId <= 0) {
            throw new ReglaNegocioException("Selecciona una venta pendiente válida.");
        }

        if (!$this->pendientes->existe($pendienteId)) {
            throw new ReglaNegocioException("La venta pendiente ya no existe.");
        }

        $items = $this->pendientes->obtenerItems($pendienteId);

        if (count($items) === 0) {
            throw new ReglaNegocioException("La venta pendiente no tiene productos.");
        }

        $this->carrito->reemplazar($items);
        $this->carrito->marcarVentaPendiente($pendienteId);
    }
}
