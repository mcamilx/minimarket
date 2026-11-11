<?php
/** @var array $resumen */
/** @var App\Models\Usuario $usuarioActual */
?>
<div class="text-center mb-5">
    <h1>Sistema de Ventas e Inventario</h1>
    <p class="text-muted">
        Panel de <?= $usuarioActual->esAdministrador() ? "administración" : "cajero" ?>
    </p>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card shadow-sm text-center h-100">
            <div class="card-body">
                <h5>Productos registrados</h5>
                <h2><?= $resumen["total_productos"] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center h-100">
            <div class="card-body">
                <h5>Stock total</h5>
                <h2><?= $resumen["total_unidades"] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center h-100">
            <div class="card-body">
                <h5>Ventas realizadas</h5>
                <h2><?= $resumen["total_ventas"] ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php if ($usuarioActual->esCajero()): ?>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h4>Nueva venta</h4>
                    <p>Registrar una compra y realizar el proceso de pago.</p>
                    <a href="<?= url("ventas") ?>" class="btn btn-success">Iniciar venta</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($usuarioActual->esAdministrador()): ?>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h4>Productos</h4>
                    <p>Registrar y consultar los productos del sistema.</p>
                    <a href="<?= url("productos") ?>" class="btn btn-primary">Gestionar productos</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h4>Inventario</h4>
                <p>Consultar el stock y las cantidades disponibles.</p>
                <a href="<?= url("inventario") ?>" class="btn btn-warning">Ver inventario</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h4>Historial de ventas</h4>
                <p>Consultar las ventas realizadas anteriormente.</p>
                <a href="<?= url("historial") ?>" class="btn btn-dark">Ver historial</a>
            </div>
        </div>
    </div>
</div>
