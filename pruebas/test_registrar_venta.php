<?php
/*
 * Pruebas de F-01 Registrar venta (sin base de datos real).
 * Ejecutar desde la raíz del repositorio:
 *     php pruebas/test_registrar_venta.php
 */
require_once __DIR__ . "/../minimarket/servicios_venta.php";

$aprobadas = 0;
$fallidas = 0;

function comprobar(string $nombre, bool $condicion): void
{
    global $aprobadas, $fallidas;
    if ($condicion) {
        $aprobadas++;
        echo "[OK]    $nombre\n";
    } else {
        $fallidas++;
        echo "[FALLA] $nombre\n";
    }
}

/* Repositorio falso: guarda en memoria lo que haría la base de datos. */
class VentaRepositoryFalso extends VentaRepository
{
    public array $stock;
    public array $detalles = [];
    public array $ventas = [];
    public bool $confirmada = false;
    public bool $revertida = false;
    public bool $pendienteExiste = true;

    public function __construct(array $stock) { $this->stock = $stock; }
    public function iniciarTransaccion(): void {}
    public function confirmar(): void { $this->confirmada = true; }
    public function revertir(): void { $this->revertida = true; }
    public function insertarVenta(float $t, string $m, float $p, array $c): int
    {
        $this->ventas[] = ["total" => $t, "metodo" => $m, "pagado" => $p];
        return 99;
    }
    public function obtenerStockBloqueado(int $id): ?int { return $this->stock[$id] ?? null; }
    public function insertarDetalle(int $v, int $p, int $c, float $pu, float $s): void
    {
        $this->detalles[] = ["producto" => $p, "cantidad" => $c, "subtotal" => $s];
    }
    public function descontarInventario(int $id, int $c): void { $this->stock[$id] -= $c; }
    public function eliminarVentaPendiente(int $id): bool { return $this->pendienteExiste; }
}

$carrito = [
    1 => ["id" => 1, "codigo" => "A1", "nombre" => "Arroz", "precio" => "3500.00", "cantidad" => 2],
    2 => ["id" => 2, "codigo" => "L1", "nombre" => "Leche", "precio" => "4200.00", "cantidad" => 3],
];
$sinCliente = ValidadorCliente::leerDesdeFormulario([]);

/* --- Cálculo del total --- */
comprobar("Subtotal = precio x cantidad", CalculadoraVenta::subtotal($carrito[1]) == 7000);
comprobar("Total = suma de subtotales (19600)", CalculadoraVenta::total($carrito) == 19600);
comprobar("Total de carrito vacío = 0", CalculadoraVenta::total([]) == 0);

/* --- Validación del cliente --- */
comprobar("Cliente vacío es válido (opcional)", ValidadorCliente::validar($sinCliente) === []);
$clienteOk = ["cliente_tipo_documento" => "CC", "cliente_documento" => "1094123456",
              "cliente_nombre" => "Ana Pérez", "cliente_telefono" => "3001234567"];
comprobar("Cliente completo y correcto es válido", ValidadorCliente::validar($clienteOk) === []);
$clienteMal = $clienteOk; $clienteMal["cliente_tipo_documento"] = "XX"; $clienteMal["cliente_telefono"] = "12";
comprobar("Cliente con tipo y teléfono inválidos da 2 errores", count(ValidadorCliente::validar($clienteMal)) === 2);
comprobar("leerDesdeFormulario quita espacios",
    ValidadorCliente::leerDesdeFormulario(["cliente_nombre" => "  Ana  "])["cliente_nombre"] === "Ana");

/* --- Registro de venta válida en efectivo --- */
$repo = new VentaRepositoryFalso([1 => 10, 2 => 10]);
$r = (new RegistrarVentaService($repo))->registrar($carrito, $sinCliente, "Efectivo", 20000, 0);
comprobar("Venta válida devuelve id", $r["venta_id"] === 99);
comprobar("Venta válida: total 19600", $r["total"] == 19600);
comprobar("Efectivo: cambio 400", $r["cambio"] == 400);
comprobar("Se guardan 2 detalles", count($repo->detalles) === 2);
comprobar("Se descuenta el inventario (8 y 7)", $repo->stock[1] === 8 && $repo->stock[2] === 7);
comprobar("La transacción se confirma", $repo->confirmada && !$repo->revertida);

/* --- Tarjeta: sin cambio --- */
$repo = new VentaRepositoryFalso([1 => 10, 2 => 10]);
$r = (new RegistrarVentaService($repo))->registrar($carrito, $sinCliente, "Tarjeta", 19600, 0);
comprobar("Tarjeta: cambio 0", $r["cambio"] == 0);

/* --- Pago insuficiente --- */
$repo = new VentaRepositoryFalso([1 => 10, 2 => 10]);
try {
    (new RegistrarVentaService($repo))->registrar($carrito, $sinCliente, "Efectivo", 1000, 0);
    comprobar("Pago insuficiente es rechazado", false);
} catch (InvalidArgumentException $e) {
    comprobar("Pago insuficiente es rechazado", true);
    comprobar("Pago insuficiente no toca la BD", count($repo->ventas) === 0);
}

/* --- Método de pago inválido --- */
try {
    (new RegistrarVentaService(new VentaRepositoryFalso([])))->registrar($carrito, $sinCliente, "Bitcoin", 50000, 0);
    comprobar("Método de pago inválido es rechazado", false);
} catch (InvalidArgumentException $e) {
    comprobar("Método de pago inválido es rechazado", true);
}

/* --- Producto inexistente --- */
$repo = new VentaRepositoryFalso([1 => 10]);   // el producto 2 no existe
try {
    (new RegistrarVentaService($repo))->registrar($carrito, $sinCliente, "Efectivo", 20000, 0);
    comprobar("Producto inexistente es rechazado", false);
} catch (Exception $e) {
    comprobar("Producto inexistente es rechazado", $e->getMessage() === "Uno de los productos ya no existe.");
    comprobar("Producto inexistente hace rollback", $repo->revertida && !$repo->confirmada);
}

/* --- Inventario insuficiente --- */
$repo = new VentaRepositoryFalso([1 => 10, 2 => 1]);
try {
    (new RegistrarVentaService($repo))->registrar($carrito, $sinCliente, "Efectivo", 20000, 0);
    comprobar("Inventario insuficiente es rechazado", false);
} catch (Exception $e) {
    comprobar("Inventario insuficiente es rechazado", $e->getMessage() === "No hay suficiente inventario de Leche.");
    comprobar("Inventario insuficiente hace rollback", $repo->revertida);
}

/* --- Venta pendiente que ya no existe --- */
$repo = new VentaRepositoryFalso([1 => 10, 2 => 10]);
$repo->pendienteExiste = false;
try {
    (new RegistrarVentaService($repo))->registrar($carrito, $sinCliente, "Efectivo", 20000, 5);
    comprobar("Pendiente inexistente es rechazada", false);
} catch (Exception $e) {
    comprobar("Pendiente inexistente es rechazada", $repo->revertida);
}

echo "\nResultado: $aprobadas aprobadas, $fallidas fallidas\n";
exit($fallidas === 0 ? 0 : 1);
