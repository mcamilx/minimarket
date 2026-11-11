<?php
/** @var string $titulo */
/** @var string $mensaje */
/** @var string $enlace */
/** @var string $textoEnlace */
?>
<div class="alert alert-danger">
    <h2 class="h5"><?= e($titulo) ?></h2>
    <p class="mb-0"><?= e($mensaje) ?></p>
</div>
<a href="<?= e($enlace) ?>" class="btn btn-primary"><?= e($textoEnlace) ?></a>
