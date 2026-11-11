<?php

namespace App\Services\Pagos;

/*
 * Patrón Strategy: cada método de pago tiene su propia forma de
 * validar el pago y de calcular el cambio.
 */
interface MetodoPagoStrategy
{
    public function nombre(): string;

    public function validarPago(float $total, float $montoPagado): void;

    public function calcularCambio(float $total, float $montoPagado): float;
}
