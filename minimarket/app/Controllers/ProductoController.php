<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sesion;
use App\Exceptions\ReglaNegocioException;
use App\Services\AuthService;
use App\Services\ProductoService;

/*
 * Gestión de productos (solo administrador): registrar, editar, eliminar y reponer.
 */
class ProductoController extends Controller
{
    public function __construct(
        AuthService $auth,
        Sesion $sesion,
        private ProductoService $productos
    ) {
        parent::__construct($auth, $sesion);
    }

    /** GET ruta=productos */
    public function index(): void
    {
        $this->vista("productos/index", ["productos" => $this->productos->listarRecientes()]);
    }

    /** POST ruta=productos/registrar */
    public function registrar(): void
    {
        try {
            $this->productos->registrar(
                $this->texto("codigo"),
                $this->texto("nombre"),
                $_POST["precio"] ?? null,
                $_POST["cantidad"] ?? null
            );
            $this->sesion->flash("Producto registrado correctamente.", "success");
        } catch (ReglaNegocioException $error) {
            $this->sesion->flash($error->getMessage(), "danger");
        }

        $this->redirigir("productos");
    }

    /** GET ruta=productos/editar&id=N */
    public function editar(): void
    {
        try {
            $producto = $this->productos->obtener((int) ($_GET["id"] ?? 0));
        } catch (ReglaNegocioException $error) {
            $this->sesion->flash($error->getMessage(), "danger");
            $this->redirigir("productos");
        }

        $this->vista("productos/editar", ["producto" => $producto]);
    }

    /** POST ruta=productos/actualizar */
    public function actualizar(): void
    {
        $id = $this->entero("id");

        try {
            $this->productos->actualizar(
                $id,
                $this->texto("codigo"),
                $this->texto("nombre"),
                $_POST["precio"] ?? null
            );
            $this->sesion->flash("Producto actualizado correctamente.", "success");
        } catch (ReglaNegocioException $error) {
            $this->sesion->flash($error->getMessage(), "danger");
            header("Location: " . url("productos/editar", ["id" => $id]));
            exit;
        }

        $this->redirigir("productos");
    }

    /** POST ruta=productos/eliminar */
    public function eliminar(): void
    {
        try {
            $nombre = $this->productos->eliminar($this->entero("id"));
            $this->sesion->flash("Producto \"" . $nombre . "\" eliminado correctamente.", "success");
        } catch (ReglaNegocioException $error) {
            $this->sesion->flash($error->getMessage(), "danger");
        }

        $this->redirigir("productos");
    }

    /** POST ruta=productos/reponer */
    public function reponer(): void
    {
        $resultado = $this->productos->reponer(
            $this->texto("codigo_reposicion"),
            $_POST["cantidad_reposicion"] ?? null
        );
        $this->sesion->flash($resultado["mensaje"], $resultado["tipo"]);

        $this->redirigir("productos");
    }
}
