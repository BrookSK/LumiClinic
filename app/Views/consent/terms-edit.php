<?php
$title = 'Editar termo';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$term = $term ?? null;
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Editar termo</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/consent-terms/edit">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($term['id'] ?? 0) ?>" />

        <label class="lc-label">Procedimento</label>
        <input class="lc-input" type="text" name="procedure_type" value="<?= htmlspecialchars((string)($term['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Título</label>
        <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($term['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Status</label>
        <?php $status = (string)($term['status'] ?? 'active'); ?>
        <select class="lc-select" name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
            <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Desativado</option>
        </select>

        <label class="lc-label">Corpo do termo</label>
        <textarea class="lc-input" name="body" rows="12" required><?= htmlspecialchars((string)($term['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/consent-terms">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
