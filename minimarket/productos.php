<?php
require_once "auth.php";
requireRole(["admin"]);
require_once "conexion.php";
require_once "patrones_diseno.php";

$mensaje = "";
$tipoMensaje = "info";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["registrar_producto"])) {

    $codigo = trim(
        is_string($_POST["codigo"] ?? null) ? $_POST["codigo"] : ""
    );
    $nombre = trim(
        is_string($_POST["nombre"] ?? null) ? $_POST["nombre"] : ""
    );
    $precio = filter_var($_POST["precio"] ?? null, FILTER_VALIDATE_FLOAT);
    $cantidad = filter_var($_POST["cantidad"] ?? null, FILTER_VALIDATE_INT);

    if (
        $codigo === "" ||
        $nombre === "" ||
        $precio === false ||
        $precio < 0 ||
        $cantidad === false ||
        $cantidad < 0
    ) {
        $mensaje = "Completa los datos con un código, nombre, precio y cantidad válidos.";
        $tipoMensaje = "danger";
    } else {
        $sql = "INSERT INTO productos (codigo, nombre, precio, cantidad)
                VALUES (?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssdi", $codigo, $nombre, $precio, $cantidad);

        try {
            $stmt->execute();
            $mensaje = "Producto registrado correctamente.";
            $tipoMensaje = "success";
        } catch (mysqli_sql_exception $error) {
            if ($error->getCode() === 1062) {
                $mensaje = "No se pudo registrar el producto: ese código ya está registrado.";
                $tipoMensaje = "danger";
            } else {
                throw $error;
            }
        }

        $stmt->close();
    }
} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reponer_stock"])) {
    $codigoReposicion = trim(
        is_string($_POST["codigo_reposicion"] ?? null)
            ? $_POST["codigo_reposicion"]
            : ""
    );
    $cantidadReposicion = filter_var(
        $_POST["cantidad_reposicion"] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($codigoReposicion === "" || $cantidadReposicion === false || $cantidadReposicion <= 0) {
        $mensaje = "Ingresa el código del producto y una cantidad mayor que cero.";
        $tipoMensaje = "danger";
    } else {
        $resultadoReposicion = ProductoRepository::reponerSiAgotado(
            $conexion,
            $codigoReposicion,
            $cantidadReposicion
        );

        if ($resultadoReposicion === "repuesto") {
            $mensaje = "Stock repuesto correctamente. El producto ya está disponible para venderse.";
            $tipoMensaje = "success";
        } elseif ($resultadoReposicion === "no_encontrado") {
            $mensaje = "No existe un producto con ese código.";
            $tipoMensaje = "danger";
        } else {
            $mensaje = "El producto existe, pero no está agotado. La reposición solo se permite cuando su stock es cero.";
            $tipoMensaje = "warning";
        }
    }

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

        <div class="alert alert-<?php echo htmlspecialchars($tipoMensaje); ?>">
            <?php echo htmlspecialchars($mensaje); ?>
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
                    name="registrar_producto"
                    class="btn btn-primary mt-4">

                    Registrar producto

                </button>

            </form>

        </div>
    </div>

    <div class="card shadow-sm mb-5">

        <div class="card-header">
            Reponer producto agotado
        </div>

        <div class="card-body">
            <p class="text-muted">
                Ingresa el código exacto del producto agotado y la cantidad de unidades que vas a agregar.
            </p>

            <form method="POST">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="codigo_reposicion" class="form-label">
                            Código del producto
                        </label>
                        <input
                            type="text"
                            id="codigo_reposicion"
                            name="codigo_reposicion"
                            class="form-control"
                            maxlength="30"
                            required>
                    </div>

                    <div class="col-md-3">
                        <label for="cantidad_reposicion" class="form-label">
                            Cantidad a agregar
                        </label>
                        <input
                            type="number"
                            id="cantidad_reposicion"
                            name="cantidad_reposicion"
                            class="form-control"
                            min="1"
                            required>
                    </div>

                    <div class="col-md-3">
                        <button
                            type="submit"
                            name="reponer_stock"
                            class="btn btn-warning w-100">
                            Reponer stock
                        </button>
                    </div>
                </div>
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
                            <th>Estado</th>
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

                            <td>
                                <?php if ((int) $producto["cantidad"] > 0): ?>
                                    <span class="badge bg-success">Disponible</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Agotado</span>
                                <?php endif; ?>
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