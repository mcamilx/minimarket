<?php

namespace App\Repositories;

use App\Models\Cliente;
use App\Models\ItemCarrito;
use App\Models\Venta;
use App\Repositories\Contracts\VentaRepositoryInterface;
use PDO;

/*
 * Patrón Repository: SQL de las tablas ventas y detalle_venta.
 */
class VentaRepository implements VentaRepositoryInterface
{
    public function __construct(private PDO $conexion)
    {
    }

    public function crear(float $total, string $metodoPago, float $montoPagado, Cliente $cliente): int
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO ventas
             (total, metodo_pago, monto_pagado, estado,
              cliente_tipo_documento, cliente_documento, cliente_nombre, cliente_telefono)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $total,
            $metodoPago,
            $montoPagado,
            Venta::APROBADA,
            $cliente->tipoDocumento,
            $cliente->documento,
            $cliente->nombre,
            $cliente->telefono
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function agregarDetalle(int $ventaId, ItemCarrito $item): void
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $ventaId,
            $item->productoId,
            $item->cantidad,
            $item->precio,
            $item->subtotal()
        ]);
    }

    public function contar(): int
    {
        return (int) $this->conexion->query("SELECT COUNT(*) FROM ventas")->fetchColumn();
    }

    public function historial(string $busqueda = ""): array
    {
        $union = "(SELECT v.id, v.fecha_hora, v.total, v.metodo_pago, v.monto_pagado,
                          CASE WHEN v.estado = 'PAGADA' THEN 'APROBADA' ELSE v.estado END AS estado,
                          v.cliente_tipo_documento, v.cliente_documento,
                          v.cliente_nombre, v.cliente_telefono, 0 AS es_pendiente
                   FROM ventas v
                   UNION ALL
                   SELECT vp.id, vp.fecha_hora, vp.total, '' AS metodo_pago,
                          0 AS monto_pagado, 'PENDIENTE' AS estado,
                          NULL AS cliente_tipo_documento, NULL AS cliente_documento,
                          NULL AS cliente_nombre, NULL AS cliente_telefono, 1 AS es_pendiente
                   FROM ventas_pendientes vp) AS historial";
        $orden = " ORDER BY fecha_hora DESC, es_pendiente DESC, id DESC";

        if ($busqueda === "") {
            $filas = $this->conexion->query("SELECT * FROM " . $union . $orden)->fetchAll();
        } else {
            $numero = ctype_digit($busqueda) ? (int) $busqueda : 0;
            $patron = "%" . $busqueda . "%";
            $stmt = $this->conexion->prepare(
                "SELECT * FROM " . $union . "
                 WHERE id = ?
                    OR cliente_nombre LIKE ?
                    OR cliente_documento LIKE ?
                    OR cliente_telefono LIKE ?" . $orden
            );
            $stmt->execute([$numero, $patron, $patron, $patron]);
            $filas = $stmt->fetchAll();
        }

        return array_map([Venta::class, "desdeFila"], $filas);
    }
}
