<?php

namespace App\Validators;

use App\Exceptions\ValidacionException;
use App\Models\Producto;

/*
 * Valida los datos del formulario de registro de productos y,
 * si son correctos, construye el modelo Producto.
 */
class ProductoValidator
{
    public function crearProducto(string $codigo, string $nombre, mixed $precio, mixed $cantidad): Producto
    {
        $precio = filter_var($precio, FILTER_VALIDATE_FLOAT);
        $cantidad = filter_var($cantidad, FILTER_VALIDATE_INT);

        if (
            $codigo === "" ||
            $nombre === "" ||
            $precio === false ||
            $precio < 0 ||
            $cantidad === false ||
            $cantidad < 0
        ) {
            throw new ValidacionException([
                "Completa los datos con un código, nombre, precio y cantidad válidos."
            ]);
        }

        return new Producto(null, $codigo, $nombre, $precio, $cantidad);
    }
}
