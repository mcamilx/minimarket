<?php
require_once "conexion.php";

$totalProductos = 0;
$totalUnidades = 0;
$totalVentas = 0;

$resultado = $conexion->query(
    "SELECT COUNT(*) AS total_productos,
            COALESCE(SUM(cantidad), 0) AS total_unidades
     FROM productos"
);

if ($resultado) {
    $datos = $resultado->fetch_assoc();
    $totalProductos = $datos["total_productos"];
    $totalUnidades = $datos["total_unidades"];
}

$resultadoVentas = $conexion->query(
    "SELECT COUNT(*) AS total_ventas FROM ventas"
);

if ($resultadoVentas) {
    $datosVentas = $resultadoVentas->fetch_assoc();
    $totalVentas = $datosVentas["total_ventas"];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Sistema Minimarket</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">

        <span class="navbar-brand mb-0 h1">
            Sistema Minimarket
        </span>

    </div>
</nav>


<div class="container py-5">

    <div class="text-center mb-5">

        <h1>Sistema de Ventas e Inventario</h1>

        <p class="text-muted">
            Administración de productos, inventario y ventas
        </p>

    </div>


    <div class="row g-4 mb-5">

        <div class="col-md-4">

            <div class="card shadow-sm text-center h-100">

                <div class="card-body">

                    <h5>Productos registrados</h5>

                    <h2>
                        <?php echo $totalProductos; ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card shadow-sm text-center h-100">

                <div class="card-body">

                    <h5>Unidades disponibles</h5>

                    <h2>
                        <?php echo $totalUnidades; ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card shadow-sm text-center h-100">

                <div class="card-body">

                    <h5>Ventas realizadas</h5>

                    <h2>
                        <?php echo $totalVentas; ?>
                    </h2>

                </div>

            </div>

        </div>

    </div>


    <div class="row g-4">

        <div class="col-md-6">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h4>Nueva venta</h4>

                    <p>
                        Registrar una nueva compra y realizar
                        el proceso de pago.
                    </p>

                    <a href="venta.php"
                       class="btn btn-success">

                        Iniciar venta

                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-6">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h4>Productos</h4>

                    <p>
                        Registrar y consultar los productos
                        disponibles en el sistema.
                    </p>

                    <a href="productos.php"
                       class="btn btn-primary">

                        Gestionar productos

                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-6">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h4>Inventario</h4>

                    <p>
                        Consultar las cantidades disponibles
                        de cada producto.
                    </p>

                    <a href="inventario.php"
                       class="btn btn-warning">

                        Ver inventario

                    </a>

                </div>

            </div>

        </div>


        <div class="col-md-6">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h4>Historial de ventas</h4>

                    <p>
                        Consultar las ventas realizadas
                        anteriormente.
                    </p>

                    <a href="historial.php"
                       class="btn btn-dark">

                        Ver historial

                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>