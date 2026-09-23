<?php
require_once "conexion.php";

$sql = "SELECT id, fecha_hora, total, metodo_pago, monto_pagado, estado
        FROM ventas
        ORDER BY id DESC";

$resultado = $conexion->query($sql);
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

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2>Historial de ventas</h2>

        <a href="venta.php"
           class="btn btn-success">

            Nueva venta

        </a>

    </div>


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
                                    #<?php echo $venta["id"]; ?>
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
                                    $<?php
                                    echo number_format(
                                        $venta["total"],
                                        0,
                                        ",",
                                        "."
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $venta["metodo_pago"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    $<?php
                                    echo number_format(
                                        $venta["monto_pagado"],
                                        0,
                                        ",",
                                        "."
                                    );
                                    ?>
                                </td>

                                <td>

                                    <span class="badge bg-success">

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

                            <td colspan="6"
                                class="text-center text-muted">

                                Todavía no hay ventas registradas.

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