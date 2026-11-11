<?php
/** @var App\Models\Venta[] $ventas */
/** @var string $buscar */
/** @var App\Models\Usuario $usuarioActual */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Historial de ventas</h2>
    <?php if (!$usuarioActual->esAdministrador()): ?>
        <a href="<?= url("ventas") ?>" class="btn btn-success">Nueva venta</a>
    <?php endif; ?>
</div>

<?php if ($usuarioActual->esAdministrador()): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET">
                <input type="hidden" name="ruta" value="historial">
                <label for="buscar" class="form-label">
                    Buscar por número de factura, nombre, documento o teléfono
                </label>
                <div class="input-group">
                    <input type="search" id="buscar" name="buscar" class="form-control"
                           placeholder="Número de factura o datos del cliente" value="<?= e($buscar) ?>">
                    <button type="submit" class="btn btn-dark">Buscar</button>
                    <a href="<?= url("historial") ?>" class="btn btn-outline-secondary">Mostrar todas</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header">Ventas realizadas</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                <tr>
                    <th>Venta</th>
                    <th>Fecha y hora</th>
                    <th>Cliente</th>
                    <th>Documento</th>
                    <th>Total</th>
                    <th>Método de pago</th>
                    <th>Valor pagado</th>
                    <th>Estado</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($ventas) > 0): ?>
                    <?php foreach ($ventas as $venta): ?>
                        <tr>
                            <td>#<?= e($venta->numero()) ?></td>
                            <td><?= date("d/m/Y H:i", strtotime($venta->fechaHora)) ?></td>
                            <td>
                                <?= e($venta->cliente->nombre !== ""
                                    ? $venta->cliente->nombre
                                    : ($venta->esPendiente ? "—" : "Consumidor final")) ?>
                            </td>
                            <td>
                                <?= $venta->cliente->documento !== ""
                                    ? e($venta->cliente->tipoDocumento . " " . $venta->cliente->documento)
                                    : "—" ?>
                            </td>
                            <td>$<?= dinero($venta->total) ?></td>
                            <td><?= $venta->metodoPago !== "" ? e($venta->metodoPago) : "—" ?></td>
                            <td><?= $venta->esPendiente ? "—" : "$" . dinero($venta->montoPagado) ?></td>
                            <td>
                                <span class="badge <?= $venta->esPendiente ? "bg-warning text-dark" : "bg-success" ?>">
                                    <?= e($venta->estado) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            <?= $buscar !== "" ? "No se encontraron ventas con esos datos." : "Todavía no hay ventas registradas." ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="<?= url("inicio") ?>" class="btn btn-secondary">Volver al inicio</a>
</div>
