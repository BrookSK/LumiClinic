<?php
/** @var array<string,mixed> $appointment */
/** @var list<array<string,mixed>> $logs */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Logs do agendamento';
$appointmentId = (int)($appointment['id'] ?? 0);

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
    <div class="lc-badge lc-badge--primary">Logs</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <?php if ($can('scheduling.logs')): ?>
            <form method="post" action="/schedule/gcal/force-sync" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="appointment_id" value="<?= (int)$appointmentId ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Forçar sync Google Calendar</button>
            </form>
        <?php endif; ?>
        <a class="lc-btn lc-btn--secondary" href="/schedule">Voltar</a>
    </div>
</div>

<?php if (isset($error) && trim((string)$error) !== ''): ?>
    <div class="lc-alert lc-alert--danger">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (isset($ok) && trim((string)$ok) !== ''): ?>
    <div class="lc-alert lc-alert--success">
        <?= htmlspecialchars((string)$ok, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__title">Agendamento #<?= (int)$appointmentId ?></div>
    <div class="lc-card__body">
        Início: <?= htmlspecialchars((string)($appointment['start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br />
        Fim: <?= htmlspecialchars((string)($appointment['end_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br />
        Status: <?= htmlspecialchars((string)($appointment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Histórico (imutável)</div>

    <?php if (($logs ?? []) === []): ?>
        <div class="lc-card__body"><div class="lc-muted">Sem logs.</div></div>
    <?php else: ?>
        <div class="lc-table-wrap">
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Ação</th>
                    <th>Em</th>
                    <th>Usuário</th>
                    <th>IP</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?= (int)$l['id'] ?></td>
                        <td><?= htmlspecialchars((string)$l['action'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$l['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <?php
                        $uname = trim((string)($l['user_name'] ?? ''));
                        $uemail = trim((string)($l['user_email'] ?? ''));
                        $ulabel = $uname !== '' ? $uname : ($uemail !== '' ? $uemail : '');
                        ?>
                        <td><?= htmlspecialchars($ulabel !== '' ? $ulabel : '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($l['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
