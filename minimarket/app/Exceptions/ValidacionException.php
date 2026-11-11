<?php

namespace App\Exceptions;

/*
 * Error de validación de un formulario. Guarda la lista de errores
 * para mostrarlos todos al mismo tiempo.
 */
class ValidacionException extends ReglaNegocioException
{
    public function __construct(private array $errores)
    {
        parent::__construct(implode(" ", $errores));
    }

    public function errores(): array
    {
        return $this->errores;
    }
}
