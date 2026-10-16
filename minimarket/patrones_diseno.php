<?php

interface MetodoPagoStrategy
{
    public function validarPago(float $total, float $montoPagado): void;

    public function calcularCambio(float $total, float $montoPagado): float;
}

class PagoEfectivoStrategy implements MetodoPagoStrategy
{
    public function validarPago(float $total, float $montoPagado): void
    {
        if ($montoPagado < $total) {
            throw new InvalidArgumentException(
                "El valor pagado es inferior al total de la compra. Regrese a la venta."
            );
        }
    }

    public function calcularCambio(float $total, float $montoPagado): float
    {
        return $montoPagado - $total;
    }
}

class PagoTarjetaStrategy implements MetodoPagoStrategy
{
    public function validarPago(float $total, float $montoPagado): void
    {
        if ($montoPagado < $total) {
            throw new InvalidArgumentException(
                "El valor pagado es inferior al total de la compra. Regrese a la venta."
            );
        }
    }

    public function calcularCambio(float $total, float $montoPagado): float
    {
        return 0;
    }
}

class MetodoPagoFactory
{
    public static function crear(string $metodo): MetodoPagoStrategy
    {
        switch ($metodo) {
            case "Efectivo":
                return new PagoEfectivoStrategy();
            case "Tarjeta":
                return new PagoTarjetaStrategy();
            default:
                throw new InvalidArgumentException(
                    "Método de pago no válido. Regrese a la venta."
                );
        }
    }
}

class ProductoRepository
{
    public static function buscarPorId(mysqli $conexion, int $productoId): ?array
    {
        $stmt = $conexion->prepare(
            "SELECT id, codigo, nombre, precio, cantidad
             FROM productos
             WHERE id = ?"
        );
        $stmt->bind_param("i", $productoId);
        $stmt->execute();

        $producto = $stmt->get_result()->fetch_assoc();

        return $producto ?: null;
    }

    public static function listarDisponibles(mysqli $conexion): mysqli_result
    {
        $resultado = $conexion->query(
            "SELECT *
             FROM productos
             WHERE cantidad > 0
             ORDER BY nombre ASC"
        );

        if (!$resultado instanceof mysqli_result) {
            throw new RuntimeException("No se pudieron consultar los productos disponibles.");
        }

        return $resultado;
    }

    public static function reponerSiAgotado(
        mysqli $conexion,
        string $codigo,
        int $cantidad
    ): string {
        $stmt = $conexion->prepare(
            "UPDATE productos
             SET cantidad = cantidad + ?
             WHERE codigo = ? AND cantidad = 0"
        );
        $stmt->bind_param("is", $cantidad, $codigo);
        $stmt->execute();

        if ($stmt->affected_rows === 1) {
            return "repuesto";
        }

        $stmtProducto = $conexion->prepare(
            "SELECT cantidad FROM productos WHERE codigo = ?"
        );
        $stmtProducto->bind_param("s", $codigo);
        $stmtProducto->execute();

        return $stmtProducto->get_result()->fetch_assoc()
            ? "no_agotado"
            : "no_encontrado";
    }
}
