<?php
$title = 'Admin do Sistema';
$rows = $rows ?? [];
$clinics = $clinics ?? [];
$clinicId = isset($clinic_id) ? (int)$clinic_id : -1;
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Termos do Owner (LGPD &amp; Políticas)</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/legal-owner-acceptances">Relatório de aceites</a>
        <a class="lc-btn lc-btn--primary" href="/sys/legal-owner-documents/edit">Novo termo</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Filtro</div>
    <div class="lc-card__body">
        <form method="get" action="/sys/legal-owner-documents" class="lc-form">
            <label class="lc-label">Aplicação</label>
            <select class="lc-select" name="clinic_id">
                <option value="-1" <?= $clinicId === -1 ? 'selected' : '' ?>>Todos (global + por clínica)</option>
                <option value="0" <?= $clinicId === 0 ? 'selected' : '' ?>>Somente global (todas as clínicas)</option>
                <?php foreach ($clinics as $c): ?>
                    <?php $cid = (int)($c['id'] ?? 0); ?>
                    <option value="<?= $cid ?>" <?= $clinicId === $cid ? 'selected' : '' ?>>Clínica #<?= $cid ?> - <?= htmlspecialchars((string)($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <button class="lc-btn lc-btn--primary" type="submit">Aplicar</button>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Documentos</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Aplicação</th>
                <th>Título</th>
                <th>Obrigatório</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">Nenhum documento cadastrado.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                        $rid = (int)($r['id'] ?? 0);
                        $rcid = $r['clinic_id'] ?? null;
                        $scopeLabel = $rcid === null ? 'Global (todas as clínicas)' : ('Clínica #' . (int)$rcid);
                    ?>
                    <tr>
                        <td><?= $rid ?></td>
                        <td><?= htmlspecialchars((string)$scopeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($r['is_required'] ?? 0) === 1 ? 'Sim' : 'Não' ?></td>
                        <td><?= htmlspecialchars((string)($r['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a class="lc-btn lc-btn--secondary" href="/sys/legal-owner-documents/edit?id=<?= $rid ?>">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
