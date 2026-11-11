<?php

namespace App\Models;

/*
 * Modelo: datos opcionales del cliente que pide factura a su nombre.
 */
class Cliente
{
    public const TIPOS_DOCUMENTO = [
        "CC" => "Cédula de ciudadanía",
        "CE" => "Cédula de extranjería",
        "NIT" => "NIT",
        "Pasaporte" => "Pasaporte"
    ];

    public function __construct(
        public readonly string $tipoDocumento = "",
        public readonly string $documento = "",
        public readonly string $nombre = "",
        public readonly string $telefono = ""
    ) {
    }

    /** True cuando el cajero no escribió ningún dato del cliente. */
    public function estaVacio(): bool
    {
        return $this->tipoDocumento === "" &&
            $this->documento === "" &&
            $this->nombre === "" &&
            $this->telefono === "";
    }

    public function aArreglo(): array
    {
        return [
            "tipo_documento" => $this->tipoDocumento,
            "documento" => $this->documento,
            "nombre" => $this->nombre,
            "telefono" => $this->telefono
        ];
    }

    public static function desdeArreglo(array $datos): self
    {
        return new self(
            $datos["tipo_documento"] ?? "",
            $datos["documento"] ?? "",
            $datos["nombre"] ?? "",
            $datos["telefono"] ?? ""
        );
    }
}
