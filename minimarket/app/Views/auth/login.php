<?php /** @var array $perfiles */ ?>
<div class="text-center mb-5">
    <h1>Bienvenido al Minimarket</h1>
    <p class="text-muted">Selecciona el perfil con el que deseas ingresar</p>
</div>

<div class="row g-4 justify-content-center">
    <?php foreach ($perfiles as $rol => $nombre): ?>
        <div class="col-md-5">
            <div class="card shadow-sm h-100 text-center">
                <div class="card-body p-5">
                    <h2 class="h4 mb-3">Entrar como <?= e($nombre) ?></h2>
                    <p class="text-muted">
                        <?= $rol === "admin"
                            ? "Gestiona productos, inventario y ventas."
                            : "Registra ventas y consulta el inventario y el historial." ?>
                    </p>
                    <form method="POST" action="<?= url("login") ?>">
                        <input type="hidden" name="rol" value="<?= e($rol) ?>">
                        <button type="submit"
                                class="btn <?= $rol === "admin" ? "btn-primary" : "btn-success" ?> btn-lg">
                            Ingresar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
