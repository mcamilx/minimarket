<?php
/** @var App\Models\Factura $factura */
/** @var App\Models\Usuario $usuarioActual */
$cliente = $factura->cliente;
?>
<div class="card shadow-sm mx-auto" style="max-width: 750px;">
    <div class="card-header text-center">
        <h3 class="mb-0">Factura de venta</h3>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col"><strong>Venta:</strong> #<?= $factura->ventaId ?></div>
            <div class="col text-end"><strong>Fecha:</strong> <?= e($factura->fecha) ?></div>
        </div>

        <?php if ($cliente->nombre !== ""): ?>
            <div class="card card-body mb-4">
                <h5>Datos del cliente</h5>
                <p class="mb-1"><strong>Nombre:</strong> <?= e($cliente->nombre) ?></p>
                <p class="mb-1"><strong>Documento:</strong> <?= e($cliente->tipoDocumento . " " . $cliente->documento) ?></p>
                <p class="mb-0"><strong>Teléfono:</strong> <?= e($cliente->telefono) ?></p>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($factura->items as $item): ?>
                    <tr>
                        <td><?= e($item->nombre) ?></td>
                        <td><?= $item->cantidad ?></td>
                        <td>$<?= dinero($item->precio) ?></td>
                        <td>$<?= dinero($item->subtotal()) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <hr>

        <div class="text-end">
            <p><strong>Método de pago:</strong> <?= e($factura->metodoPago) ?></p>
            <p><strong>Total:</strong> $<?= dinero($factura->total) ?></p>
            <p><strong>Valor pagado:</strong> $<?= dinero($factura->montoPagado) ?></p>
            <?php if ($factura->metodoPago === "Efectivo"): ?>
                <p><strong>Cambio:</strong> $<?= dinero($factura->cambio) ?></p>
            <?php endif; ?>
        </div>

        <div class="alert alert-success mt-4">
            Venta registrada y productos entregados correctamente.
        </div>

        <div class="d-flex gap-2">
            <?php if ($usuarioActual->esCajero()): ?>
                <a href="<?= url("ventas") ?>" class="btn btn-success">Nueva venta</a>
            <?php endif; ?>
            <a href="<?= url("inicio") ?>" class="btn btn-dark">Inicio</a>
        </div>
    </div>
</div>
