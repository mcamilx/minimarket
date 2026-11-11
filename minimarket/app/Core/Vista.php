<?php

namespace App\Core;

/*
 * Se encarga de mostrar las vistas (archivos de app/Views).
 * Primero genera el contenido de la vista y luego lo coloca dentro
 * de la plantilla general (layouts/principal.php).
 */
class Vista
{
    public static function renderizar(string $nombre, array $datos = []): void
    {
        extract($datos);

        ob_start();
        require BASE_PATH . "/app/Views/" . $nombre . ".php";
        $contenido = ob_get_clean();

        require BASE_PATH . "/app/Views/layouts/principal.php";
    }
}
