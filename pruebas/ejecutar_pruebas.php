<?php
/*
 * Pruebas automáticas del Minimarket.
 * Ejecutar desde la raíz del repositorio:
 *     php pruebas/ejecutar_pruebas.php
 */

require __DIR__ . "/../minimarket/app/bootstrap.php";
require __DIR__ . "/dobles.php";

use App\Core\Sesion;
use App\Exceptions\ReglaNegocioException;
use App\Exceptions\ValidacionException;
use App\Models\Cliente;
use App\Models\ItemCarrito;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\AuthService;
use App\Services\CalculadoraVenta;
use App\Services\CarritoService;
use App\Services\Pagos\MetodoPagoFactory;
use App\Services\Pagos\PagoEfectivoStrategy;
use App\Services\Pagos\PagoTarjetaStrategy;
use App\Services\ProductoService;
use App\Services\VentaPendienteService;
use App\Services\VentaService;
use App\Validators\ClienteValidator;
use App\Validators\ProductoValidator;

$aprobadas = 0;
$fallidas = 0;

function comprobar(string $nombre, bool $condicion): void
{
    global $aprobadas, $fallidas;
    $condicion ? $aprobadas++ : $fallidas++;
    echo ($condicion ? "[OK]    " : "[FALLA] ") . $nombre . PHP_EOL;
}

/** Ejecuta $accion y devuelve el mensaje de la excepción esperada (o null si no se lanzó). */
function mensajeDeError(callable $accion, string $tipo = ReglaNegocioException::class): ?string
{
    try {
        $accion();
    } catch (Throwable $error) {
        return $error instanceof $tipo ? $error->getMessage() : "OTRO: " . get_class($error);
    }
    return null;
}

function seccion(string $titulo): void
{
    echo PHP_EOL . "--- " . $titulo . " ---" . PHP_EOL;
}

// Datos de ejemplo: 2 Arroz a $3.500 + 3 Leche a $4.200 = $19.600
$arroz = new Producto(1, "ARR01", "Arroz", 3500, 10);
$leche = new Producto(2, "LEC01", "Leche", 4200, 10);
$items = [
    1 => ItemCarrito::desdeProducto($arroz, 2),
    2 => ItemCarrito::desdeProducto($leche, 3)
];
$sinCliente = new Cliente();

function crearVentaService(ProductoRepositoryFalso $productos, ?VentaRepositoryFalso $ventas = null, ?VentaPendienteRepositoryFalso $pendientes = null, ?TransaccionFalsa $transaccion = null): VentaService
{
    return new VentaService(
        $ventas ?? new VentaRepositoryFalso(),
        $productos,
        $pendientes ?? new VentaPendienteRepositoryFalso(),
        new MetodoPagoFactory(),
        new CalculadoraVenta(),
        $transaccion ?? new TransaccionFalsa()
    );
}

/* ================= MODELOS Y CÁLCULOS ================= */
seccion("Modelos y cálculo del total");
comprobar("Subtotal = precio x cantidad (2 x 3500 = 7000)", $items[1]->subtotal() == 7000);
comprobar("Total = suma de subtotales (19600)", (new CalculadoraVenta())->total($items) == 19600);
comprobar("Total de una venta vacía = 0", (new CalculadoraVenta())->total([]) == 0);
comprobar("ItemCarrito se convierte a arreglo y vuelve igual", ItemCarrito::desdeArreglo($items[2]->aArreglo()) == $items[2]);
comprobar("Producto con stock 0 está agotado", !(new Producto(3, "X", "X", 1, 0))->estaDisponible());
comprobar("Usuario se crea desde un rol válido", Usuario::desdeRol("cajero")?->nombre === "Cajero");
comprobar("Usuario no se crea desde un rol inválido", Usuario::desdeRol("hacker") === null);

/* ================= VALIDADORES ================= */
seccion("Validadores");
$validadorCliente = new ClienteValidator();
$clienteValido = new Cliente("CC", "1094123456", "Ana Pérez", "3001234567");
comprobar("Cliente vacío es válido (consumidor final)", $validadorCliente->validar($sinCliente) === []);
comprobar("Cliente completo y correcto es válido", $validadorCliente->validar($clienteValido) === []);
comprobar(
    "Cliente con tipo y teléfono inválidos da 2 errores",
    count($validadorCliente->validar(new Cliente("XX", "1094123456", "Ana Pérez", "12"))) === 2
);
$validadorProducto = new ProductoValidator();
comprobar("Producto válido se construye", $validadorProducto->crearProducto("A1", "Arroz", "3500", "10")->precio == 3500);
comprobar(
    "Producto con precio negativo es rechazado",
    mensajeDeError(fn () => $validadorProducto->crearProducto("A1", "Arroz", "-1", "10"), ValidacionException::class) !== null
);

/* ================= PATRONES STRATEGY Y FACTORY ================= */
seccion("Patrones Strategy y Factory (pagos)");
$fabrica = new MetodoPagoFactory();
comprobar("Factory crea la estrategia de efectivo", $fabrica->crear("Efectivo") instanceof PagoEfectivoStrategy);
comprobar("Factory crea la estrategia de tarjeta", $fabrica->crear("Tarjeta") instanceof PagoTarjetaStrategy);
comprobar("Factory rechaza un método desconocido", mensajeDeError(fn () => $fabrica->crear("Bitcoin")) === "Método de pago no válido. Regrese a la venta.");
comprobar("Efectivo: cambio = pagado - total (400)", $fabrica->crear("Efectivo")->calcularCambio(19600, 20000) == 400);
comprobar("Tarjeta: no hay cambio", $fabrica->crear("Tarjeta")->calcularCambio(19600, 20000) == 0);

/* ================= F-01 REGISTRAR VENTA ================= */
seccion("VentaService: F-01 Registrar venta");
$productos = new ProductoRepositoryFalso($arroz, $leche);
$ventas = new VentaRepositoryFalso();
$transaccion = new TransaccionFalsa();
$factura = crearVentaService($productos, $ventas, null, $transaccion)->registrar($items, $sinCliente, "Efectivo", 20000);
comprobar("Prueba A (efectivo 20000): total 19600", $factura->total == 19600);
comprobar("Prueba A: cambio 400", $factura->cambio == 400);
comprobar("Prueba A: se guardan 2 detalles", count($ventas->detalles) === 2);
comprobar("Prueba A: inventario queda en Arroz 8 y Leche 7", $productos->productos[1]->cantidad === 8 && $productos->productos[2]->cantidad === 7);
comprobar("Prueba A: la transacción se confirma", $transaccion->confirmada && !$transaccion->revertida);

$factura = crearVentaService(new ProductoRepositoryFalso($arroz, $leche))->registrar($items, $sinCliente, "Tarjeta", 19600);
comprobar("Prueba B (tarjeta 19600): cambio 0", $factura->cambio == 0);

$ventas = new VentaRepositoryFalso();
$error = mensajeDeError(fn () => crearVentaService(new ProductoRepositoryFalso($arroz, $leche), $ventas)->registrar($items, $sinCliente, "Efectivo", 1000));
comprobar("Prueba C (efectivo 1000): pago insuficiente es rechazado", str_starts_with((string) $error, "El valor pagado es inferior"));
comprobar("Prueba C: no se guarda nada en la base de datos", count($ventas->ventas) === 0);

$transaccion = new TransaccionFalsa();
$error = mensajeDeError(fn () => crearVentaService(new ProductoRepositoryFalso($arroz), null, null, $transaccion)->registrar($items, $sinCliente, "Efectivo", 20000));
comprobar("Producto inexistente es rechazado", $error === "Uno de los productos ya no existe.");
comprobar("Producto inexistente hace rollback", $transaccion->revertida && !$transaccion->confirmada);

$transaccion = new TransaccionFalsa();
$error = mensajeDeError(fn () => crearVentaService(new ProductoRepositoryFalso($arroz, new Producto(2, "LEC01", "Leche", 4200, 1)), null, null, $transaccion)->registrar($items, $sinCliente, "Efectivo", 20000));
comprobar("Inventario insuficiente es rechazado", $error === "No hay suficiente inventario de Leche.");
comprobar("Inventario insuficiente hace rollback", $transaccion->revertida);

$pendientes = new VentaPendienteRepositoryFalso();
$pendientes->pendientes[5] = array_values($items);
crearVentaService(new ProductoRepositoryFalso($arroz, $leche), null, $pendientes)->registrar($items, $sinCliente, "Efectivo", 20000, 5);
comprobar("Cobrar una venta pendiente la elimina", !$pendientes->existe(5));

$transaccion = new TransaccionFalsa();
$error = mensajeDeError(fn () => crearVentaService(new ProductoRepositoryFalso($arroz, $leche), null, null, $transaccion)->registrar($items, $sinCliente, "Efectivo", 20000, 99));
comprobar("Venta pendiente que ya no existe hace rollback", $transaccion->revertida && str_starts_with((string) $error, "La venta pendiente ya no existe"));

/* ================= CARRITO Y VENTAS EN ESPERA ================= */
seccion("CarritoService y VentaPendienteService");
$_SESSION = [];
$sesion = new Sesion();
$carrito = new CarritoService($sesion, new ProductoRepositoryFalso($arroz, $leche), new CalculadoraVenta());
$carrito->agregar(1, 2);
$carrito->agregar(1, 3);
comprobar("Agregar el mismo producto suma la cantidad (2 + 3 = 5)", $carrito->items()[1]->cantidad === 5);
comprobar("Producto inexistente no se agrega", mensajeDeError(fn () => $carrito->agregar(99, 1)) === "El producto no existe.");
comprobar("Cantidad cero no se agrega", mensajeDeError(fn () => $carrito->agregar(1, 0)) === "La cantidad debe ser mayor que cero.");
comprobar("No se puede superar el inventario", mensajeDeError(fn () => $carrito->agregar(1, 6)) === "La cantidad solicitada supera el inventario disponible.");
$carrito->eliminar(1);
comprobar("Eliminar deja el carrito vacío", $carrito->estaVacio());

$repoPendientes = new VentaPendienteRepositoryFalso();
$servicioPendientes = new VentaPendienteService($repoPendientes, $carrito, new TransaccionFalsa());
comprobar("No se guarda en espera un carrito vacío", mensajeDeError(fn () => $servicioPendientes->guardar()) === "Agrega productos antes de guardar una venta en espera.");
$carrito->agregar(2, 3);
$numero = $servicioPendientes->guardar();
comprobar("Guardar en espera devuelve el número 1 y vacía el carrito", $numero === 1 && $carrito->estaVacio());
$servicioPendientes->retomar(1);
comprobar("Retomar la venta recupera sus productos", $carrito->items()[2]->cantidad === 3 && $carrito->ventaPendienteId() === 1);
comprobar("Volver a guardarla conserva el mismo número", $servicioPendientes->guardar() === 1);

/* ================= PRODUCTOS Y SESIÓN ================= */
seccion("ProductoService (registrar, editar, eliminar, reponer) y AuthService");
$servicioProductos = new ProductoService(new ProductoRepositoryFalso($arroz, new Producto(3, "AZU01", "Azúcar", 2000, 0)), new ProductoValidator());
comprobar("Código repetido es rechazado", mensajeDeError(fn () => $servicioProductos->registrar("ARR01", "Otro", "1", "1")) === "No se pudo registrar el producto: ese código ya está registrado.");
comprobar("Reponer un producto agotado funciona", $servicioProductos->reponer("AZU01", "5")["tipo"] === "success");
comprobar("Reponer un producto con stock da advertencia", $servicioProductos->reponer("ARR01", "5")["tipo"] === "warning");
comprobar("Reponer un código inexistente da error", $servicioProductos->reponer("NADA", "5")["tipo"] === "danger");

$repoEdicion = new ProductoRepositoryFalso($arroz, $leche, new Producto(3, "AZU01", "Azúcar", 2000, 0));
$repoEdicion->conMovimientos = [1];
$servicioEdicion = new ProductoService($repoEdicion, new ProductoValidator());
$servicioEdicion->actualizar(2, "LEC01", "Leche entera", "4500");
comprobar("Editar cambia nombre y precio", $repoEdicion->productos[2]->nombre === "Leche entera" && $repoEdicion->productos[2]->precio == 4500);
comprobar("Editar no cambia el stock", $repoEdicion->productos[2]->cantidad === 10);
comprobar("Editar con el código de otro producto es rechazado", str_starts_with((string) mensajeDeError(fn () => $servicioEdicion->actualizar(2, "ARR01", "Leche", "4500")), "No se pudo actualizar"));
comprobar("Editar con precio inválido es rechazado", mensajeDeError(fn () => $servicioEdicion->actualizar(2, "LEC01", "Leche", "-5")) !== null);
comprobar("Eliminar un producto sin ventas funciona", $servicioEdicion->eliminar(3) === "Azúcar" && !isset($repoEdicion->productos[3]));
comprobar("Eliminar un producto con ventas es rechazado", str_starts_with((string) mensajeDeError(fn () => $servicioEdicion->eliminar(1)), "No se puede eliminar") && isset($repoEdicion->productos[1]));
comprobar("Eliminar un producto inexistente es rechazado", mensajeDeError(fn () => $servicioEdicion->eliminar(99)) === "El producto no existe.");

$_SESSION = [];
$auth = new AuthService(new Sesion());
comprobar("Ingresar como cajero guarda el usuario", $auth->iniciarSesion("cajero") && $auth->usuarioActual()?->esCajero());
comprobar("Un rol inventado no puede ingresar", !(new AuthService(new Sesion()))->iniciarSesion("hacker"));

echo PHP_EOL . "Resultado: $aprobadas aprobadas, $fallidas fallidas" . PHP_EOL;
exit($fallidas === 0 ? 0 : 1);
