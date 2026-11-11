<?php /** @var App\Models\Producto $producto */ ?>
<?php if ($producto->estaDisponible()): ?>
    <span class="badge bg-success">Disponible</span>
<?php else: ?>
    <span class="badge bg-danger">Agotado</span>
<?php endif; ?>
