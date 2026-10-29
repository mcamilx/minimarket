<?php
/*
 * Controlador de F-01 Registrar venta.
 * Responsabilidades: recibir la solicitud, delegar en los servicios
 * y mostrar la respuesta (errores o factura).
 * La lógica de negocio y el SQL están en servicios_venta.php.
 */
require_once "auth.php";
requireRole(["admin", "cajero"]);
require_once "conexion.php";
require_once "servicios_venta.php";

if (!isset($_SESSION["carrito"]) || count($_SESSION["carrito"]) == 0) {
    header("Location: venta.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: venta.php");
    exit;
}

/* 1. LEER Y VALIDAR DATOS DEL CLIENTE */

$datosCliente = ValidadorCliente::leerDesdeFormulario($_POST);
$_SESSION["datos_cliente"] = $datosCliente;

$erroresCliente = ValidadorCliente::validar($datosCliente);

if (count($erroresCliente) > 0) {
    http_response_code(422);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Revisar datos del cliente - Minimarket</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
              rel="stylesheet">
    </head>
    <body class="bg-light">
    <?php require "navbar.php"; ?>
    <main class="container py-5">
        <div class="alert alert-danger">
            <h2 class="h5">Revisa los datos del cliente</h2>
            <ul class="mb-0">
                <?php foreach ($erroresCliente as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <p>La venta sigue en curso y no se ha descontado el inventario.</p>
        <a href="venta.php" class="btn btn-primary">Volver a la venta</a>
    </main>
    </body>
    </html>
    <?php
    exit;
}

/* 2. LEER DATOS DEL PAGO */

$metodo_pago = is_string($_POST["metodo_pago"] ?? null)
    ? $_POST["metodo_pago"]
    : "";
$monto_pagado = (float) ($_POST["monto_pagado"] ?? 0);
$pendienteId = (int) ($_SESSION["venta_pendiente_id"] ?? 0);

/* 3. DELEGAR EL REGISTRO AL SERVICIO */

$servicio = new RegistrarVentaService(new VentaRepository($conexion));

try {
    $resultado = $servicio->registrar(
        $_SESSION["carrito"],
        $datosCliente,
        $metodo_pago,
        $monto_pagado,
        $pendienteId
    );
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    die(
        htmlspecialchars($error->getMessage()) .
        " <a href='venta.php'>Regresar a la venta</a>"
    );
} catch (Exception $e) {
    die(
        "No se pudo completar la venta: " .
        htmlspecialchars($e->getMessage()) .
        "<br><a href='venta.php'>Regresar</a>"
    );
}

$venta_id = $resultado["venta_id"];
$total = $resultado["total"];
$cambio = $resultado["cambio"];

/* 4. PREPARAR LA VISTA Y LIMPIAR LA SESIÓN */

$carritoFactura = $_SESSION["carrito"];

$_SESSION["carrito"] = [];
unset($_SESSION["venta_pendiente_id"], $_SESSION["datos_cliente"]);
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Factura - Minimarket</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body class="bg-light">

<?php require "navbar.php"; ?>


<div class="container py-5">

    <div class="card shadow-sm mx-auto"
         style="max-width: 750px;">

        <div class="card-header text-center">

            <h3 class="mb-0">
                Factura de venta
            </h3>

        </div>


        <div class="card-body">

            <div class="row mb-4">

                <div class="col">

                    <strong>
                        Venta:
                    </strong>

                    #<?php echo $venta_id; ?>

                </div>

                <div class="col text-end">

                    <strong>
                        Fecha:
                    </strong>

                    <?php echo date("d/m/Y H:i"); ?>

                </div>

            </div>

            <?php if ($datosCliente["cliente_nombre"] !== ""): ?>
                <div class="card card-body mb-4">
                    <h5>Datos del cliente</h5>
                    <p class="mb-1">
                        <strong>Nombre:</strong>
                        <?php echo htmlspecialchars($datosCliente["cliente_nombre"]); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Documento:</strong>
                        <?php echo htmlspecialchars($datosCliente["cliente_tipo_documento"]); ?>
                        <?php echo htmlspecialchars($datosCliente["cliente_documento"]); ?>
                    </p>
                    <p class="mb-0">
                        <strong>Teléfono:</strong>
                        <?php echo htmlspecialchars($datosCliente["cliente_telefono"]); ?>
                    </p>
                </div>
            <?php endif; ?>


            <div class="table-responsive">

                <table class="table">

                    <thead>

                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($carritoFactura as $item): ?>

                        <?php
                        $subtotal = CalculadoraVenta::subtotal($item);
                        ?>

                        <tr>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $item["nombre"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo $item["cantidad"];
                                ?>
                            </td>

                            <td>
                                $<?php
                                echo number_format(
                                    $item["precio"],
                                    0,
                                    ",",
                                    "."
                                );
                                ?>
                            </td>

                            <td>
                                $<?php
                                echo number_format(
                                    $subtotal,
                                    0,
                                    ",",
                                    "."
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <hr>


            <div class="text-end">

                <p>
                    <strong>Método de pago:</strong>
                    <?php
                    echo htmlspecialchars($metodo_pago);
                    ?>
                </p>

                <p>
                    <strong>Total:</strong>

                    $<?php
                    echo number_format(
                        $total,
                        0,
                        ",",
                        "."
                    );
                    ?>
                </p>

                <p>
                    <strong>Valor pagado:</strong>

                    $<?php
                    echo number_format(
                        $monto_pagado,
                        0,
                        ",",
                        "."
                    );
                    ?>
                </p>


                <?php if ($metodo_pago == "Efectivo"): ?>

                    <p>
                        <strong>Cambio:</strong>

                        $<?php
                        echo number_format(
                            $cambio,
                            0,
                            ",",
                            "."
                        );
                        ?>
                    </p>

                <?php endif; ?>

            </div>


            <div class="alert alert-success mt-4">

                Venta registrada y productos
                entregados correctamente.

            </div>


            <div class="d-flex gap-2">

                <?php if (currentRole() === "cajero"): ?>
                    <a href="venta.php" class="btn btn-success">
                        Nueva venta
                    </a>
                <?php endif; ?>

                <a href="index.php"
                   class="btn btn-dark">

                    Inicio

                </a>

            </div>

        </div>

    </div>

</div>

</body>

</html>