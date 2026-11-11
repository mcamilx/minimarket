<?php

namespace App\Repositories;

use App\Models\Producto;
use App\Repositories\Contracts\ProductoRepositoryInterface;
use PDO;

/*
 * Patrón Repository: único lugar del sistema con SQL de la tabla productos.
 */
class ProductoRepository implements ProductoRepositoryInterface
{
    public function __construct(private PDO $conexion)
    {
    }

    public function buscarPorId(int $id): ?Producto
    {
        $stmt = $this->conexion->prepare(
            "SELECT id, codigo, nombre, precio, cantidad FROM productos WHERE id = ?"
        );
        $stmt->execute([$id]);
        $fila = $stmt->fetch();

        return $fila ? Producto::desdeFila($fila) : null;
    }

    public function listarRecientes(): array
    {
        return $this->aProductos(
            $this->conexion->query("SELECT * FROM productos ORDER BY id DESC")->fetchAll()
        );
    }

    public function listarDisponibles(): array
    {
        return $this->aProductos(
            $this->conexion->query(
                "SELECT * FROM productos WHERE cantidad > 0 ORDER BY nombre ASC"
            )->fetchAll()
        );
    }

    public function buscar(string $texto): array
    {
        if ($texto === "") {
            return $this->aProductos(
                $this->conexion->query("SELECT * FROM productos ORDER BY nombre ASC")->fetchAll()
            );
        }

        $stmt = $this->conexion->prepare(
            "SELECT * FROM productos
             WHERE codigo LIKE ? OR nombre LIKE ?
             ORDER BY nombre ASC"
        );
        $patron = "%" . $texto . "%";
        $stmt->execute([$patron, $patron]);

        return $this->aProductos($stmt->fetchAll());
    }

    public function existeCodigo(string $codigo): bool
    {
        $stmt = $this->conexion->prepare("SELECT 1 FROM productos WHERE codigo = ?");
        $stmt->execute([$codigo]);

        return $stmt->fetchColumn() !== false;
    }

    public function crear(Producto $producto): int
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO productos (codigo, nombre, precio, cantidad) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $producto->codigo,
            $producto->nombre,
            $producto->precio,
            $producto->cantidad
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    public function reponerSiAgotado(string $codigo, int $cantidad): bool
    {
        $stmt = $this->conexion->prepare(
            "UPDATE productos SET cantidad = cantidad + ? WHERE codigo = ? AND cantidad = 0"
        );
        $stmt->execute([$cantidad, $codigo]);

        return $stmt->rowCount() === 1;
    }

    public function obtenerStockBloqueado(int $id): ?int
    {
        $stmt = $this->conexion->prepare(
            "SELECT cantidad FROM productos WHERE id = ? FOR UPDATE"
        );
        $stmt->execute([$id]);
        $cantidad = $stmt->fetchColumn();

        return $cantidad === false ? null : (int) $cantidad;
    }

    public function descontarStock(int $id, int $cantidad): void
    {
        $stmt = $this->conexion->prepare(
            "UPDATE productos SET cantidad = cantidad - ? WHERE id = ?"
        );
        $stmt->execute([$cantidad, $id]);
    }

    public function estadisticas(): array
    {
        $fila = $this->conexion->query(
            "SELECT COUNT(*) AS total_productos, COALESCE(SUM(cantidad), 0) AS total_unidades
             FROM productos"
        )->fetch();

        return [
            "total_productos" => (int) $fila["total_productos"],
            "total_unidades" => (int) $fila["total_unidades"]
        ];
    }

    /** @return Producto[] */
    private function aProductos(array $filas): array
    {
        return array_map([Producto::class, "desdeFila"], $filas);
    }
}
