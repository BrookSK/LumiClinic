<?php
$title = 'Incidentes de Segurança';
$csrf = $_SESSION['_csrf'] ?? '';
$items = $items ?? [];
$users = $users ?? [];
$error = $error ?? '';

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

$severityLabel = [
    'low' => 'Baixa',
    'medium' => 'Média',
    'high' => 'Alta',
    'critical' => 'Crítica',
];

$statusLabel = [
    'open' => 'Aberto',
    'investigating' => 'Em apuração',
    'contained' => 'Contido',
    'resolved' => 'Resolvido',
];
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Segurança</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/">Dashboard</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($can('compliance.incidents.create')): ?>
    <div class="lc-card" style="margin-bottom:14px;">
        <div class="lc-card__title">Registrar incidente</div>
        <div class="lc-card__body">
            <form method="post" action="/compliance/incidents/create" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Severidade</label>
            <select class="lc-select" name="severity">
                <option value="low">Baixa</option>
                <option value="medium" selected>Média</option>
                <option value="high">Alta</option>
                <option value="critical">Crítica</option>
            </select>

            <label class="lc-label">Título</label>
            <input class="lc-input" type="text" name="title" required />

            <label class="lc-label">Descrição</label>
            <textarea class="lc-textarea" name="description" rows="3"></textarea>

                <button class="lc-btn lc-btn--primary" type="submit">Registrar</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__title">Incidentes</div>
    <div class="lc-card__body">
        <?php if (!is_array($items) || $items === []): ?>
            <div>Nenhum incidente.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Severidade</th>
                        <th>Status</th>
                        <th>Título</th>
                        <th>Detectado em</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <?php $sev = (string)($it['severity'] ?? ''); ?>
                            <?php $st = (string)($it['status'] ?? ''); ?>
                            <td><?= htmlspecialchars((string)($severityLabel[$sev] ?? $sev), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($statusLabel[$st] ?? $st), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['detected_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="min-width:420px;">
                                <?php if ($can('compliance.incidents.update')): ?>
                                    <form method="post" action="/compliance/incidents/update" class="lc-form lc-flex lc-flex--wrap" style="gap:8px; align-items:flex-end;">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)($it['id'] ?? 0) ?>" />

                                    <select class="lc-select" name="status">
                                        <option value="open" <?= (($it['status'] ?? '')==='open')?'selected':'' ?>>Aberto</option>
                                        <option value="investigating" <?= (($it['status'] ?? '')==='investigating')?'selected':'' ?>>Em apuração</option>
                                        <option value="contained" <?= (($it['status'] ?? '')==='contained')?'selected':'' ?>>Contido</option>
                                        <option value="resolved" <?= (($it['status'] ?? '')==='resolved')?'selected':'' ?>>Resolvido</option>
                                    </select>

                                    <select class="lc-select" name="assigned_to_user_id">
                                        <option value="">Responsável (opcional)</option>
                                        <?php foreach ($users as $u): ?>
                                            <?php
                                            $uid = (int)($u['id'] ?? 0);
                                            $uname = trim((string)($u['name'] ?? ''));
                                            $uemail = trim((string)($u['email'] ?? ''));
                                            $label = $uname !== '' ? $uname : ($uemail !== '' ? $uemail : ('Usuário #' . $uid));
                                            if ($uemail !== '' && $uname !== '') {
                                                $label .= ' (' . $uemail . ')';
                                            }
                                            $currentAssigned = (int)($it['assigned_to_user_id'] ?? 0);
                                            ?>
                                            <option value="<?= $uid ?>" <?= ($currentAssigned > 0 && $uid === $currentAssigned) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <input class="lc-input" type="text" name="corrective_action" placeholder="ação corretiva (opcional)" />

                                        <button class="lc-btn lc-btn--secondary" type="submit">Atualizar</button>
                                    </form>
                                <?php else: ?>
                                    <span style="opacity:.7;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
