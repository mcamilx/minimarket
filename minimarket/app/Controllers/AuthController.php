<?php

namespace App\Controllers;

use App\Core\Controller;

/*
 * Ingreso y salida del sistema.
 */
class AuthController extends Controller
{
    /** POST ruta=login */
    public function login(): void
    {
        $this->auth->iniciarSesion($this->texto("rol"));
        $this->redirigir("inicio");
    }

    /** POST ruta=logout */
    public function logout(): void
    {
        $this->auth->cerrarSesion();
        $this->redirigir("inicio");
    }
}
