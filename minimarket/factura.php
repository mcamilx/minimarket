<?php
require_once "auth.php";
requireRole(["admin", "cajero"]);
require_once "conexion.php";

if (!isset($_SESSION["carrito"]) || count($_SESSION["carrito"]) == 0) {
    header("Location: venta.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: venta.php");
    exit;
}

$datosCliente = [
    "cliente_tipo_documento" => trim(
        is_string($_POST["cliente_tipo_documento"] ?? null)
            ? $_POST["cliente_tipo_documento"]
            : ""
    ),
    "cliente_documento" => trim(
        is_string($_POST["cliente_documento"] ?? null)
            ? $_POST["cliente_documento"]
            : ""
    ),
    "cliente_nombre" => trim(
        is_string($_POST["cliente_nombre"] ?? null)
            ? $_POST["cliente_nombre"]
            : ""
    ),
    "cliente_telefono" => trim(
        is_string($_POST["cliente_telefono"] ?? null)
            ? $_POST["cliente_telefono"]
            : ""
    )
];
$_SESSION["datos_cliente"] = $datosCliente;

$camposClienteCompletos = array_filter($datosCliente, function ($valor) {
    return $valor !== "";
});
$erroresCliente = [];

if (count($camposClienteCompletos) > 0) {
    $tiposDocumentoValidos = ["CC", "CE", "NIT", "Pasaporte"];

    if (!in_array($datosCliente["cliente_tipo_documento"], $tiposDocumentoValidos, true)) {
        $erroresCliente[] = "Selecciona un tipo de documento válido.";
    }

    if (!preg_match('/^[A-Za-z0-9.-]{5,30}$/', $datosCliente["cliente_documento"])) {
        $erroresCliente[] = "El documento debe tener entre 5 y 30 caracteres alfanuméricos.";
    }

    if (
        strlen($datosCliente["cliente_nombre"]) < 2 ||
        strlen($datosCliente["cliente_nombre"]) > 100 ||
        preg_match('/[\x00-\x1F\x7F]/', $datosCliente["cliente_nombre"])
    ) {
        $erroresCliente[] = "Ingresa un nombre de cliente válido (máximo 100 caracteres).";
    }

    $digitosTelefono = preg_match_all('/[0-9]/', $datosCliente["cliente_telefono"]);
    if (
        !preg_match('/^[0-9+() -]{7,20}$/', $datosCliente["cliente_telefono"]) ||
        $digitosTelefono < 7 ||
        $digitosTelefono > 15
    ) {
        $erroresCliente[] = "El teléfono debe tener entre 7 y 15 dígitos.";
    }
}

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

$metodo_pago = $_POST["metodo_pago"] ?? "";
$monto_pagado = (float) ($_POST["monto_pagado"] ?? 0);

$total = 0;

foreach ($_SESSION["carrito"] as $item) {
    $total += $item["precio"] * $item["cantidad"];
}

/* VALIDAR MÉTODO DE PAGO */
if ($metodo_pago != "Efectivo" && $metodo_pago != "Tarjeta") {
    die("Método de pago no válido. Regrese a la venta.");
}

/* VALIDAR PAGO */
if ($monto_pagado < $total) {
    die(
        "El valor pagado es inferior al total de la compra. " .
        "<a href='venta.php'>Regresar a la venta</a>"
    );
}

$cambio = $monto_pagado - $total;

$conexion->begin_transaction();

try {

    /* REGISTRAR VENTA */

    $stmtVenta = $conexion->prepare(
        "INSERT INTO ventas
        (total, metodo_pago, monto_pagado, estado,
         cliente_tipo_documento, cliente_documento,
         cliente_nombre, cliente_telefono)
        VALUES (?, ?, ?, 'APROBADA', ?, ?, ?, ?)"
    );

    $stmtVenta->bind_param(
        "dsdssss",
        $total,
        $metodo_pago,
        $monto_pagado,
        $datosCliente["cliente_tipo_documento"],
        $datosCliente["cliente_documento"],
        $datosCliente["cliente_nombre"],
        $datosCliente["cliente_telefono"]
    );

    $stmtVenta->execute();

    $venta_id = $conexion->insert_id;


    /* REGISTRAR PRODUCTOS VENDIDOS */

    foreach ($_SESSION["carrito"] as $item) {

        $producto_id = $item["id"];
        $cantidadVendida = $item["cantidad"];
        $precio = $item["precio"];
        $subtotal = $precio * $cantidadVendida;


        /* COMPROBAR NUEVAMENTE EL INVENTARIO */

        $stmtStock = $conexion->prepare(
            "SELECT cantidad
             FROM productos
             WHERE id = ?
             FOR UPDATE"
        );

        $stmtStock->bind_param(
            "i",
            $producto_id
        );

        $stmtStock->execute();

        $resultadoStock =
            $stmtStock->get_result();

        $productoStock =
            $resultadoStock->fetch_assoc();

        if (!$productoStock) {
            throw new Exception(
                "Uno de los productos ya no existe."
            );
        }

        if ($productoStock["cantidad"] < $cantidadVendida) {
            throw new Exception(
                "No hay suficiente inventario de " .
                $item["nombre"] . "."
            );
        }


        /* GUARDAR DETALLE DE LA VENTA */

        $stmtDetalle = $conexion->prepare(
            "INSERT INTO detalle_venta
            (venta_id, producto_id, cantidad,
             precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmtDetalle->bind_param(
            "iiidd",
            $venta_id,
            $producto_id,
            $cantidadVendida,
            $precio,
            $subtotal
        );

        $stmtDetalle->execute();


        /* DESCONTAR INVENTARIO */

        $stmtActualizar = $conexion->prepare(
            "UPDATE productos
             SET cantidad = cantidad - ?
             WHERE id = ?"
        );

        $stmtActualizar->bind_param(
            "ii",
            $cantidadVendida,
            $producto_id
        );

        $stmtActualizar->execute();
    }

    $pendienteId = (int) ($_SESSION["venta_pendiente_id"] ?? 0);
    if ($pendienteId > 0) {
        $stmtEliminarPendiente = $conexion->prepare(
            "DELETE FROM ventas_pendientes WHERE id = ?"
        );
        $stmtEliminarPendiente->bind_param("i", $pendienteId);
        $stmtEliminarPendiente->execute();

        if ($stmtEliminarPendiente->affected_rows !== 1) {
            throw new Exception(
                "La venta pendiente ya no existe; no se pudo confirmar el cobro."
            );
        }
    }


    /* CONFIRMAR TODOS LOS CAMBIOS */

    $conexion->commit();

} catch (Exception $e) {

    $conexion->rollback();

    die(
        "No se pudo completar la venta: " .
        htmlspecialchars($e->getMessage()) .
        "<br><a href='venta.php'>Regresar</a>"
    );
}

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
                        $subtotal =
                            $item["precio"] *
                            $item["cantidad"];
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