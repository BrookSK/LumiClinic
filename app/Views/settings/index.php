<?php
$title = 'Configurações';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$settings = $settings ?? null;
ob_start();
?>
<div class="lc-grid" style="grid-template-columns: 1fr;">
    <div class="lc-card">
        <div class="lc-card__title">Geral</div>

        <?php if ($error): ?>
            <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" class="lc-form" action="/settings">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Timezone</label>
            <input class="lc-input" type="text" name="timezone" value="<?= htmlspecialchars((string)($settings['timezone'] ?? 'America/Sao_Paulo'), ENT_QUOTES, 'UTF-8') ?>" required />

            <label class="lc-label">Idioma</label>
            <input class="lc-input" type="text" name="language" value="<?= htmlspecialchars((string)($settings['language'] ?? 'pt-BR'), ENT_QUOTES, 'UTF-8') ?>" required />

            <div style="margin-top:14px; display:flex; gap:10px; align-items:center;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/settings/terminology">Editar terminologia</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
