<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["carrito"])) {
    $_SESSION["carrito"] = [];
}

$mensaje = "";

/* AGREGAR PRODUCTO AL CARRITO */
if (isset($_POST["agregar"])) {

    $producto_id = (int) $_POST["producto_id"];
    $cantidad = (int) $_POST["cantidad"];

    $stmt = $conexion->prepare(
        "SELECT id, codigo, nombre, precio, cantidad
         FROM productos
         WHERE id = ?"
    );

    $stmt->bind_param("i", $producto_id);
    $stmt->execute();

    $producto = $stmt->get_result()->fetch_assoc();

    if (!$producto) {

        $mensaje = "El producto no existe.";

    } elseif ($cantidad <= 0) {

        $mensaje = "La cantidad debe ser mayor que cero.";

    } elseif ($cantidad > $producto["cantidad"]) {

        $mensaje = "No hay suficientes unidades disponibles.";

    } else {

        $cantidadCarrito = 0;

        if (isset($_SESSION["carrito"][$producto_id])) {
            $cantidadCarrito =
                $_SESSION["carrito"][$producto_id]["cantidad"];
        }

        if (($cantidadCarrito + $cantidad) > $producto["cantidad"]) {

            $mensaje = "La cantidad solicitada supera el inventario disponible.";

        } else {

            $_SESSION["carrito"][$producto_id] = [
                "id" => $producto["id"],
                "codigo" => $producto["codigo"],
                "nombre" => $producto["nombre"],
                "precio" => $producto["precio"],
                "cantidad" => $cantidadCarrito + $cantidad
            ];

            $mensaje = "Producto agregado a la compra.";
        }
    }
}


/* ELIMINAR PRODUCTO DEL CARRITO */
if (isset($_GET["eliminar"])) {

    $idEliminar = (int) $_GET["eliminar"];

    if (isset($_SESSION["carrito"][$idEliminar])) {
        unset($_SESSION["carrito"][$idEliminar]);
    }

    header("Location: venta.php");
    exit;
}


/* CALCULAR TOTAL */
$total = 0;

foreach ($_SESSION["carrito"] as $item) {

    $subtotal = $item["precio"] * $item["cantidad"];
    $total += $subtotal;
}


/* PRODUCTOS DISPONIBLES */
$resultado = $conexion->query(
    "SELECT *
     FROM productos
     WHERE cantidad > 0
     ORDER BY nombre ASC"
);
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Nueva venta - Minimarket</title>

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

    <h2 class="mb-4">
        Nueva venta
    </h2>


    <?php if ($mensaje != ""): ?>

        <div class="alert alert-info">

            <?php echo htmlspecialchars($mensaje); ?>

        </div>

    <?php endif; ?>


    <!-- SELECCIONAR PRODUCTO -->

    <div class="card shadow-sm mb-4">

        <div class="card-header">
            Agregar producto
        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Producto
                        </label>

                        <select
                            name="producto_id"
                            class="form-select"
                            required>

                            <option value="">
                                Seleccione un producto
                            </option>

                            <?php while ($producto = $resultado->fetch_assoc()): ?>

                                <option value="<?php echo $producto["id"]; ?>">

                                    <?php
                                    echo htmlspecialchars(
                                        $producto["codigo"] . " - " .
                                        $producto["nombre"]
                                    );
                                    ?>

                                    - $<?php
                                    echo number_format(
                                        $producto["precio"],
                                        0,
                                        ",",
                                        "."
                                    );
                                    ?>

                                    (Stock:
                                    <?php echo $producto["cantidad"]; ?>)

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">
                            Cantidad
                        </label>

                        <input
                            type="number"
                            name="cantidad"
                            class="form-control"
                            min="1"
                            value="1"
                            required>

                    </div>


                    <div class="col-md-3 d-flex align-items-end">

                        <button
                            type="submit"
                            name="agregar"
                            class="btn btn-primary w-100">

                            Agregar

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- RESUMEN DE COMPRA -->

    <div class="card shadow-sm">

        <div class="card-header">
            Resumen de compra
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($_SESSION["carrito"]) > 0): ?>

                        <?php foreach ($_SESSION["carrito"] as $item): ?>

                            <?php
                            $subtotal =
                                $item["precio"] * $item["cantidad"];
                            ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars($item["codigo"]);
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($item["nombre"]);
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
                                    <?php echo $item["cantidad"]; ?>
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

                                <td>

                                    <a
                                        href="venta.php?eliminar=<?php echo $item["id"]; ?>"
                                        class="btn btn-sm btn-danger">

                                        Eliminar

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center text-muted">

                                No hay productos agregados.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <div class="text-end">

                <h3>
                    Total:
                    $<?php
                    echo number_format(
                        $total,
                        0,
                        ",",
                        "."
                    );
                    ?>
                </h3>

            </div>


            <?php if (count($_SESSION["carrito"]) > 0): ?>

                <hr>

                <h5 class="mb-3">
                    Información de pago
                </h5>

                <form
                    method="POST"
                    action="factura.php">

                    <div class="row g-3">

                        <div class="col-md-4">

                            <label class="form-label">
                                Método de pago
                            </label>

                            <select
                                name="metodo_pago"
                                id="metodo_pago"
                                class="form-select"
                                required>

                                <option value="">
                                    Seleccione
                                </option>

                                <option value="Efectivo">
                                    Efectivo
                                </option>

                                <option value="Tarjeta">
                                    Tarjeta
                                </option>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Valor pagado
                            </label>

                            <input
                                type="number"
                                name="monto_pagado"
                                id="monto_pagado"
                                class="form-control"
                                min="0"
                                step="0.01"
                                required>

                        </div>


                        <div class="col-md-4 d-flex align-items-end">

                            <button
                                type="submit"
                                class="btn btn-success w-100">

                                Finalizar venta

                            </button>

                        </div>

                    </div>

                </form>

            <?php endif; ?>

        </div>

    </div>

</div>

<script src="script.js"></script>

</body>
</html>