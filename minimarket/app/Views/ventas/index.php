<?php
/** @var App\Models\ItemCarrito[] $items */
/** @var float $total */
/** @var App\Models\Producto[] $productos */
/** @var App\Models\VentaPendiente[] $pendientes */
/** @var int $ventaPendienteId */
/** @var App\Models\Cliente $cliente */
$hayItems = count($items) > 0;
?>
<h2 class="mb-4">Nueva venta</h2>

<?php if ($ventaPendienteId > 0): ?>
    <div class="alert alert-warning">
        Estás retomando la venta pendiente #<?= $ventaPendienteId ?>.
        Al guardarla en espera nuevamente conservará el mismo número.
    </div>
<?php endif; ?>

<?php if (count($pendientes) > 0): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">Ventas en espera</div>
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                <tr>
                    <th>Venta pendiente</th>
                    <th>Guardada</th>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pendientes as $pendiente): ?>
                    <tr>
                        <td>#<?= $pendiente->id ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($pendiente->fechaHora)) ?></td>
                        <td><?= $pendiente->cantidadProductos ?></td>
                        <td>$<?= dinero($pendiente->total) ?></td>
                        <td>
                            <form method="POST" action="<?= url("ventas/retomar-pendiente") ?>" class="m-0">
                                <input type="hidden" name="venta_pendiente_id" value="<?= $pendiente->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary"
                                    <?= $hayItems ? "disabled" : "" ?>>
                                    Retomar venta
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header">Agregar producto</div>
    <div class="card-body">
        <form method="POST" action="<?= url("ventas/agregar") ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="producto_id">Producto</label>
                    <select id="producto_id" name="producto_id" class="form-select" required>
                        <option value="">Seleccione un producto</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?= $producto->id ?>">
                                <?= e($producto->codigo . " - " . $producto->nombre) ?>
                                - $<?= dinero($producto->precio) ?>
                                (Stock: <?= $producto->cantidad ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="cantidad">Cantidad</label>
                    <input type="number" id="cantidad" name="cantidad" class="form-control" min="1" value="1" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Agregar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Resumen de compra</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($hayItems): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item->codigo) ?></td>
                            <td><?= e($item->nombre) ?></td>
                            <td>$<?= dinero($item->precio) ?></td>
                            <td><?= $item->cantidad ?></td>
                            <td>$<?= dinero($item->subtotal()) ?></td>
                            <td>
                                <form method="POST" action="<?= url("ventas/eliminar") ?>" class="m-0">
                                    <input type="hidden" name="producto_id" value="<?= $item->productoId ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No hay productos agregados.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-end">
            <h3>Total: $<?= dinero($total) ?></h3>
        </div>

        <?php if ($hayItems): ?>
            <form method="POST" action="<?= url("ventas/guardar-pendiente") ?>" class="mb-4">
                <button type="submit" class="btn btn-outline-warning">Guardar venta en espera</button>
            </form>

            <hr>
            <h5 class="mb-3">Información de pago</h5>

            <form method="POST" action="<?= url("ventas/finalizar") ?>" id="formulario_pago">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="metodo_pago">Método de pago</label>
                        <select name="metodo_pago" id="metodo_pago" class="form-select" required>
                            <option value="">Seleccione</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta">Tarjeta</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="monto_pagado">Valor pagado</label>
                        <input type="number" name="monto_pagado" id="monto_pagado"
                               class="form-control" min="0" step="0.01" required>
                    </div>

                    <div class="col-12">
                        <hr>
                        <h5>Datos del cliente (opcionales)</h5>
                        <p class="text-muted mb-3">
                            Completa todos los campos si el cliente solicita factura a su nombre.
                        </p>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="cliente_tipo_documento">Tipo de documento</label>
                        <select id="cliente_tipo_documento" name="cliente_tipo_documento" class="form-select">
                            <option value="">Seleccione</option>
                            <?php foreach (App\Models\Cliente::TIPOS_DOCUMENTO as $codigoTipo => $nombreTipo): ?>
                                <option value="<?= e($codigoTipo) ?>"
                                    <?= $cliente->tipoDocumento === $codigoTipo ? "selected" : "" ?>>
                                    <?= e($nombreTipo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="cliente_documento">Número de documento</label>
                        <input type="text" id="cliente_documento" name="cliente_documento" class="form-control"
                               maxlength="30" pattern="[A-Za-z0-9.-]{5,30}" value="<?= e($cliente->documento) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="cliente_nombre">Nombre del cliente</label>
                        <input type="text" id="cliente_nombre" name="cliente_nombre" class="form-control"
                               maxlength="100" value="<?= e($cliente->nombre) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="cliente_telefono">Teléfono de contacto</label>
                        <input type="tel" id="cliente_telefono" name="cliente_telefono" class="form-control"
                               maxlength="30" pattern="[0-9+() -]{7,20}" value="<?= e($cliente->telefono) ?>">
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">Finalizar venta</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
