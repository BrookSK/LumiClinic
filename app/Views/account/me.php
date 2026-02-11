<?php
$title = 'Meu perfil';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$user = $user ?? null;

ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Meu perfil</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/me">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">E-mail</label>
        <input class="lc-input" type="email" name="email" value="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
