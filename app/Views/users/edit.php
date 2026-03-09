<?php
$title = 'Editar usuário';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$user = $user ?? null;
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

$ro = $can('users.update') ? '' : 'disabled';

ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Edição</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($can('users.update')): ?>
        <form method="post" class="lc-form" action="/users/edit">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>" />

            <label class="lc-label">Nome</label>
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

            <label class="lc-label">E-mail</label>
            <input class="lc-input" type="email" name="email" value="<?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

            <label class="lc-label">Status</label>
            <select class="lc-input" name="status">
                <?php $currentStatus = (string)($user['status'] ?? 'active'); ?>
                <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Ativo</option>
                <option value="disabled" <?= $currentStatus === 'disabled' ? 'selected' : '' ?>>Desativado</option>
            </select>

            <label class="lc-label">Papel</label>
            <select class="lc-input" name="role_id" required>
                <option value="">Selecione</option>
                <?php $currentRoleId = (int)($user['role_id'] ?? 0); ?>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $currentRoleId ? 'selected' : '' ?>><?= htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>

            <label class="lc-label">Nova senha (opcional)</label>
            <input class="lc-input" type="password" name="new_password" />

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/users">Voltar</a>
            </div>
        </form>
    <?php else: ?>
        <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr;">
            <div>
                <div class="lc-muted">Nome</div>
                <div><?= htmlspecialchars((string)($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted">E-mail</div>
                <div><?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted">Status</div>
                <div><?= htmlspecialchars((string)($user['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted">Papel</div>
                <div><?= htmlspecialchars((string)($user['role_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <a class="lc-btn lc-btn--secondary" href="/users">Voltar</a>
        </div>
    <?php endif; ?>

    <?php if ($can('users.delete')): ?>
        <form method="post" action="/users/disable" style="margin-top:16px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>" />
            <button class="lc-btn lc-btn--danger" type="submit">Desativar usuário</button>
        </form>
    <?php endif; ?>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
