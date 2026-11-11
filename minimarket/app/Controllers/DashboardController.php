<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sesion;
use App\Models\Usuario;
use App\Services\AuthService;
use App\Services\DashboardService;

/*
 * Página de inicio: selección de perfil o panel principal.
 */
class DashboardController extends Controller
{
    public function __construct(
        AuthService $auth,
        Sesion $sesion,
        private DashboardService $dashboard
    ) {
        parent::__construct($auth, $sesion);
    }

    /** GET ruta=inicio */
    public function index(): void
    {
        $usuario = $this->auth->usuarioActual();

        if ($usuario === null) {
            $this->vista("auth/login", ["perfiles" => Usuario::ROLES]);
            return;
        }

        $this->vista("dashboard/index", ["resumen" => $this->dashboard->resumen()]);
    }
}
