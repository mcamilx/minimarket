<?php
/*
 * Dobles de prueba: versiones falsas de los repositorios y de la transacción.
 * Guardan los datos en memoria, así las pruebas no necesitan MariaDB.
 * Funcionan porque los servicios dependen de INTERFACES, no de clases concretas.
 */

use App\Core\Transaccion;
use App\Models\Cliente;
use App\Models\ItemCarrito;
use App\Models\Producto;
use App\Models\VentaPendiente;
use App\Repositories\Contracts\ProductoRepositoryInterface;
use App\Repositories\Contracts\VentaPendienteRepositoryInterface;
use App\Repositories\Contracts\VentaRepositoryInterface;

class TransaccionFalsa extends Transaccion
{
    public bool $confirmada = false;
    public bool $revertida = false;

    public function __construct()
    {
    }

    public function ejecutar(callable $operacion): mixed
    {
        try {
            $resultado = $operacion();
            $this->confirmada = true;
            return $resultado;
        } catch (Throwable $error) {
            $this->revertida = true;
            throw $error;
        }
    }
}

class ProductoRepositoryFalso implements ProductoRepositoryInterface
{
    /** @var Producto[] */
    public array $productos = [];

    public function __construct(Producto ...$productos)
    {
        foreach ($productos as $producto) {
            $this->productos[$producto->id] = $producto;
        }
    }

    public function buscarPorId(int $id): ?Producto { return $this->productos[$id] ?? null; }
    public function listarRecientes(): array { return array_values($this->productos); }
    public function listarDisponibles(): array { return array_values(array_filter($this->productos, fn ($p) => $p->cantidad > 0)); }
    public function buscar(string $texto): array { return array_values($this->productos); }

    /** Ids de productos que ya aparecen en ventas (para probar la eliminación). */
    public array $conMovimientos = [];

    public function existeCodigo(string $codigo, int $exceptoId = 0): bool
    {
        foreach ($this->productos as $producto) {
            if ($producto->codigo === $codigo && $producto->id !== $exceptoId) {
                return true;
            }
        }
        return false;
    }

    public function crear(Producto $producto): int
    {
        $id = count($this->productos) + 1;
        $this->productos[$id] = new Producto($id, $producto->codigo, $producto->nombre, $producto->precio, $producto->cantidad);
        return $id;
    }

    public function actualizar(Producto $producto): void { $this->productos[$producto->id] = $producto; }
    public function tieneMovimientos(int $id): bool { return in_array($id, $this->conMovimientos, true); }
    public function eliminar(int $id): void { unset($this->productos[$id]); }

    public function reponerSiAgotado(string $codigo, int $cantidad): bool
    {
        foreach ($this->productos as $id => $p) {
            if ($p->codigo === $codigo && $p->cantidad === 0) {
                $this->productos[$id] = new Producto($id, $p->codigo, $p->nombre, $p->precio, $cantidad);
                return true;
            }
        }
        return false;
    }

    public function obtenerStockBloqueado(int $id): ?int { return isset($this->productos[$id]) ? $this->productos[$id]->cantidad : null; }

    public function descontarStock(int $id, int $cantidad): void
    {
        $p = $this->productos[$id];
        $this->productos[$id] = new Producto($id, $p->codigo, $p->nombre, $p->precio, $p->cantidad - $cantidad);
    }

    public function estadisticas(): array { return ["total_productos" => count($this->productos), "total_unidades" => 0]; }
}

class VentaRepositoryFalso implements VentaRepositoryInterface
{
    public array $ventas = [];
    public array $detalles = [];

    public function crear(float $total, string $metodoPago, float $montoPagado, Cliente $cliente): int
    {
        $this->ventas[] = compact("total", "metodoPago", "montoPagado");
        return count($this->ventas);
    }

    public function agregarDetalle(int $ventaId, ItemCarrito $item): void { $this->detalles[] = $item; }
    public function contar(): int { return count($this->ventas); }
    public function historial(string $busqueda = ""): array { return []; }
}

class VentaPendienteRepositoryFalso implements VentaPendienteRepositoryInterface
{
    /** @var array<int, ItemCarrito[]> */
    public array $pendientes = [];

    public function listar(): array { return []; }
    public function existe(int $id): bool { return isset($this->pendientes[$id]); }
    public function bloquear(int $id): bool { return $this->existe($id); }
    public function obtenerItems(int $id): array { return $this->pendientes[$id] ?? []; }
    public function crear(float $total): int { $id = count($this->pendientes) + 1; $this->pendientes[$id] = []; return $id; }
    public function actualizarTotal(int $id, float $total): void {}
    public function eliminarItems(int $id): void { $this->pendientes[$id] = []; }
    public function agregarItem(int $id, ItemCarrito $item): void { $this->pendientes[$id][] = $item; }

    public function eliminar(int $id): bool
    {
        if (!$this->existe($id)) {
            return false;
        }
        unset($this->pendientes[$id]);
        return true;
    }
}
