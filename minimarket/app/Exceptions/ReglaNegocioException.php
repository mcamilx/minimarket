<?php

namespace App\Exceptions;

use RuntimeException;

/*
 * Error esperado del negocio (pago insuficiente, sin inventario, etc.).
 * Su mensaje se le puede mostrar directamente al usuario.
 */
class ReglaNegocioException extends RuntimeException
{
}
