<?php

namespace App\Services\Pagos;

use App\Exceptions\ReglaNegocioException;

class PagoTarjetaStrategy implements MetodoPagoStrategy
{
    public function nombre(): string
    {
        return "Tarjeta";
    }

    public function validarPago(float $total, float $montoPagado): void
    {
        if ($montoPagado < $total) {
            throw new ReglaNegocioException(
                "El valor pagado es inferior al total de la compra. Regrese a la venta."
            );
        }
    }

    /** Con tarjeta no hay cambio. */
    public function calcularCambio(float $total, float $montoPagado): float
    {
        return 0;
    }
}
