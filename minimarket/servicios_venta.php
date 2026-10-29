<?php
/*
 * Capa de servicios de la funcionalidad F-01 Registrar venta.
 *
 * Antes de esta refactorización, factura.php validaba al cliente, calculaba
 * el total, ejecutaba todas las consultas SQL y además pintaba la factura.
 * Aquí se separan esas responsabilidades:
 *
 *   - CalculadoraVenta       -> reglas de cálculo (subtotal y total).
 *   - ValidadorCliente       -> lectura y validación de los datos del cliente.
 *   - VentaRepository        -> acceso a la base de datos (SQL).
 *   - RegistrarVentaService  -> coordina la transacción de registrar la venta.
 *
 * factura.php queda solo como controlador + vista.
 */

require_once "patrones_diseno.php";

/* ===================== DOMINIO: CÁLCULOS ===================== */

class CalculadoraVenta
{
    public static function subtotal(array $item): float
    {
        return $item["precio"] * $item["cantidad"];
    }

    public static function total(array $carrito): float
    {
        $total = 0;

        foreach ($carrito as $item) {
            $total += self::subtotal($item);
        }

        return $total;
    }
}

/* ===================== VALIDACIÓN DEL CLIENTE ===================== */

class ValidadorCliente
{
    private const TIPOS_DOCUMENTO_VALIDOS = ["CC", "CE", "NIT", "Pasaporte"];

    /** Lee los cuatro campos del cliente desde $_POST y los limpia. */
    public static function leerDesdeFormulario(array $post): array
    {
        $campos = [
            "cliente_tipo_documento",
            "cliente_documento",
            "cliente_nombre",
            "cliente_telefono"
        ];
        $datos = [];

        foreach ($campos as $campo) {
            $datos[$campo] = trim(
                is_string($post[$campo] ?? null) ? $post[$campo] : ""
            );
        }

        return $datos;
    }

    /**
     * Devuelve la lista de errores. Lista vacía = datos válidos.
     * Si todos los campos están vacíos, el cliente es opcional y no hay errores.
     */
    public static function validar(array $datosCliente): array
    {
        $camposCompletos = array_filter($datosCliente, function ($valor) {
            return $valor !== "";
        });
        $errores = [];

        if (count($camposCompletos) === 0) {
            return $errores;
        }

        if (!in_array($datosCliente["cliente_tipo_documento"], self::TIPOS_DOCUMENTO_VALIDOS, true)) {
            $errores[] = "Selecciona un tipo de documento válido.";
        }

        if (!preg_match('/^[A-Za-z0-9.-]{5,30}$/', $datosCliente["cliente_documento"])) {
            $errores[] = "El documento debe tener entre 5 y 30 caracteres alfanuméricos.";
        }

        if (
            strlen($datosCliente["cliente_nombre"]) < 2 ||
            strlen($datosCliente["cliente_nombre"]) > 100 ||
            preg_match('/[\x00-\x1F\x7F]/', $datosCliente["cliente_nombre"])
        ) {
            $errores[] = "Ingresa un nombre de cliente válido (máximo 100 caracteres).";
        }

        $digitosTelefono = preg_match_all('/[0-9]/', $datosCliente["cliente_telefono"]);
        if (
            !preg_match('/^[0-9+() -]{7,20}$/', $datosCliente["cliente_telefono"]) ||
            $digitosTelefono < 7 ||
            $digitosTelefono > 15
        ) {
            $errores[] = "El teléfono debe tener entre 7 y 15 dígitos.";
        }

        return $errores;
    }
}

/* ===================== PERSISTENCIA ===================== */

class VentaRepository
{
    private mysqli $conexion;

    public function __construct(mysqli $conexion)
    {
        $this->conexion = $conexion;
    }

    public function iniciarTransaccion(): void
    {
        $this->conexion->begin_transaction();
    }

    public function confirmar(): void
    {
        $this->conexion->commit();
    }

    public function revertir(): void
    {
        $this->conexion->rollback();
    }

    public function insertarVenta(
        float $total,
        string $metodoPago,
        float $montoPagado,
        array $datosCliente
    ): int {
        $stmt = $this->conexion->prepare(
            "INSERT INTO ventas
            (total, metodo_pago, monto_pagado, estado,
             cliente_tipo_documento, cliente_documento,
             cliente_nombre, cliente_telefono)
            VALUES (?, ?, ?, 'APROBADA', ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "dsdssss",
            $total,
            $metodoPago,
            $montoPagado,
            $datosCliente["cliente_tipo_documento"],
            $datosCliente["cliente_documento"],
            $datosCliente["cliente_nombre"],
            $datosCliente["cliente_telefono"]
        );
        $stmt->execute();

        return $this->conexion->insert_id;
    }

    /** Bloquea la fila del producto (FOR UPDATE) y devuelve su stock, o null si no existe. */
    public function obtenerStockBloqueado(int $productoId): ?int
    {
        $stmt = $this->conexion->prepare(
            "SELECT cantidad
             FROM productos
             WHERE id = ?
             FOR UPDATE"
        );
        $stmt->bind_param("i", $productoId);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        return $fila ? (int) $fila["cantidad"] : null;
    }

    public function insertarDetalle(
        int $ventaId,
        int $productoId,
        int $cantidad,
        float $precioUnitario,
        float $subtotal
    ): void {
        $stmt = $this->conexion->prepare(
            "INSERT INTO detalle_venta
            (venta_id, producto_id, cantidad,
             precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iiidd",
            $ventaId,
            $productoId,
            $cantidad,
            $precioUnitario,
            $subtotal
        );
        $stmt->execute();
    }

    public function descontarInventario(int $productoId, int $cantidad): void
    {
        $stmt = $this->conexion->prepare(
            "UPDATE productos
             SET cantidad = cantidad - ?
             WHERE id = ?"
        );
        $stmt->bind_param("ii", $cantidad, $productoId);
        $stmt->execute();
    }

    /** Devuelve true si se eliminó exactamente una venta pendiente. */
    public function eliminarVentaPendiente(int $pendienteId): bool
    {
        $stmt = $this->conexion->prepare(
            "DELETE FROM ventas_pendientes WHERE id = ?"
        );
        $stmt->bind_param("i", $pendienteId);
        $stmt->execute();

        return $stmt->affected_rows === 1;
    }
}

/* ===================== SERVICIO (CASO DE USO) ===================== */

class RegistrarVentaService
{
    private VentaRepository $repositorio;

    public function __construct(VentaRepository $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    /**
     * Registra la venta completa dentro de una transacción.
     *
     * Lanza InvalidArgumentException si el pago no es válido (antes de tocar la BD)
     * y Exception si algo falla durante la transacción (después de hacer rollback).
     *
     * @return array ["venta_id" => int, "total" => float, "cambio" => float]
     */
    public function registrar(
        array $carrito,
        array $datosCliente,
        string $metodoPago,
        float $montoPagado,
        int $pendienteId
    ): array {
        $total = CalculadoraVenta::total($carrito);

        // 1. Validar el pago (Strategy + Factory ya existentes).
        $estrategiaPago = MetodoPagoFactory::crear($metodoPago);
        $estrategiaPago->validarPago($total, $montoPagado);
        $cambio = $estrategiaPago->calcularCambio($total, $montoPagado);

        // 2. Persistir todo en una sola transacción.
        $this->repositorio->iniciarTransaccion();

        try {
            $ventaId = $this->repositorio->insertarVenta(
                $total,
                $metodoPago,
                $montoPagado,
                $datosCliente
            );

            foreach ($carrito as $item) {
                $productoId = (int) $item["id"];
                $cantidadVendida = (int) $item["cantidad"];

                $stock = $this->repositorio->obtenerStockBloqueado($productoId);

                if ($stock === null) {
                    throw new Exception("Uno de los productos ya no existe.");
                }

                if ($stock < $cantidadVendida) {
                    throw new Exception(
                        "No hay suficiente inventario de " . $item["nombre"] . "."
                    );
                }

                $this->repositorio->insertarDetalle(
                    $ventaId,
                    $productoId,
                    $cantidadVendida,
                    (float) $item["precio"],
                    CalculadoraVenta::subtotal($item)
                );

                $this->repositorio->descontarInventario($productoId, $cantidadVendida);
            }

            if ($pendienteId > 0 && !$this->repositorio->eliminarVentaPendiente($pendienteId)) {
                throw new Exception(
                    "La venta pendiente ya no existe; no se pudo confirmar el cobro."
                );
            }

            $this->repositorio->confirmar();
        } catch (Exception $e) {
            $this->repositorio->revertir();
            throw $e;
        }

        return [
            "venta_id" => $ventaId,
            "total" => $total,
            "cambio" => $cambio
        ];
    }
}
