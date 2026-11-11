<?php
/*
 * Arranque de la aplicación: se ejecuta una sola vez por solicitud.
 * 1. Define la ruta base del proyecto.
 * 2. Registra el autoloader (carga las clases automáticamente).
 * 3. Carga la configuración y las funciones de ayuda para las vistas.
 * 4. Inicia la sesión.
 */

define("BASE_PATH", dirname(__DIR__));

/*
 * Autoloader PSR-4: convierte el nombre de una clase en la ruta de su archivo.
 * Ejemplo: App\Services\VentaService -> app/Services/VentaService.php
 */
spl_autoload_register(function (string $clase): void {
    $prefijo = "App\\";

    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }

    $rutaRelativa = str_replace("\\", "/", substr($clase, strlen($prefijo)));
    $archivo = BASE_PATH . "/app/" . $rutaRelativa . ".php";

    if (is_file($archivo)) {
        require $archivo;
    }
});

$configuracion = require BASE_PATH . "/config/config.php";
date_default_timezone_set($configuracion["app"]["zona_horaria"]);
App\Core\Database::configurar($configuracion["base_datos"]);

require BASE_PATH . "/app/Core/helpers.php";

if (PHP_SAPI !== "cli" && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
