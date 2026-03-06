<?php
$title = 'Admin do Sistema';
$rows = $rows ?? [];
$filters = $filters ?? [];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
$hasNext = isset($has_next) ? (bool)$has_next : false;
$ok = isset($ok) ? (string)$ok : '';
$error = isset($error) ? (string)$error : '';

$provider = (string)($filters['provider'] ?? '');
$eventType = (string)($filters['event_type'] ?? '');
$externalId = (string)($filters['external_id'] ?? '');
$processed = (string)($filters['processed'] ?? '');
$from = (string)($filters['from'] ?? '');
$to = (string)($filters['to'] ?? '');

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Billing Events</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/billing">Config Billing</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/billing">Assinaturas</a>
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
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/sys/billing-events" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr 120px; align-items:end;">
            <div>
                <label class="lc-label">Provider</label>
                <input class="lc-input" type="text" name="provider" value="<?= htmlspecialchars($provider, ENT_QUOTES, 'UTF-8') ?>" placeholder="asaas / mercadopago" />
            </div>
            <div>
                <label class="lc-label">Event type</label>
                <input class="lc-input" type="text" name="event_type" value="<?= htmlspecialchars($eventType, ENT_QUOTES, 'UTF-8') ?>" placeholder="PAYMENT_..." />
            </div>
            <div>
                <label class="lc-label">External ID</label>
                <input class="lc-input" type="text" name="external_id" value="<?= htmlspecialchars($externalId, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <button class="lc-btn lc-btn--secondary" type="submit">Filtrar</button>

            <div>
                <label class="lc-label">Processado</label>
                <select class="lc-select" name="processed">
                    <option value="" <?= $processed === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="yes" <?= $processed === 'yes' ? 'selected' : '' ?>>Sim</option>
                    <option value="no" <?= $processed === 'no' ? 'selected' : '' ?>>Não</option>
                </select>
            </div>
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
    <div class="lc-card__header">Eventos</div>
    <div class="lc-card__body">
        <?php if (!is_array($rows) || $rows === []): ?>
            <div class="lc-muted">Nenhum evento encontrado.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Clínica</th>
                        <th>Provider</th>
                        <th>Tipo</th>
                        <th>External ID</th>
                        <th>Processado</th>
                        <th>Criado em</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int)($r['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($r['clinic_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['provider'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['event_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['external_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= ($r['processed_at'] ?? null) ? 'Sim' : 'Não' ?></td>
                            <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a class="lc-btn lc-btn--secondary" href="/sys/billing-events/show?id=<?= (int)($r['id'] ?? 0) ?>">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
                <div class="lc-muted">Página <?= (int)$page ?></div>
                <div class="lc-flex lc-gap-sm">
                    <?php if ($page > 1): ?>
                        <a class="lc-btn lc-btn--secondary" href="/sys/billing-events?page=<?= (int)($page - 1) ?>&per_page=<?= (int)$perPage ?>&provider=<?= urlencode($provider) ?>&event_type=<?= urlencode($eventType) ?>&external_id=<?= urlencode($externalId) ?>&processed=<?= urlencode($processed) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Anterior</a>
                    <?php endif; ?>
                    <?php if ($hasNext): ?>
                        <a class="lc-btn lc-btn--secondary" href="/sys/billing-events?page=<?= (int)($page + 1) ?>&per_page=<?= (int)$perPage ?>&provider=<?= urlencode($provider) ?>&event_type=<?= urlencode($eventType) ?>&external_id=<?= urlencode($externalId) ?>&processed=<?= urlencode($processed) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>">Próxima</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
