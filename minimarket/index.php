<?php
require_once "auth.php";

$personas = [
    "admin" => "Administrador",
    "cajero" => "Cajero"
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cerrar_sesion"])) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $parametros = session_get_cookie_params();
        setcookie(
            session_name(),
            "",
            time() - 42000,
            $parametros["path"],
            $parametros["domain"],
            $parametros["secure"],
            $parametros["httponly"]
        );
    }

    session_destroy();
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["rol"])) {
    $rol = $_POST["rol"];

    if (is_string($rol) && isset($personas[$rol])) {
        session_regenerate_id(true);
        $_SESSION["usuario"] = [
            "nombre" => $personas[$rol],
            "rol" => $rol
        ];
    }
}

$usuario = currentUser();
$rolUsuario = is_array($usuario) ? ($usuario["rol"] ?? null) : null;

if ($rolUsuario !== null) {
    require_once "conexion.php";

    $resultado = $conexion->query(
        "SELECT COUNT(*) AS total_productos,
                COALESCE(SUM(cantidad), 0) AS total_unidades
         FROM productos"
    );
    $estadisticas = $resultado->fetch_assoc();

    $resultadoVentas = $conexion->query(
        "SELECT COUNT(*) AS total_ventas FROM ventas"
    );
    $totalVentas = $resultadoVentas->fetch_assoc()["total_ventas"];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Minimarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<?php if (!$usuario): ?>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand mb-0 h1">Sistema Minimarket</span>
        </div>
    </nav>

    <main class="container py-5">
        <div class="text-center mb-5">
            <h1>Bienvenido al Minimarket</h1>
            <p class="text-muted">Selecciona el perfil con el que deseas ingresar</p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($personas as $rol => $nombre): ?>
                <div class="col-md-5">
                    <div class="card shadow-sm h-100 text-center">
                        <div class="card-body p-5">
                            <h2 class="h4 mb-3">Entrar como <?php echo htmlspecialchars($nombre); ?></h2>
                            <p class="text-muted">
                                <?php if ($rol === "admin"): ?>
                                    Gestiona productos, inventario y ventas.
                                <?php else: ?>
                                    Registra ventas y consulta el inventario y el historial.
                                <?php endif; ?>
                            </p>
                            <form method="POST">
                                <input type="hidden" name="rol" value="<?php echo htmlspecialchars($rol); ?>">
                                <button type="submit"
                                        class="btn <?php echo $rol === "admin" ? "btn-primary" : "btn-success"; ?> btn-lg">
                                    Ingresar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
<?php else: ?>
    <?php require "navbar.php"; ?>

    <main class="container py-5">
        <div class="text-center mb-5">
            <h1>Sistema de Ventas e Inventario</h1>
            <p class="text-muted">
                Panel de <?php echo $rolUsuario === "admin" ? "administración" : "cajero"; ?>
            </p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h5>Productos registrados</h5>
                        <h2><?php echo $estadisticas["total_productos"]; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h5>Stock total</h5>
                        <h2><?php echo $estadisticas["total_unidades"]; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <h5>Ventas realizadas</h5>
                        <h2><?php echo $totalVentas; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php if ($rolUsuario === "cajero"): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h4>Nueva venta</h4>
                            <p>Registrar una compra y realizar el proceso de pago.</p>
                            <a href="venta.php" class="btn btn-success">Iniciar venta</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($rolUsuario === "admin"): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h4>Productos</h4>
                            <p>Registrar y consultar los productos del sistema.</p>
                            <a href="productos.php" class="btn btn-primary">Gestionar productos</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h4>Inventario</h4>
                        <p>Consultar el stock y las cantidades disponibles.</p>
                        <a href="inventario.php" class="btn btn-warning">Ver inventario</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h4>Historial de ventas</h4>
                        <p>Consultar las ventas realizadas anteriormente.</p>
                        <a href="historial.php" class="btn btn-dark">Ver historial</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php endif; ?>

</body>
</html>
