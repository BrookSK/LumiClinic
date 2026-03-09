<?php
$title = 'Consentimento (Assinaturas) - Legado';
$terms = $terms ?? [];

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
    <div class="lc-badge lc-badge--primary">Consentimento (Legado)</div>
    <div>
        <?php if ($can('consent_terms.manage')): ?>
            <a class="lc-btn lc-btn--primary" href="/consent-terms/create">Novo termo</a>
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
                <th>Procedimento</th>
                <th>Título</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($terms as $t): ?>
                <tr>
                    <td><?= (int)$t['id'] ?></td>
                    <td><?= htmlspecialchars((string)$t['procedure_type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$t['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$t['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($can('consent_terms.manage')): ?>
                            <a class="lc-btn lc-btn--secondary" href="/consent-terms/edit?id=<?= (int)$t['id'] ?>">Editar</a>
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
