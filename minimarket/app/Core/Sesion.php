<?php

namespace App\Core;

/*
 * Envoltorio de $_SESSION. Ninguna otra clase toca $_SESSION directamente;
 * así la sesión se puede reemplazar o simular en las pruebas.
 */
class Sesion
{
    public function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        return $_SESSION[$clave] ?? $porDefecto;
    }

    public function guardar(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public function eliminar(string ...$claves): void
    {
        foreach ($claves as $clave) {
            unset($_SESSION[$clave]);
        }
    }

    public function regenerar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function destruir(): void
    {
        $_SESSION = [];

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if (ini_get("session.use_cookies")) {
            $parametros = session_get_cookie_params();
            setcookie(
                session_name(),
                "",
                time() - 42000,
                $parametros["path"],
                $parametros["domain"],
                $parametros["secure"],
                $parametros["httponly"]
            );
        }

        session_destroy();
    }

    /** Guarda un mensaje para mostrarlo una sola vez en la siguiente página. */
    public function flash(string $mensaje, string $tipo = "info"): void
    {
        $_SESSION["flash"] = ["mensaje" => $mensaje, "tipo" => $tipo];
    }

    public function tomarFlash(): ?array
    {
        $flash = $_SESSION["flash"] ?? null;
        unset($_SESSION["flash"]);

        return $flash;
    }
}
