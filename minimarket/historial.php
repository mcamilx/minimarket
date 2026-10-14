<?php
require_once "auth.php";
requireRole(["admin", "cajero"]);
require_once "conexion.php";

$esAdministrador = currentRole() === "admin";
$buscar = trim(
    is_string($_GET["buscar"] ?? null) ? $_GET["buscar"] : ""
);

$sql = "(SELECT v.id, v.fecha_hora, v.total, v.metodo_pago, v.monto_pagado,
               CASE WHEN v.estado = 'PAGADA' THEN 'APROBADA' ELSE v.estado END AS estado,
               v.cliente_tipo_documento, v.cliente_documento,
               v.cliente_nombre, v.cliente_telefono, 0 AS es_pendiente
        FROM ventas v
        UNION ALL
        SELECT vp.id, vp.fecha_hora, vp.total, '' AS metodo_pago,
               0 AS monto_pagado, 'PENDIENTE' AS estado,
               NULL AS cliente_tipo_documento, NULL AS cliente_documento,
               NULL AS cliente_nombre, NULL AS cliente_telefono, 1 AS es_pendiente
        FROM ventas_pendientes vp)";

if ($esAdministrador && $buscar !== "") {
    $numeroFactura = ctype_digit($buscar) ? (int) $buscar : 0;
    $coincidencia = "%" . $buscar . "%";
    $stmt = $conexion->prepare(
        "SELECT * FROM " . $sql . " AS historial
         WHERE (es_pendiente = 0 AND id = ?)
                OR (es_pendiente = 1 AND id = ?)
                OR cliente_nombre LIKE ?
                OR cliente_documento LIKE ?
                OR cliente_telefono LIKE ?
         ORDER BY fecha_hora DESC, es_pendiente DESC, id DESC"
    );
    $stmt->bind_param(
        "iisss",
        $numeroFactura,
        $numeroFactura,
        $coincidencia,
        $coincidencia,
        $coincidencia
    );
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $resultado = $conexion->query(
        "SELECT * FROM " . $sql .
        " AS historial ORDER BY fecha_hora DESC, es_pendiente DESC, id DESC"
    );
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Historial de ventas - Minimarket</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body class="bg-light">

<?php require "navbar.php"; ?>


<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2>Historial de ventas</h2>

        <?php if (!$esAdministrador): ?>
            <a href="venta.php" class="btn btn-success">
                Nueva venta
            </a>
        <?php endif; ?>

    </div>

    <?php if ($esAdministrador): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET">
                    <label for="buscar" class="form-label">
                        Buscar por número de factura, nombre, documento o teléfono
                    </label>
                    <div class="input-group">
                        <input type="search" id="buscar" name="buscar"
                               class="form-control"
                               placeholder="Número de factura o datos del cliente"
                               value="<?php echo htmlspecialchars($buscar); ?>">
                        <button type="submit" class="btn btn-dark">Buscar</button>
                        <a href="historial.php" class="btn btn-outline-secondary">
                            Mostrar todas
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>


    <div class="card shadow-sm">

        <div class="card-header">
            Ventas realizadas
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead>

                        <tr>
                            <th>Venta</th>
                            <th>Fecha y hora</th>
                            <th>Cliente</th>
                            <th>Documento</th>
                            <th>Total</th>
                            <th>Método de pago</th>
                            <th>Valor pagado</th>
                            <th>Estado</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($venta = $resultado->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    #<?php
                                    echo $venta["es_pendiente"]
                                        ? "P-" . (int) $venta["id"]
                                        : (int) $venta["id"];
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        "d/m/Y H:i",
                                        strtotime($venta["fecha_hora"])
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $venta["cliente_nombre"] ?:
                                            ($venta["es_pendiente"] ? "—" : "Consumidor final")
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php if ($venta["cliente_documento"]): ?>
                                        <?php
                                        echo htmlspecialchars(
                                            $venta["cliente_tipo_documento"] . " " .
                                            $venta["cliente_documento"]
                                        );
                                        ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>

                                <td>
                                    $<?php echo number_format($venta["total"], 0, ",", "."); ?>
                                </td>

                                <td>
                                    <?php echo $venta["metodo_pago"]
                                        ? htmlspecialchars($venta["metodo_pago"])
                                        : "—"; ?>
                                </td>

                                <td>
                                    <?php if ($venta["es_pendiente"]): ?>
                                        —
                                    <?php else: ?>
                                        $<?php echo number_format($venta["monto_pagado"], 0, ",", "."); ?>
                                    <?php endif; ?>
                                </td>

                                <td>

                                    <span class="badge <?php echo $venta["estado"] === "PENDIENTE" ? "bg-warning text-dark" : "bg-success"; ?>">

                                        <?php
                                        echo htmlspecialchars(
                                            $venta["estado"]
                                        );
                                        ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="8"
                                class="text-center text-muted">

                                <?php echo $buscar !== "" ? "No se encontraron ventas con esos datos." : "Todavía no hay ventas registradas."; ?>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <div class="mt-4">

        <a href="index.php"
           class="btn btn-secondary">

            Volver al inicio

        </a>

    </div>

</div>

</body>

</html>