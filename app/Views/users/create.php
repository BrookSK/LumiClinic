<?php
$title = 'Novo usuário';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$roles = $roles ?? [];

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Cadastro</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($can('users.create')): ?>
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

            <div class="lc-flex lc-gap-sm" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
                <a class="lc-btn lc-btn--secondary" href="/users">Voltar</a>
            </div>
        </form>
    <?php else: ?>
        <div class="lc-flex lc-gap-sm" style="margin-top:14px;">
            <a class="lc-btn lc-btn--secondary" href="/users">Voltar</a>
        </div>
    <?php endif; ?>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
