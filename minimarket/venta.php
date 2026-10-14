<?php
require_once "auth.php";
requireRole(["admin", "cajero"]);
require_once "conexion.php";

if (!isset($_SESSION["carrito"])) {
    $_SESSION["carrito"] = [];
}

$mensaje = "";

if (isset($_POST["retomar_pendiente"])) {
    $pendienteId = (int) ($_POST["venta_pendiente_id"] ?? 0);

    if (count($_SESSION["carrito"]) > 0) {
        $mensaje = "Termina o guarda la venta actual antes de retomar otra.";
    } elseif ($pendienteId <= 0) {
        $mensaje = "Selecciona una venta pendiente válida.";
    } else {
        $stmtPendiente = $conexion->prepare(
            "SELECT id FROM ventas_pendientes WHERE id = ?"
        );
        $stmtPendiente->bind_param("i", $pendienteId);
        $stmtPendiente->execute();
        $existePendiente = $stmtPendiente->get_result()->fetch_assoc();

        if (!$existePendiente) {
            $mensaje = "La venta pendiente ya no existe.";
        } else {
            $stmtDetallePendiente = $conexion->prepare(
                "SELECT producto_id, codigo, nombre, precio, cantidad
                 FROM detalle_venta_pendiente
                 WHERE venta_pendiente_id = ?
                 ORDER BY id ASC"
            );
            $stmtDetallePendiente->bind_param("i", $pendienteId);
            $stmtDetallePendiente->execute();
            $lineasPendientes = $stmtDetallePendiente->get_result();
            $carritoRetomado = [];

            while ($linea = $lineasPendientes->fetch_assoc()) {
                $carritoRetomado[$linea["producto_id"]] = [
                    "id" => (int) $linea["producto_id"],
                    "codigo" => $linea["codigo"],
                    "nombre" => $linea["nombre"],
                    "precio" => $linea["precio"],
                    "cantidad" => (int) $linea["cantidad"]
                ];
            }

            if (count($carritoRetomado) === 0) {
                $mensaje = "La venta pendiente no tiene productos.";
            } else {
                $_SESSION["carrito"] = $carritoRetomado;
                $_SESSION["venta_pendiente_id"] = $pendienteId;
                $mensaje = "Venta pendiente #" . $pendienteId . " retomada.";
            }
        }
    }
}

/* GUARDAR O ACTUALIZAR UNA VENTA PENDIENTE */
if (isset($_POST["guardar_pendiente"])) {
    if (count($_SESSION["carrito"]) === 0) {
        $mensaje = "Agrega productos antes de guardar una venta en espera.";
    } else {
        $totalPendiente = 0;
        foreach ($_SESSION["carrito"] as $item) {
            $totalPendiente += $item["precio"] * $item["cantidad"];
        }

        $pendienteId = (int) ($_SESSION["venta_pendiente_id"] ?? 0);
        $conexion->begin_transaction();

        try {
            if ($pendienteId > 0) {
                $stmtExiste = $conexion->prepare(
                    "SELECT id FROM ventas_pendientes WHERE id = ? FOR UPDATE"
                );
                $stmtExiste->bind_param("i", $pendienteId);
                $stmtExiste->execute();

                if (!$stmtExiste->get_result()->fetch_assoc()) {
                    throw new Exception("La venta pendiente ya no existe.");
                }

                $stmtEliminarLineas = $conexion->prepare(
                    "DELETE FROM detalle_venta_pendiente
                     WHERE venta_pendiente_id = ?"
                );
                $stmtEliminarLineas->bind_param("i", $pendienteId);
                $stmtEliminarLineas->execute();

                $stmtActualizarTotal = $conexion->prepare(
                    "UPDATE ventas_pendientes SET total = ? WHERE id = ?"
                );
                $stmtActualizarTotal->bind_param(
                    "di",
                    $totalPendiente,
                    $pendienteId
                );
                $stmtActualizarTotal->execute();
            } else {
                $stmtCrearPendiente = $conexion->prepare(
                    "INSERT INTO ventas_pendientes (total) VALUES (?)"
                );
                $stmtCrearPendiente->bind_param("d", $totalPendiente);
                $stmtCrearPendiente->execute();
                $pendienteId = $conexion->insert_id;
            }

            $stmtGuardarLinea = $conexion->prepare(
                "INSERT INTO detalle_venta_pendiente
                 (venta_pendiente_id, producto_id, codigo, nombre, precio, cantidad)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            foreach ($_SESSION["carrito"] as $item) {
                $productoId = (int) $item["id"];
                $codigo = $item["codigo"];
                $nombre = $item["nombre"];
                $precio = (float) $item["precio"];
                $cantidad = (int) $item["cantidad"];
                $stmtGuardarLinea->bind_param(
                    "iissdi",
                    $pendienteId,
                    $productoId,
                    $codigo,
                    $nombre,
                    $precio,
                    $cantidad
                );
                $stmtGuardarLinea->execute();
            }

            $conexion->commit();
            $_SESSION["carrito"] = [];
            unset($_SESSION["venta_pendiente_id"]);
            $mensaje = "Venta guardada en espera como #" . $pendienteId . ".";
        } catch (Throwable $error) {
            $conexion->rollback();
            $mensaje = "No se pudo guardar la venta pendiente: " .
                $error->getMessage();
        }
    }
}

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

$stmtPendientes = $conexion->prepare(
    "SELECT vp.id, vp.fecha_hora, vp.total, COUNT(dvp.id) AS productos
     FROM ventas_pendientes vp
     LEFT JOIN detalle_venta_pendiente dvp
        ON dvp.venta_pendiente_id = vp.id
     GROUP BY vp.id, vp.fecha_hora, vp.total
     ORDER BY vp.id DESC"
);
$stmtPendientes->execute();
$pendientes = $stmtPendientes->get_result();
$datosCliente = $_SESSION["datos_cliente"] ?? [];

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

<?php require "navbar.php"; ?>


<div class="container py-5">

    <h2 class="mb-4">
        Nueva venta
    </h2>


    <?php if ($mensaje != ""): ?>

        <div class="alert alert-info">

            <?php echo htmlspecialchars($mensaje); ?>

        </div>

    <?php endif; ?>

    <?php if (isset($_SESSION["venta_pendiente_id"])): ?>
        <div class="alert alert-warning">
            Estás retomando la venta pendiente
            #<?php echo (int) $_SESSION["venta_pendiente_id"]; ?>.
            Al guardarla en espera nuevamente conservará el mismo número.
        </div>
    <?php endif; ?>

    <?php if ($pendientes->num_rows > 0): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header">Ventas en espera</div>
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Venta pendiente</th>
                            <th>Guardada</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pendiente = $pendientes->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo (int) $pendiente["id"]; ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($pendiente["fecha_hora"])); ?></td>
                                <td><?php echo (int) $pendiente["productos"]; ?></td>
                                <td>$<?php echo number_format($pendiente["total"], 0, ",", "."); ?></td>
                                <td>
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="venta_pendiente_id"
                                               value="<?php echo (int) $pendiente["id"]; ?>">
                                        <button type="submit" name="retomar_pendiente"
                                                class="btn btn-sm btn-outline-primary"
                                                <?php echo count($_SESSION["carrito"]) > 0 ? "disabled" : ""; ?>>
                                            Retomar venta
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
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
                <form method="POST" class="mb-4">
                    <button type="submit" name="guardar_pendiente"
                            class="btn btn-outline-warning">
                        Guardar venta en espera
                    </button>
                </form>
            <?php endif; ?>


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

                        <div class="col-12">
                            <hr>
                            <h5>Datos del cliente (opcionales)</h5>
                            <p class="text-muted mb-3">
                                Completa todos los campos si el cliente solicita factura a su nombre.
                            </p>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tipo de documento</label>
                            <select name="cliente_tipo_documento" class="form-select">
                                <option value="">Seleccione</option>
                                <?php
                                $tiposDocumento = [
                                    "CC" => "Cédula de ciudadanía",
                                    "CE" => "Cédula de extranjería",
                                    "NIT" => "NIT",
                                    "Pasaporte" => "Pasaporte"
                                ];
                                foreach ($tiposDocumento as $codigoTipo => $nombreTipo):
                                ?>
                                    <option value="<?php echo htmlspecialchars($codigoTipo); ?>"
                                        <?php echo ($datosCliente["cliente_tipo_documento"] ?? "") === $codigoTipo ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($nombreTipo); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Número de documento</label>
                            <input type="text" name="cliente_documento" class="form-control"
                                   maxlength="30" pattern="[A-Za-z0-9.-]{5,30}"
                                   value="<?php echo htmlspecialchars($datosCliente["cliente_documento"] ?? ""); ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Nombre del cliente</label>
                            <input type="text" name="cliente_nombre" class="form-control"
                                   maxlength="100"
                                   value="<?php echo htmlspecialchars($datosCliente["cliente_nombre"] ?? ""); ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Teléfono de contacto</label>
                            <input type="tel" name="cliente_telefono" class="form-control"
                                   maxlength="30" pattern="[0-9+() -]{7,20}"
                                   value="<?php echo htmlspecialchars($datosCliente["cliente_telefono"] ?? ""); ?>">
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