<?php
$title = 'Incidentes de Segurança';
$csrf = $_SESSION['_csrf'] ?? '';
$items = $items ?? [];
$error = $error ?? '';
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Segurança</div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="lc-btn lc-btn--secondary" href="/">Dashboard</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Registrar incidente</div>
    <div class="lc-card__body">
        <form method="post" action="/compliance/incidents/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Severidade</label>
            <select class="lc-select" name="severity">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>

            <label class="lc-label">Título</label>
            <input class="lc-input" type="text" name="title" required />

            <label class="lc-label">Descrição</label>
            <textarea class="lc-textarea" name="description" rows="3"></textarea>

            <button class="lc-btn lc-btn--primary" type="submit">Registrar</button>
        </form>
    </div>
</div>

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
                        <th>ID</th>
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
                            <td><?= (int)($it['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($it['severity'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['detected_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="min-width:420px;">
                                <form method="post" action="/compliance/incidents/update" class="lc-form" style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($it['id'] ?? 0) ?>" />

                                    <select class="lc-select" name="status">
                                        <option value="open" <?= (($it['status'] ?? '')==='open')?'selected':'' ?>>open</option>
                                        <option value="investigating" <?= (($it['status'] ?? '')==='investigating')?'selected':'' ?>>investigating</option>
                                        <option value="contained" <?= (($it['status'] ?? '')==='contained')?'selected':'' ?>>contained</option>
                                        <option value="resolved" <?= (($it['status'] ?? '')==='resolved')?'selected':'' ?>>resolved</option>
                                    </select>

                                    <input class="lc-input" type="number" name="assigned_to_user_id" min="0" placeholder="assigned_to_user_id" />

                                    <input class="lc-input" type="text" name="corrective_action" placeholder="ação corretiva (opcional)" />

                                    <button class="lc-btn lc-btn--secondary" type="submit">Atualizar</button>
                                </form>
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
