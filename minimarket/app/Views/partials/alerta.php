<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash["tipo"]) ?>">
        <?= e($flash["mensaje"]) ?>
    </div>
<?php endif; ?>
