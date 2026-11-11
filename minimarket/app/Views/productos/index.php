<?php /** @var App\Models\Producto[] $productos */ ?>
<h2 class="mb-4">Gestión de productos</h2>

<div class="card shadow-sm mb-5">
    <div class="card-header">Registrar nuevo producto</div>
    <div class="card-body">
        <form method="POST" action="<?= url("productos/registrar") ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="codigo">Código</label>
                    <input type="text" id="codigo" name="codigo" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="precio">Precio</label>
                    <input type="number" id="precio" name="precio" class="form-control" min="0" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="cantidad">Cantidad inicial</label>
                    <input type="number" id="cantidad" name="cantidad" class="form-control" min="0" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4">Registrar producto</button>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-5">
    <div class="card-header">Reponer producto agotado</div>
    <div class="card-body">
        <p class="text-muted">
            Ingresa el código exacto del producto agotado y la cantidad de unidades que vas a agregar.
        </p>
        <form method="POST" action="<?= url("productos/reponer") ?>">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="codigo_reposicion" class="form-label">Código del producto</label>
                    <input type="text" id="codigo_reposicion" name="codigo_reposicion"
                           class="form-control" maxlength="30" required>
                </div>
                <div class="col-md-3">
                    <label for="cantidad_reposicion" class="form-label">Cantidad a agregar</label>
                    <input type="number" id="cantidad_reposicion" name="cantidad_reposicion"
                           class="form-control" min="1" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-warning w-100">Reponer stock</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Productos registrados</div>
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
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?= e($producto->codigo) ?></td>
                        <td><?= e($producto->nombre) ?></td>
                        <td>$<?= dinero($producto->precio) ?></td>
                        <td><?= $producto->cantidad ?></td>
                        <td><?php require BASE_PATH . "/app/Views/partials/estado_producto.php"; ?></td>
                        <td class="text-nowrap">
                            <a href="<?= url("productos/editar", ["id" => $producto->id]) ?>"
                               class="btn btn-sm btn-outline-primary">Editar</a>
                            <form method="POST" action="<?= url("productos/eliminar") ?>" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar el producto <?= e(addslashes($producto->nombre)) ?>?');">
                                <input type="hidden" name="id" value="<?= $producto->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
