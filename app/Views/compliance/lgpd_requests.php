<?php
$title = 'LGPD (Backoffice)';
$csrf = $_SESSION['_csrf'] ?? '';
$items = $items ?? [];
$status = $status ?? 'pending';
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">LGPD</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/">Dashboard</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/compliance/lgpd-requests" class="lc-form">
            <label class="lc-label">Status</label>
            <select class="lc-select" name="status">
                <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pendente</option>
                <option value="processed" <?= $status==='processed'?'selected':'' ?>>Processado</option>
                <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Rejeitado</option>
                <option value="all" <?= $status==='all'?'selected':'' ?>>Todos</option>
            </select>
            <button class="lc-btn lc-btn--primary" type="submit">Aplicar</button>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Solicitações</div>
    <div class="lc-card__body">
        <?php if (!is_array($items) || $items === []): ?>
            <div>Nenhuma solicitação.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Paciente</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $r): ?>
                        <tr>
                            <td><?= (int)($r['id'] ?? 0) ?></td>
                            <td>
                                #<?= (int)($r['patient_id'] ?? 0) ?>
                                <?= htmlspecialchars((string)($r['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= htmlspecialchars((string)($r['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="min-width:420px;">
                                <div class="lc-flex lc-flex--wrap" style="gap:8px;">
                                    <a class="lc-btn lc-btn--secondary" href="/compliance/lgpd-requests/export?id=<?= (int)($r['id'] ?? 0) ?>">Export JSON</a>

                                    <form method="post" action="/compliance/lgpd-requests/process" class="lc-form lc-flex" style="gap:8px; align-items:flex-end;">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>" />
                                        <select class="lc-select" name="decision">
                                            <option value="processed">Processar</option>
                                            <option value="rejected">Rejeitar</option>
                                        </select>
                                        <input class="lc-input" type="text" name="note" placeholder="nota (opcional)" />
                                        <button class="lc-btn lc-btn--primary" type="submit">OK</button>
                                    </form>

                                    <form method="post" action="/compliance/lgpd-requests/anonymize" class="lc-form">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>" />
                                        <button class="lc-btn lc-btn--danger" type="submit">Anonimizar</button>
                                    </form>
                                </div>
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
