<?php
$title = 'Admin - LGPD & Termos (Owners)';
$rows = $rows ?? [];
$clinics = $clinics ?? [];
$clinicId = isset($clinic_id) ? (int)$clinic_id : -1;

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">LGPD & Termos (Owners)</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Termos que os donos de clínica precisam aceitar para usar o sistema.</div>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/legal-owner-acceptances">Relatório de aceites</a>
        <a class="lc-btn lc-btn--primary lc-btn--sm" href="/sys/legal-owner-documents/edit">+ Novo termo</a>
    </div>
</div>

<!-- Filtro -->
<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <form method="get" action="/sys/legal-owner-documents" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <div class="lc-field" style="min-width:280px;flex:1;">
            <label class="lc-label">Aplicação</label>
            <select class="lc-select" name="clinic_id">
                <option value="-1" <?= $clinicId === -1 ? 'selected' : '' ?>>Todos</option>
                <option value="0" <?= $clinicId === 0 ? 'selected' : '' ?>>Global (todas as clínicas)</option>
                <?php foreach ($clinics as $c): ?>
                    <?php $cid = (int)($c['id'] ?? 0); ?>
                    <option value="<?= $cid ?>" <?= $clinicId === $cid ? 'selected' : '' ?>><?= htmlspecialchars((string)($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="padding-bottom:1px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Filtrar</button></div>
    </form>
</div>

<!-- Lista -->
<?php if (!$rows): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);"><div style="font-size:32px;margin-bottom:8px;">📄</div><div>Nenhum documento cadastrado.</div></div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($rows as $r): ?>
        <?php
        $rid = (int)($r['id'] ?? 0);
        $rcid = $r['clinic_id'] ?? null;
        $scopeLabel = $rcid === null ? 'Global' : ('Clínica: ' . htmlspecialchars((string)($r['clinic_name'] ?? '#' . (int)$rcid), ENT_QUOTES, 'UTF-8'));
        $st = (string)($r['status'] ?? '');
        $stOk = $st === 'active';
        $req = (int)($r['is_required'] ?? 0) === 1;
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 2px 8px rgba(17,24,39,.04);flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;flex-wrap:wrap;">
                <span style="font-weight:750;font-size:14px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <span style="font-size:11px;color:rgba(31,41,55,.40);"><?= $scopeLabel ?></span>
                <?php if ($req): ?>
                    <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(185,28,28,.08);color:#b91c1c;border:1px solid rgba(185,28,28,.16);">Obrigatório</span>
                <?php endif; ?>
                <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:<?= $stOk ? 'rgba(22,163,74,.12)' : 'rgba(107,114,128,.10)' ?>;color:<?= $stOk ? '#16a34a' : '#6b7280' ?>;border:1px solid <?= $stOk ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;"><?= $stOk ? 'Ativo' : 'Inativo' ?></span>
            </div>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/legal-owner-documents/edit?id=<?= $rid ?>">Editar</a>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
