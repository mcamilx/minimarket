<?php /** @var App\Models\Producto $producto */ ?>
<h2 class="mb-4">Editar producto</h2>

<div class="card shadow-sm" style="max-width: 750px;">
    <div class="card-header">
        <?= e($producto->codigo . " - " . $producto->nombre) ?>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url("productos/actualizar") ?>">
            <input type="hidden" name="id" value="<?= $producto->id ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="codigo">Código</label>
                    <input type="text" id="codigo" name="codigo" class="form-control"
                           maxlength="30" value="<?= e($producto->codigo) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="form-control"
                           maxlength="100" value="<?= e($producto->nombre) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="precio">Precio</label>
                    <input type="number" id="precio" name="precio" class="form-control"
                           min="0" step="0.01" value="<?= e($producto->precio) ?>" required>
                </div>
            </div>
            <p class="text-muted mt-3 mb-0">
                Stock actual: <strong><?= $producto->cantidad ?></strong> unidades.
                El stock cambia con las ventas y con la opción "Reponer producto agotado".
                Cambiar el precio no modifica las ventas ya registradas.
            </p>
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="<?= url("productos") ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
