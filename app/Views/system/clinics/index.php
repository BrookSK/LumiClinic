<?php
$title = 'Admin - Clínicas';
$items = $items ?? [];
$q = $q ?? '';
$csrf = $_SESSION['_csrf'] ?? '';

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Gestão de Clínicas</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;"><?= count($items) ?> clínica(s) cadastrada(s)</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/billing">Assinaturas</a>
        <a class="lc-btn lc-btn--primary lc-btn--sm" href="/sys/clinics/create">+ Nova clínica</a>
    </div>
</div>

<!-- Busca -->
<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <form method="get" action="/sys/clinics" style="display:flex;gap:10px;align-items:center;">
        <input class="lc-input" style="flex:1;max-width:400px;" type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome, identificação ou domínio..." />
        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Buscar</button>
        <?php if (trim((string)$q) !== ''): ?>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/clinics">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Lista -->
<?php if ($items === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">🏥</div>
        <div style="font-size:14px;">Nenhuma clínica encontrada.</div>
    </div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($items as $it): ?>
        <?php
        $id = (int)($it['id'] ?? 0);
        $status = (string)($it['status'] ?? '');
        $stLbl = $status === 'active' ? 'Ativo' : ($status === 'inactive' ? 'Inativo' : $status);
        $stClr = $status === 'active' ? '#16a34a' : '#b91c1c';
        $created = (string)($it['created_at'] ?? '');
        $createdFmt = $created !== '' ? date('d/m/Y', strtotime($created)) : '—';
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 2px 8px rgba(17,24,39,.04);flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;flex-wrap:wrap;">
                <a href="/sys/clinics/edit?id=<?= $id ?>" style="font-weight:750;font-size:14px;color:rgba(129,89,1,1);text-decoration:none;"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></a>
                <span style="font-size:12px;color:rgba(31,41,55,.40);font-family:monospace;"><?= htmlspecialchars((string)($it['tenant_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
                <span style="font-size:11px;color:rgba(31,41,55,.35);">Criada em <?= htmlspecialchars($createdFmt, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;">
                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/clinics/edit?id=<?= $id ?>">Editar</a>
                <form method="post" action="/sys/clinics/set-status" style="margin:0;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="id" value="<?= $id ?>" />
                    <input type="hidden" name="status" value="<?= $status === 'active' ? 'inactive' : 'active' ?>" />
                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit"><?= $status === 'active' ? 'Desativar' : 'Ativar' ?></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
