<?php
/*
 * Tabla de rutas del sistema.
 * Cada ruta indica: método HTTP, nombre de la ruta, [controlador, método]
 * y los roles que pueden usarla (vacío = cualquiera).
 */

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HistorialController;
use App\Controllers\InventarioController;
use App\Controllers\ProductoController;
use App\Controllers\VentaController;
use App\Models\Usuario;

$todos = [Usuario::ADMIN, Usuario::CAJERO];
$admin = [Usuario::ADMIN];

/** @var App\Core\Router $router */

// Inicio y sesión
$router->get("inicio", [DashboardController::class, "index"]);
$router->post("login", [AuthController::class, "login"]);
$router->post("logout", [AuthController::class, "logout"]);

// Productos (solo administrador)
$router->get("productos", [ProductoController::class, "index"], $admin);
$router->post("productos/registrar", [ProductoController::class, "registrar"], $admin);
$router->post("productos/reponer", [ProductoController::class, "reponer"], $admin);
$router->get("productos/editar", [ProductoController::class, "editar"], $admin);
$router->post("productos/actualizar", [ProductoController::class, "actualizar"], $admin);
$router->post("productos/eliminar", [ProductoController::class, "eliminar"], $admin);

// Inventario
$router->get("inventario", [InventarioController::class, "index"], $todos);

// Ventas
$router->get("ventas", [VentaController::class, "index"], $todos);
$router->post("ventas/agregar", [VentaController::class, "agregar"], $todos);
$router->post("ventas/eliminar", [VentaController::class, "eliminar"], $todos);
$router->post("ventas/guardar-pendiente", [VentaController::class, "guardarPendiente"], $todos);
$router->post("ventas/retomar-pendiente", [VentaController::class, "retomarPendiente"], $todos);
$router->post("ventas/finalizar", [VentaController::class, "finalizar"], $todos);

// Historial
$router->get("historial", [HistorialController::class, "index"], $todos);
