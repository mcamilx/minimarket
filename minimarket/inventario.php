<?php
require_once "conexion.php";

$buscar = "";

if (isset($_GET["buscar"])) {
    $buscar = trim($_GET["buscar"]);
}

if ($buscar != "") {

    $sql = "SELECT * FROM productos
            WHERE codigo LIKE ? OR nombre LIKE ?
            ORDER BY nombre ASC";

    $stmt = $conexion->prepare($sql);

    $busqueda = "%" . $buscar . "%";

    $stmt->bind_param("ss", $busqueda, $busqueda);
    $stmt->execute();

    $resultado = $stmt->get_result();

} else {

    $resultado = $conexion->query(
        "SELECT * FROM productos ORDER BY nombre ASC"
    );
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Inventario - Minimarket</title>

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

        <h2>Inventario</h2>

        <a href="productos.php"
           class="btn btn-primary">

            Registrar producto

        </a>

    </div>


    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="input-group">

                    <input
                        type="text"
                        name="buscar"
                        class="form-control"
                        placeholder="Buscar por código o nombre"
                        value="<?php echo htmlspecialchars($buscar); ?>">

                    <button
                        type="submit"
                        class="btn btn-dark">

                        Buscar

                    </button>

                    <a
                        href="inventario.php"
                        class="btn btn-outline-secondary">

                        Mostrar todos

                    </a>

                </div>

            </form>

        </div>

    </div>


    <div class="card shadow-sm">

        <div class="card-header">
            Existencias disponibles
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead>

                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Disponible</th>
                            <th>Estado</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($resultado->num_rows > 0): ?>

                        <?php while ($producto = $resultado->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($producto["codigo"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($producto["nombre"]); ?>
                                </td>

                                <td>
                                    $<?php echo number_format(
                                        $producto["precio"],
                                        0,
                                        ",",
                                        "."
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo $producto["cantidad"]; ?>
                                </td>

                                <td>

                                    <?php if ($producto["cantidad"] > 0): ?>

                                        <span class="badge bg-success">
                                            Disponible
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">
                                            Agotado
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="5"
                                class="text-center text-muted">

                                No se encontraron productos.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>