<?php
/** @var App\Models\Producto[] $productos */
/** @var string $buscar */
/** @var App\Models\Usuario $usuarioActual */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Inventario</h2>
    <?php if ($usuarioActual->esAdministrador()): ?>
        <a href="<?= url("productos") ?>" class="btn btn-primary">Gestionar productos</a>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="ruta" value="inventario">
            <div class="input-group">
                <input type="text" name="buscar" class="form-control"
                       placeholder="Buscar por código o nombre" value="<?= e($buscar) ?>">
                <button type="submit" class="btn btn-dark">Buscar</button>
                <a href="<?= url("inventario") ?>" class="btn btn-outline-secondary">Mostrar todos</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Existencias disponibles</div>
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
                <?php if (count($productos) > 0): ?>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?= e($producto->codigo) ?></td>
                            <td><?= e($producto->nombre) ?></td>
                            <td>$<?= dinero($producto->precio) ?></td>
                            <td><?= $producto->cantidad ?></td>
                            <td><?php require BASE_PATH . "/app/Views/partials/estado_producto.php"; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No se encontraron productos.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
