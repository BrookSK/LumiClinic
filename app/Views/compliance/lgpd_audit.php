<?php
$title = 'LGPD (Auditoria)';
$csrf = $_SESSION['_csrf'] ?? '';

$from = isset($from) ? (string)$from : '';
$to = isset($to) ? (string)$to : '';
$patient_id = isset($patient_id) ? (int)$patient_id : 0;
$user_id = isset($user_id) ? (int)$user_id : 0;

$sensitive = isset($sensitive) && is_array($sensitive) ? $sensitive : [];
$exports = isset($exports) && is_array($exports) ? $exports : [];

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
    <div class="lc-badge lc-badge--primary">LGPD (Auditoria)</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/compliance/lgpd-requests">Solicitações LGPD</a>
        <a class="lc-btn lc-btn--secondary" href="/">Dashboard</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/compliance/lgpd-audit" class="lc-form lc-grid lc-grid--4 lc-gap-grid">
            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Paciente ID</label>
                <input class="lc-input" type="number" name="patient_id" value="<?= (int)$patient_id ?>" min="0" />
            </div>

            <div class="lc-field">
                <label class="lc-label">User ID</label>
                <input class="lc-input" type="number" name="user_id" value="<?= (int)$user_id ?>" min="0" />
            </div>

            <div class="lc-flex lc-gap-sm" style="grid-column: 1 / -1; align-items:center;">
                <button class="lc-btn lc-btn--primary" type="submit">Aplicar</button>
                <?php if ($can('compliance.lgpd.export')): ?>
                    <a class="lc-btn lc-btn--secondary" href="/compliance/lgpd-audit/export?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&patient_id=<?= (int)$patient_id ?>&user_id=<?= (int)$user_id ?>">Exportar CSV</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="lc-grid" style="grid-template-columns: 1fr; gap:14px;">
    <div class="lc-card">
        <div class="lc-card__title">Acessos a dados sensíveis</div>
        <div class="lc-card__body">
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data/Hora</th>
                        <th>User</th>
                        <th>Ação</th>
                        <th>Entidade</th>
                        <th>IP</th>
                        <th>Meta</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($sensitive === []): ?>
                        <tr><td colspan="7" class="lc-muted">Sem eventos.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sensitive as $it): ?>
                            <tr>
                                <td><?= (int)($it['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($it['occurred_at'] ?? $it['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($it['user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($it['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= htmlspecialchars((string)($it['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($it['entity_id'])): ?>
                                        #<?= (int)$it['entity_id'] ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)($it['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="max-width:360px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars((string)($it['meta_json'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="lc-card">
        <div class="lc-card__title">Exportações / Compartilhamentos</div>
        <div class="lc-card__body">
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data/Hora</th>
                        <th>User</th>
                        <th>Ação</th>
                        <th>Entidade</th>
                        <th>Formato</th>
                        <th>IP</th>
                        <th>Meta</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($exports === []): ?>
                        <tr><td colspan="8" class="lc-muted">Sem exportações.</td></tr>
                    <?php else: ?>
                        <?php foreach ($exports as $it): ?>
                            <tr>
                                <td><?= (int)($it['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($it['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($it['user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($it['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= htmlspecialchars((string)($it['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($it['entity_id'])): ?>
                                        #<?= (int)$it['entity_id'] ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)($it['format'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($it['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="max-width:360px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars((string)($it['meta_json'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
