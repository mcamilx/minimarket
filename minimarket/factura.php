<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["carrito"]) || count($_SESSION["carrito"]) == 0) {
    header("Location: venta.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: venta.php");
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
        (total, metodo_pago, monto_pagado, estado)
        VALUES (?, ?, ?, 'PAGADA')"
    );

    $stmtVenta->bind_param(
        "dsd",
        $total,
        $metodo_pago,
        $monto_pagado
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

<nav class="navbar navbar-dark bg-dark">

    <div class="container">

        <a class="navbar-brand" href="index.php">
            Sistema Minimarket
        </a>

        <a href="index.php" class="btn btn-outline-light">
            Inicio
        </a>

    </div>

</nav>


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

                <a href="venta.php"
                   class="btn btn-success">

                    Nueva venta

                </a>

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