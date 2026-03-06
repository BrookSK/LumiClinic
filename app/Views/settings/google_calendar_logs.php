<?php
$title = 'Google Calendar - Logs';
$rows = $rows ?? [];
$filters = $filters ?? [];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
$hasNext = isset($has_next) ? (bool)$has_next : false;
$ok = isset($ok) ? (string)$ok : '';
$error = isset($error) ? (string)$error : '';
$csrf = $_SESSION['_csrf'] ?? '';

$status = (string)($filters['status'] ?? '');
$action = (string)($filters['action'] ?? '');
$appointmentId = (string)($filters['appointment_id'] ?? '');
$userId = (string)($filters['user_id'] ?? '');
$from = (string)($filters['from'] ?? '');
$to = (string)($filters['to'] ?? '');

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Google Calendar - Logs</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/settings/google-calendar">Config</a>
        <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
    </div>
</div>

<?php if ($ok !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header">Forçar sync</div>
    <div class="lc-card__body">
        <form method="post" action="/settings/google-calendar/logs/force-sync" class="lc-form lc-flex lc-gap-sm lc-flex--wrap" style="align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div>
                <label class="lc-label">Appointment ID</label>
                <input class="lc-input" type="number" name="appointment_id" min="1" placeholder="123" />
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Enfileirar sync</button>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/settings/google-calendar/logs" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr 1fr 120px; align-items:end;">
            <div>
                <label class="lc-label">Status</label>
                <input class="lc-input" type="text" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" placeholder="ok / failed / skipped" />
            </div>
            <div>
                <label class="lc-label">Action</label>
                <input class="lc-input" type="text" name="action" value="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" placeholder="create/update/delete/sync" />
            </div>
            <div>
                <label class="lc-label">Appointment ID</label>
                <input class="lc-input" type="text" name="appointment_id" value="<?= htmlspecialchars($appointmentId, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <label class="lc-label">User ID</label>
                <input class="lc-input" type="text" name="user_id" value="<?= htmlspecialchars($userId, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <button class="lc-btn lc-btn--secondary" type="submit">Filtrar</button>

            <div>
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Últimos logs</div>
    <div class="lc-card__body">
        <?php if (!is_array($rows) || $rows === []): ?>
            <div class="lc-muted">Nenhum log encontrado.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Quando</th>
                        <th>Usuário</th>
                        <th>Appointment</th>
                        <th>Ação</th>
                        <th>Status</th>
                        <th>Mensagem</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int)($r['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string)($r['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (($r['user_id'] ?? null) !== null): ?>
                                    <div class="lc-muted" style="font-size:12px;">#<?= (int)($r['user_id'] ?? 0) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($r['appointment_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
                <div class="lc-muted">Página <?= (int)$page ?></div>
                <div class="lc-flex lc-gap-sm">
                    <?php if ($page > 1): ?>
                        <a class="lc-btn lc-btn--secondary" href="/settings/google-calendar/logs?page=<?= (int)($page - 1) ?>&per_page=<?= (int)$perPage ?>&status=<?= urlencode($status) ?>&action=<?= urlencode($action) ?>&appointment_id=<?= urlencode($appointmentId) ?>&user_id=<?= urlencode($userId) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Anterior</a>
                    <?php endif; ?>
                    <?php if ($hasNext): ?>
                        <a class="lc-btn lc-btn--secondary" href="/settings/google-calendar/logs?page=<?= (int)($page + 1) ?>&per_page=<?= (int)$perPage ?>&status=<?= urlencode($status) ?>&action=<?= urlencode($action) ?>&appointment_id=<?= urlencode($appointmentId) ?>&user_id=<?= urlencode($userId) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Próxima</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
