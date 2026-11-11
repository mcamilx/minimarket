<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Sesion;
use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use App\Services\AuthService;
use App\Services\CarritoService;
use App\Services\ProductoService;
use App\Services\VentaPendienteService;
use App\Services\VentaService;
use App\Validators\ClienteValidator;
use Throwable;

/*
 * Venta en curso, ventas en espera y registro de la venta (F-01).
 * El controlador solo recibe la solicitud, llama al servicio y elige la vista.
 */
class VentaController extends Controller
{
    public function __construct(
        AuthService $auth,
        Sesion $sesion,
        private CarritoService $carrito,
        private VentaService $ventas,
        private VentaPendienteService $pendientes,
        private ProductoService $productos,
        private ClienteValidator $validadorCliente
    ) {
        parent::__construct($auth, $sesion);
    }

    /** GET ruta=ventas */
    public function index(): void
    {
        $this->vista("ventas/index", [
            "items" => $this->carrito->items(),
            "total" => $this->carrito->total(),
            "productos" => $this->productos->listarDisponibles(),
            "pendientes" => $this->pendientes->listar(),
            "ventaPendienteId" => $this->carrito->ventaPendienteId(),
            "cliente" => $this->carrito->clienteRecordado(),
            "scripts" => ["assets/js/pago.js"]
        ]);
    }

    /** POST ruta=ventas/agregar */
    public function agregar(): void
    {
        try {
            $this->carrito->agregar($this->entero("producto_id"), $this->entero("cantidad"));
            $this->sesion->flash("Producto agregado a la compra.");
        } catch (ReglaNegocioException $error) {
            $this->sesion->flash($error->getMessage());
        }

        $this->redirigir("ventas");
    }

    /** POST ruta=ventas/eliminar */
    public function eliminar(): void
    {
        $this->carrito->eliminar($this->entero("producto_id"));
        $this->redirigir("ventas");
    }

    /** POST ruta=ventas/guardar-pendiente */
    public function guardarPendiente(): void
    {
        try {
            $numero = $this->pendientes->guardar();
            $this->sesion->flash("Venta guardada en espera como #" . $numero . ".");
        } catch (ReglaNegocioException $error) {
            $this->sesion->flash($error->getMessage());
        } catch (Throwable $error) {
            $this->sesion->flash("No se pudo guardar la venta pendiente: " . $error->getMessage());
        }

        $this->redirigir("ventas");
    }

    /** POST ruta=ventas/retomar-pendiente */
    public function retomarPendiente(): void
    {
        $id = $this->entero("venta_pendiente_id");

        try {
            $this->pendientes->retomar($id);
            $this->sesion->flash("Venta pendiente #" . $id . " retomada.");
        } catch (ReglaNegocioException $error) {
            $this->sesion->flash($error->getMessage());
        }

        $this->redirigir("ventas");
    }

    /** POST ruta=ventas/finalizar */
    public function finalizar(): void
    {
        if ($this->carrito->estaVacio()) {
            $this->redirigir("ventas");
        }

        // 1. Leer y validar los datos del cliente.
        $cliente = new Cliente(
            $this->texto("cliente_tipo_documento"),
            $this->texto("cliente_documento"),
            $this->texto("cliente_nombre"),
            $this->texto("cliente_telefono")
        );
        $this->carrito->recordarCliente($cliente);

        $errores = $this->validadorCliente->validar($cliente);

        if (count($errores) > 0) {
            $this->vista("ventas/error_cliente", ["errores" => $errores], 422);
            return;
        }

        // 2. Delegar el registro de la venta al servicio.
        try {
            $factura = $this->ventas->registrar(
                $this->carrito->items(),
                $cliente,
                $this->texto("metodo_pago"),
                (float) ($_POST["monto_pagado"] ?? 0),
                $this->carrito->ventaPendienteId()
            );
        } catch (ReglaNegocioException $error) {
            $this->vista("errores/mensaje", [
                "titulo" => "No se pudo completar la venta",
                "mensaje" => $error->getMessage(),
                "enlace" => url("ventas"),
                "textoEnlace" => "Regresar a la venta"
            ], 422);
            return;
        }

        // 3. Limpiar la venta en curso y mostrar la factura.
        $this->carrito->vaciar();
        $this->vista("ventas/factura", ["factura" => $factura]);
    }
}
