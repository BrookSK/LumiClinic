<?php
$title = 'Admin do Sistema';
$items = $items ?? [];
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
    <div class="lc-badge lc-badge--gold">Gestão de clínicas</div>
    <a class="lc-btn lc-btn--primary" href="/sys/clinics/create">Nova clínica</a>
</div>

<div class="lc-card">
    <div class="lc-card__title">Clínicas</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Tenant</th>
                <th>Status</th>
                <th>Criada em</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['tenant_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$it['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$it['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
