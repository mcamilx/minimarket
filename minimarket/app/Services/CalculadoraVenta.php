<?php

namespace App\Services;

use App\Models\ItemCarrito;

/*
 * Único lugar donde se calcula el total de una venta.
 * (El subtotal de cada línea lo calcula ItemCarrito::subtotal()).
 */
class CalculadoraVenta
{
    /** @param ItemCarrito[] $items */
    public function total(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $total += $item->subtotal();
        }

        return $total;
    }
}
