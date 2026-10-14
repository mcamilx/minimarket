<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Sistema Minimarket</a>

        <div class="d-flex align-items-center gap-3">
            <span class="text-white">
                <?php echo htmlspecialchars(currentUser()["nombre"]); ?>
                (<?php echo currentRole() === "admin" ? "Administrador" : "Cajero"; ?>)
            </span>

            <?php if (basename($_SERVER["PHP_SELF"]) !== "index.php"): ?>
                <a href="index.php" class="btn btn-outline-light">
                    Volver al panel
                </a>
            <?php endif; ?>

            <form method="POST" action="index.php" class="m-0">
                <button type="submit" name="cerrar_sesion" class="btn btn-outline-light">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</nav>
