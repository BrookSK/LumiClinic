<?php
$title = 'Novo usuário';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$roles = $roles ?? [];
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Cadastro</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/users/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" required />

        <label class="lc-label">E-mail</label>
        <input class="lc-input" type="email" name="email" required />

        <label class="lc-label">Senha</label>
        <input class="lc-input" type="password" name="password" required />

        <label class="lc-label">Papel</label>
        <select class="lc-input" name="role_id" required>
            <option value="">Selecione</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <div style="margin-top:14px; display:flex; gap:10px;">
            <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
            <a class="lc-btn lc-btn--secondary" href="/users">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
