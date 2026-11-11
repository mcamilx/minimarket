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

    /** @return Producto[] */
    public function listarRecientes(): array;

    /** @return Producto[] */
    public function listarDisponibles(): array;

    /** @return Producto[] Productos cuyo código o nombre contiene el texto (todos si está vacío). */
    public function buscar(string $texto): array;

    public function existeCodigo(string $codigo): bool;

    public function crear(Producto $producto): int;

    /** Suma unidades solo si el producto está agotado. Devuelve true si lo repuso. */
    public function reponerSiAgotado(string $codigo, int $cantidad): bool;

    /** Bloquea la fila hasta terminar la transacción y devuelve el stock (null si no existe). */
    public function obtenerStockBloqueado(int $id): ?int;

    public function descontarStock(int $id, int $cantidad): void;

    /** @return array{total_productos:int,total_unidades:int} */
    public function estadisticas(): array;
}
