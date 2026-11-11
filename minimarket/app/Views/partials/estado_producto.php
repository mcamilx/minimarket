<?php /** @var App\Models\Producto $producto */ ?>
<?php if ($producto->estaDescontinuado()): ?>
    <span class="badge bg-secondary">Descontinuado</span>
<?php elseif ($producto->estaDisponible()): ?>
    <span class="badge bg-success">Disponible</span>
<?php else: ?>
    <span class="badge bg-danger">Agotado</span>
<?php endif; ?>
