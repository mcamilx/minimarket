<?php

namespace App\Repositories;

use App\Models\ItemCarrito;
use App\Models\VentaPendiente;
use App\Repositories\Contracts\VentaPendienteRepositoryInterface;
use PDO;

/*
 * Patrón Repository: SQL de las tablas ventas_pendientes y detalle_venta_pendiente.
 */
class VentaPendienteRepository implements VentaPendienteRepositoryInterface
{
    public function __construct(private PDO $conexion)
    {
    }

    public function listar(): array
    {
        $filas = $this->conexion->query(
            "SELECT vp.id, vp.fecha_hora, vp.total, COUNT(dvp.id) AS productos
             FROM ventas_pendientes vp
             LEFT JOIN detalle_venta_pendiente dvp ON dvp.venta_pendiente_id = vp.id
             GROUP BY vp.id, vp.fecha_hora, vp.total
             ORDER BY vp.id DESC"
        )->fetchAll();

        return array_map([VentaPendiente::class, "desdeFila"], $filas);
    }

    public function existe(int $id): bool
    {
        $stmt = $this->conexion->prepare("SELECT 1 FROM ventas_pendientes WHERE id = ?");
        $stmt->execute([$id]);

        return $stmt->fetchColumn() !== false;
    }

    public function bloquear(int $id): bool
    {
        $stmt = $this->conexion->prepare(
            "SELECT id FROM ventas_pendientes WHERE id = ? FOR UPDATE"
        );
        $stmt->execute([$id]);

        return $stmt->fetchColumn() !== false;
    }

    public function obtenerItems(int $id): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT producto_id, codigo, nombre, precio, cantidad
             FROM detalle_venta_pendiente
             WHERE venta_pendiente_id = ?
             ORDER BY id ASC"
        );
        $stmt->execute([$id]);

        return array_map([ItemCarrito::class, "desdeArreglo"], $stmt->fetchAll());
    }

    public function crear(float $total): int
    {
        $stmt = $this->conexion->prepare("INSERT INTO ventas_pendientes (total) VALUES (?)");
        $stmt->execute([$total]);

        return (int) $this->conexion->lastInsertId();
    }

    public function actualizarTotal(int $id, float $total): void
    {
        $stmt = $this->conexion->prepare("UPDATE ventas_pendientes SET total = ? WHERE id = ?");
        $stmt->execute([$total, $id]);
    }

    public function eliminarItems(int $id): void
    {
        $stmt = $this->conexion->prepare(
            "DELETE FROM detalle_venta_pendiente WHERE venta_pendiente_id = ?"
        );
        $stmt->execute([$id]);
    }

    public function agregarItem(int $id, ItemCarrito $item): void
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO detalle_venta_pendiente
             (venta_pendiente_id, producto_id, codigo, nombre, precio, cantidad)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $id,
            $item->productoId,
            $item->codigo,
            $item->nombre,
            $item->precio,
            $item->cantidad
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->conexion->prepare("DELETE FROM ventas_pendientes WHERE id = ?");
        $stmt->execute([$id]);

        return $stmt->rowCount() === 1;
    }
}
