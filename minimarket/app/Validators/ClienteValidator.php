<?php

namespace App\Validators;

use App\Models\Cliente;

/*
 * Valida los datos opcionales del cliente.
 * Si todos los campos están vacíos, la venta es para "consumidor final" y es válida.
 */
class ClienteValidator
{
    /** @return string[] Lista de errores; vacía si los datos son válidos. */
    public function validar(Cliente $cliente): array
    {
        if ($cliente->estaVacio()) {
            return [];
        }

        $errores = [];

        if (!array_key_exists($cliente->tipoDocumento, Cliente::TIPOS_DOCUMENTO)) {
            $errores[] = "Selecciona un tipo de documento válido.";
        }

        if (!preg_match('/^[A-Za-z0-9.-]{5,30}$/', $cliente->documento)) {
            $errores[] = "El documento debe tener entre 5 y 30 caracteres alfanuméricos.";
        }

        if (
            strlen($cliente->nombre) < 2 ||
            strlen($cliente->nombre) > 100 ||
            preg_match('/[\x00-\x1F\x7F]/', $cliente->nombre)
        ) {
            $errores[] = "Ingresa un nombre de cliente válido (máximo 100 caracteres).";
        }

        $digitosTelefono = preg_match_all('/[0-9]/', $cliente->telefono);
        if (
            !preg_match('/^[0-9+() -]{7,20}$/', $cliente->telefono) ||
            $digitosTelefono < 7 ||
            $digitosTelefono > 15
        ) {
            $errores[] = "El teléfono debe tener entre 7 y 15 dígitos.";
        }

        return $errores;
    }
}
