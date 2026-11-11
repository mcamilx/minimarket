<?php
/*
 * Front Controller: ÚNICA puerta de entrada del sistema.
 * Todas las páginas se piden así: index.php?ruta=nombre-de-la-ruta
 */

require __DIR__ . "/app/bootstrap.php";

use App\Core\Contenedor;
use App\Core\Router;

$router = new Router(new Contenedor());

require __DIR__ . "/config/rutas.php";

$router->despachar($_SERVER["REQUEST_METHOD"], $_GET["ruta"] ?? "inicio");
