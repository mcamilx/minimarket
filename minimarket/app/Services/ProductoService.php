<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Producto;
use App\Repositories\Contracts\ProductoRepositoryInterface;
use App\Validators\ProductoValidator;

/*
 * Reglas de negocio de los productos: registrar, editar, eliminar, reponer y consultar.
 */
class ProductoService
{
    public function __construct(
        private ProductoRepositoryInterface $productos,
        private ProductoValidator $validador
    ) {
    }

    /** @return Producto[] */
    public function listarRecientes(): array
    {
        return $this->productos->listarRecientes();
    }

    /** @return Producto[] */
    public function listarDisponibles(): array
    {
        return $this->productos->listarDisponibles();
    }

    /** @return Producto[] */
    public function buscar(string $texto): array
    {
        return $this->productos->buscar($texto);
    }

    public function registrar(string $codigo, string $nombre, mixed $precio, mixed $cantidad): void
    {
        $producto = $this->validador->crearProducto($codigo, $nombre, $precio, $cantidad);

        if ($this->productos->existeCodigo($producto->codigo)) {
            throw new ReglaNegocioException(
                "No se pudo registrar el producto: ese código ya está registrado."
            );
        }

        $this->productos->crear($producto);
    }

    public function obtener(int $id): Producto
    {
        $producto = $this->productos->buscarPorId($id);

        if ($producto === null) {
            throw new ReglaNegocioException("El producto no existe.");
        }

        return $producto;
    }

    /**
     * Cambia código, nombre y precio. El stock solo cambia con ventas y reposiciones.
     * Las ventas anteriores no se afectan porque guardan su propio precio unitario.
     */
    public function actualizar(int $id, string $codigo, string $nombre, mixed $precio): void
    {
        $actual = $this->obtener($id);
        $datos = $this->validador->crearProducto($codigo, $nombre, $precio, $actual->cantidad);

        if ($this->productos->existeCodigo($datos->codigo, $id)) {
            throw new ReglaNegocioException(
                "No se pudo actualizar el producto: ese código ya está registrado."
            );
        }

        $this->productos->actualizar(
            new Producto($id, $datos->codigo, $datos->nombre, $datos->precio, $actual->cantidad)
        );
    }

    /**
     * Elimina un producto solo si nunca se ha vendido ni está en una venta en espera,
     * para no dejar facturas del historial sin su producto.
     */
    public function eliminar(int $id): string
    {
        $producto = $this->obtener($id);

        if ($this->productos->tieneMovimientos($id)) {
            throw new ReglaNegocioException(
                "No se puede eliminar \"" . $producto->nombre . "\" porque ya tiene ventas " .
                "registradas o está en una venta en espera. Así se conserva el historial."
            );
        }

        $this->productos->eliminar($id);

        return $producto->nombre;
    }

    /**
     * Repone un producto agotado.
     * @return array{tipo:string,mensaje:string} Mensaje listo para mostrar.
     */
    public function reponer(string $codigo, mixed $cantidad): array
    {
        $cantidad = filter_var($cantidad, FILTER_VALIDATE_INT);

        if ($codigo === "" || $cantidad === false || $cantidad <= 0) {
            return [
                "tipo" => "danger",
                "mensaje" => "Ingresa el código del producto y una cantidad mayor que cero."
            ];
        }

        if ($this->productos->reponerSiAgotado($codigo, $cantidad)) {
            return [
                "tipo" => "success",
                "mensaje" => "Stock repuesto correctamente. El producto ya está disponible para venderse."
            ];
        }

        if (!$this->productos->existeCodigo($codigo)) {
            return ["tipo" => "danger", "mensaje" => "No existe un producto con ese código."];
        }

        return [
            "tipo" => "warning",
            "mensaje" => "El producto existe, pero no está agotado. La reposición solo se permite cuando su stock es cero."
        ];
    }
}
