<?php

namespace App\Repositories\Contracts;

use App\Models\Producto;

/*
 * Contrato del repositorio de productos. Los servicios dependen de esta
 * interfaz y no de la clase concreta (principio de inversión de dependencias).
 */
interface ProductoRepositoryInterface
{
    public function buscarPorId(int $id): ?Producto;

    public function buscarPorCodigo(string $codigo): ?Producto;

    /** @return Producto[] */
    public function listarRecientes(): array;

    /** @return Producto[] Productos activos con stock (los que se pueden vender). */
    public function listarDisponibles(): array;

    /** @return Producto[] Productos cuyo código o nombre contiene el texto (todos si está vacío). */
    public function buscar(string $texto): array;

    /** True si el código ya existe en otro producto (se puede excluir un id al editar). */
    public function existeCodigo(string $codigo, int $exceptoId = 0): bool;

    public function crear(Producto $producto): int;

    /** Actualiza código, nombre y precio. El stock no se cambia aquí. */
    public function actualizar(Producto $producto): void;

    /** True si el producto aparece en alguna venta o venta en espera. */
    public function tieneMovimientos(int $id): bool;

    public function eliminar(int $id): void;

    /** Suma unidades solo si el producto está activo y agotado. Devuelve true si lo repuso. */
    public function reponerSiAgotado(string $codigo, int $cantidad): bool;

    /** Activo = se vende; inactivo = descontinuado. */
    public function cambiarEstado(int $id, bool $activo): void;

    /** Bloquea la fila hasta terminar la transacción y devuelve el producto (null si no existe). */
    public function bloquearParaVenta(int $id): ?Producto;

    public function descontarStock(int $id, int $cantidad): void;

    /** @return array{total_productos:int,total_unidades:int} */
    public function estadisticas(): array;
}
