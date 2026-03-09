<?php
$title = 'Anamnese - Templates';
$templates = $templates ?? [];

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
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Templates de anamnese</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <?php if ($can('anamnesis.manage')): ?>
            <a class="lc-btn lc-btn--primary" href="/anamnesis/templates/create">Novo template</a>
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
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($templates as $t): ?>
                <tr>
                    <td><?= (int)$t['id'] ?></td>
                    <td><?= htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$t['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($can('anamnesis.manage')): ?>
                            <a class="lc-btn lc-btn--secondary" href="/anamnesis/templates/edit?id=<?= (int)$t['id'] ?>">Editar</a>
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
