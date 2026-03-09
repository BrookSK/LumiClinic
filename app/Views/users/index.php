<?php
$title = 'Usuários';
$users = $users ?? [];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
$hasNext = isset($has_next) ? (bool)$has_next : false;

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
<div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Gestão de usuários</div>
    <?php if ($can('users.create')): ?>
        <a class="lc-btn lc-btn--primary" href="/users/create">Novo usuário</a>
    <?php endif; ?>
</div>

<div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-bottom:12px;">
    <div class="lc-muted">Página <?= (int)$page ?></div>
    <div class="lc-flex lc-gap-sm">
        <?php if ($page > 1): ?>
            <a class="lc-btn lc-btn--secondary" href="/users?per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
        <?php endif; ?>
        <?php if ($hasNext): ?>
            <a class="lc-btn lc-btn--secondary" href="/users?per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Lista</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Status</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars((string)$u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$u['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$u['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($can('users.update')): ?>
                            <a class="lc-btn lc-btn--secondary" href="/users/edit?id=<?= (int)$u['id'] ?>">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
