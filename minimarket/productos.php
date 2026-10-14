<?php
require_once "auth.php";
requireRole(["admin"]);
require_once "conexion.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $codigo = trim($_POST["codigo"]);
    $nombre = trim($_POST["nombre"]);
    $precio = $_POST["precio"];
    $cantidad = $_POST["cantidad"];

    $sql = "INSERT INTO productos (codigo, nombre, precio, cantidad)
            VALUES (?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssdi", $codigo, $nombre, $precio, $cantidad);

    if ($stmt->execute()) {
        $mensaje = "Producto registrado correctamente.";
    } else {
        $mensaje = "No se pudo registrar el producto. Verifica que el código no esté repetido.";
    }

    $stmt->close();
}

$resultado = $conexion->query("SELECT * FROM productos ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Productos - Minimarket</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<?php require "navbar.php"; ?>

<div class="container py-5">

    <h2 class="mb-4">Gestión de productos</h2>

    <?php if ($mensaje != ""): ?>

        <div class="alert alert-info">
            <?php echo $mensaje; ?>
        </div>

    <?php endif; ?>

    <div class="card shadow-sm mb-5">

        <div class="card-header">
            Registrar nuevo producto
        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label">Código</label>

                        <input
                            type="text"
                            name="codigo"
                            class="form-control"
                            required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Nombre</label>

                        <input
                            type="text"
                            name="nombre"
                            class="form-control"
                            required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Precio</label>

                        <input
                            type="number"
                            name="precio"
                            class="form-control"
                            min="0"
                            step="0.01"
                            required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Cantidad inicial</label>

                        <input
                            type="number"
                            name="cantidad"
                            class="form-control"
                            min="0"
                            required>
                    </div>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary mt-4">

                    Registrar producto

                </button>

            </form>

        </div>
    </div>


    <div class="card shadow-sm">

        <div class="card-header">
            Productos registrados
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover">

                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($producto = $resultado->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($producto["codigo"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($producto["nombre"]); ?>
                            </td>

                            <td>
                                $<?php echo number_format($producto["precio"], 0, ",", "."); ?>
                            </td>

                            <td>
                                <?php echo $producto["cantidad"]; ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

</body>
</html>