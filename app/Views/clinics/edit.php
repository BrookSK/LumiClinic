<?php
$title = 'Clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$clinic = $clinic ?? null;
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Dados da clínica</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/clinic">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($clinic['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <div style="margin-top:14px; display:flex; gap:10px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/">Voltar</a>
            <a class="lc-btn lc-btn--secondary" href="/clinic/working-hours">Horários</a>
            <a class="lc-btn lc-btn--secondary" href="/clinic/closed-days">Feriados e Recesso</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
