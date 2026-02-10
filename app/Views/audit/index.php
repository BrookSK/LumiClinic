<?php
$title = 'Auditoria';
$payload = $items ?? [];
$items = (is_array($payload) && isset($payload['rows']) && is_array($payload['rows'])) ? $payload['rows'] : [];
$hasNext = (is_array($payload) && isset($payload['has_next'])) ? (bool)$payload['has_next'] : false;
$filters = $filters ?? ['action' => '', 'from' => '', 'to' => ''];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
ob_start();
?>
<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__title">Filtros</div>

    <form method="get" class="lc-form" action="/audit-logs">
        <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
        <input type="hidden" name="page" value="1" />
        <label class="lc-label">Ação (ex.: auth.login, users.create)</label>
        <input class="lc-input" type="text" name="action" value="<?= htmlspecialchars((string)$filters['action'], ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">De</label>
        <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)$filters['from'], ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Até</label>
        <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)$filters['to'], ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
            <button class="lc-btn lc-btn--primary" type="submit">Aplicar</button>
            <a class="lc-btn lc-btn--secondary" href="/audit-logs/export?action=<?= urlencode((string)$filters['action']) ?>&from=<?= urlencode((string)$filters['from']) ?>&to=<?= urlencode((string)$filters['to']) ?>">Exportar CSV</a>
        </div>
    </form>
</div>

<div class="lc-card">
    <div class="lc-card__title">Eventos</div>

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
                <th>User-Agent</th>
                <th>Meta</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td><?= htmlspecialchars((string)($it['occurred_at'] ?? $it['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$it['action'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?= htmlspecialchars((string)($it['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($it['entity_id'])): ?>
                            #<?= (int)$it['entity_id'] ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string)($it['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars((string)($it['user_agent'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td style="max-width:360px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars((string)($it['meta_json'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
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
                <a class="lc-btn lc-btn--secondary" href="/audit-logs?action=<?= urlencode((string)$filters['action']) ?>&from=<?= urlencode((string)$filters['from']) ?>&to=<?= urlencode((string)$filters['to']) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
            <?php endif; ?>
            <?php if ($hasNext): ?>
                <a class="lc-btn lc-btn--secondary" href="/audit-logs?action=<?= urlencode((string)$filters['action']) ?>&from=<?= urlencode((string)$filters['from']) ?>&to=<?= urlencode((string)$filters['to']) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
