<?php
$title = 'Novo termo';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Novo termo</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/consent-terms/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Procedimento</label>
        <input class="lc-input" type="text" name="procedure_type" required />

        <label class="lc-label">Título</label>
        <input class="lc-input" type="text" name="title" required />

        <label class="lc-label">Corpo do termo</label>
        <textarea class="lc-input" name="body" rows="12" required></textarea>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
            <a class="lc-btn lc-btn--secondary" href="/consent-terms">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
