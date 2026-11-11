<?php

namespace App\Services\Pagos;

use App\Exceptions\ReglaNegocioException;

/*
 * Patrón Factory: crea la estrategia de pago a partir del texto que llega
 * del formulario. Para agregar un método nuevo (por ejemplo Nequi) solo se
 * crea su clase y se agrega una línea aquí; el resto del sistema no cambia.
 */
class MetodoPagoFactory
{
    public function crear(string $metodo): MetodoPagoStrategy
    {
        return match ($metodo) {
            "Efectivo" => new PagoEfectivoStrategy(),
            "Tarjeta" => new PagoTarjetaStrategy(),
            default => throw new ReglaNegocioException(
                "Método de pago no válido. Regrese a la venta."
            )
        };
    }
}
