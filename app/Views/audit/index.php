<?php
$title = 'Auditoria';
$items = $items ?? [];
$filters = $filters ?? ['action' => '', 'from' => '', 'to' => ''];
ob_start();
?>
<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__title">Filtros</div>

    <form method="get" class="lc-form" action="/audit-logs">
        <label class="lc-label">Ação (ex.: auth.login, users.create)</label>
        <input class="lc-input" type="text" name="action" value="<?= htmlspecialchars((string)$filters['action'], ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">De</label>
        <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)$filters['from'], ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Até</label>
        <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)$filters['to'], ENT_QUOTES, 'UTF-8') ?>" />

        <div style="margin-top:14px; display:flex; gap:10px; align-items:center;">
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
                <th>IP</th>
                <th>Meta</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td><?= htmlspecialchars((string)$it['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$it['action'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="max-width:360px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= htmlspecialchars((string)($it['meta_json'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
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
