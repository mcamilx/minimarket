<?php /** @var string[] $errores */ ?>
<div class="alert alert-danger">
    <h2 class="h5">Revisa los datos del cliente</h2>
    <ul class="mb-0">
        <?php foreach ($errores as $error): ?>
            <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<p>La venta sigue en curso y no se ha descontado el inventario.</p>
<a href="<?= url("ventas") ?>" class="btn btn-primary">Volver a la venta</a>
