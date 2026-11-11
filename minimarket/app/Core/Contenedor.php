<?php

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HistorialController;
use App\Controllers\InventarioController;
use App\Controllers\ProductoController;
use App\Controllers\VentaController;
use App\Repositories\ProductoRepository;
use App\Repositories\VentaPendienteRepository;
use App\Repositories\VentaRepository;
use App\Services\AuthService;
use App\Services\CalculadoraVenta;
use App\Services\CarritoService;
use App\Services\DashboardService;
use App\Services\HistorialService;
use App\Services\Pagos\MetodoPagoFactory;
use App\Services\ProductoService;
use App\Services\VentaPendienteService;
use App\Services\VentaService;
use App\Validators\ClienteValidator;
use App\Validators\ProductoValidator;
use InvalidArgumentException;

/*
 * Contenedor de dependencias (inyección de dependencias).
 * Es el ÚNICO lugar donde se usa "new" para armar los objetos del sistema:
 * aquí se decide qué repositorio recibe cada servicio y qué servicio recibe
 * cada controlador. Cada objeto se crea una sola vez por solicitud.
 */
class Contenedor
{
    private array $instancias = [];

    public function obtener(string $clase): object
    {
        if (!isset($this->instancias[$clase])) {
            $this->instancias[$clase] = $this->crear($clase);
        }

        return $this->instancias[$clase];
    }

    private function crear(string $clase): object
    {
        return match ($clase) {
            // Núcleo
            Sesion::class => new Sesion(),
            Transaccion::class => new Transaccion(Database::obtenerConexion()),

            // Repositorios
            ProductoRepository::class => new ProductoRepository(Database::obtenerConexion()),
            VentaRepository::class => new VentaRepository(Database::obtenerConexion()),
            VentaPendienteRepository::class => new VentaPendienteRepository(Database::obtenerConexion()),

            // Servicios
            AuthService::class => new AuthService($this->obtener(Sesion::class)),
            CalculadoraVenta::class => new CalculadoraVenta(),
            MetodoPagoFactory::class => new MetodoPagoFactory(),
            ProductoService::class => new ProductoService(
                $this->obtener(ProductoRepository::class),
                new ProductoValidator()
            ),
            CarritoService::class => new CarritoService(
                $this->obtener(Sesion::class),
                $this->obtener(ProductoRepository::class),
                $this->obtener(CalculadoraVenta::class)
            ),
            VentaPendienteService::class => new VentaPendienteService(
                $this->obtener(VentaPendienteRepository::class),
                $this->obtener(CarritoService::class),
                $this->obtener(Transaccion::class)
            ),
            VentaService::class => new VentaService(
                $this->obtener(VentaRepository::class),
                $this->obtener(ProductoRepository::class),
                $this->obtener(VentaPendienteRepository::class),
                $this->obtener(MetodoPagoFactory::class),
                $this->obtener(CalculadoraVenta::class),
                $this->obtener(Transaccion::class)
            ),
            HistorialService::class => new HistorialService($this->obtener(VentaRepository::class)),
            DashboardService::class => new DashboardService(
                $this->obtener(ProductoRepository::class),
                $this->obtener(VentaRepository::class)
            ),

            // Controladores
            AuthController::class => new AuthController(...$this->base()),
            DashboardController::class => new DashboardController(
                ...$this->base(),
                ...[$this->obtener(DashboardService::class)]
            ),
            ProductoController::class => new ProductoController(
                ...$this->base(),
                ...[$this->obtener(ProductoService::class)]
            ),
            InventarioController::class => new InventarioController(
                ...$this->base(),
                ...[$this->obtener(ProductoService::class)]
            ),
            VentaController::class => new VentaController(
                ...$this->base(),
                ...[
                    $this->obtener(CarritoService::class),
                    $this->obtener(VentaService::class),
                    $this->obtener(VentaPendienteService::class),
                    $this->obtener(ProductoService::class),
                    new ClienteValidator()
                ]
            ),
            HistorialController::class => new HistorialController(
                ...$this->base(),
                ...[$this->obtener(HistorialService::class)]
            ),

            default => throw new InvalidArgumentException("No se sabe cómo crear " . $clase)
        };
    }

    /** Dependencias que reciben todos los controladores. */
    private function base(): array
    {
        return [$this->obtener(AuthService::class), $this->obtener(Sesion::class)];
    }
}
