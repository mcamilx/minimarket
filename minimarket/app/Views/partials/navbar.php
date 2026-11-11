<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <?php if ($usuarioActual === null): ?>
            <span class="navbar-brand mb-0 h1">Sistema Minimarket</span>
        <?php else: ?>
            <a class="navbar-brand" href="<?= url("inicio") ?>">Sistema Minimarket</a>

            <div class="d-flex align-items-center gap-3">
                <span class="text-white">
                    <?= e($usuarioActual->nombre) ?>
                    (<?= $usuarioActual->esAdministrador() ? "Administrador" : "Cajero" ?>)
                </span>

                <?php if ($rutaActual !== "inicio"): ?>
                    <a href="<?= url("inicio") ?>" class="btn btn-outline-light">Volver al panel</a>
                <?php endif; ?>

                <form method="POST" action="<?= url("logout") ?>" class="m-0">
                    <button type="submit" class="btn btn-outline-light">Cerrar sesión</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</nav>
