<?php

namespace App\Services\Pagos;

use App\Exceptions\ReglaNegocioException;

class PagoEfectivoStrategy implements MetodoPagoStrategy
{
    public function nombre(): string
    {
        return "Efectivo";
    }

    public function validarPago(float $total, float $montoPagado): void
    {
        if ($montoPagado < $total) {
            throw new ReglaNegocioException(
                "El valor pagado es inferior al total de la compra. Regrese a la venta."
            );
        }
    }

    /** En efectivo se devuelve la diferencia. */
    public function calcularCambio(float $total, float $montoPagado): float
    {
        return $montoPagado - $total;
    }
}
