<?php
/*
 * Plantilla general: todas las páginas comparten esta estructura.
 * Variables: $contenido (HTML de la vista), $usuarioActual, $rutaActual,
 * $flash (mensaje de una sola vez), $scripts (archivos JS opcionales).
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? "Sistema Minimarket") ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php require BASE_PATH . "/app/Views/partials/navbar.php"; ?>

<main class="container py-5">
    <?php require BASE_PATH . "/app/Views/partials/alerta.php"; ?>
    <?= $contenido ?>
</main>

<?php foreach ($scripts ?? [] as $script): ?>
    <script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
