<?php
$title = 'Admin - Assinaturas';
$items = $items ?? [];
$plans = $plans ?? [];
$error = isset($error) ? (string)$error : '';
$csrf = $_SESSION['_csrf'] ?? '';

$statusLabel = ['trial'=>'Teste','active'=>'Ativa','past_due'=>'Em atraso','canceled'=>'Cancelada','suspended'=>'Suspensa'];
$statusColor = ['trial'=>'#eeb810','active'=>'#16a34a','past_due'=>'#b5841e','canceled'=>'#b91c1c','suspended'=>'#6b7280'];

$planLabel = [];
foreach ($plans as $p) {
    $code = (string)($p['code'] ?? '');
    $name = (string)($p['name'] ?? '');
    $planLabel[(int)($p['id'] ?? 0)] = trim($name !== '' ? $name : $code);
}

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Assinaturas</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;"><?= count($items) ?> clínica(s)</div>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/clinics">Clínicas</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/plans">Planos</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/settings/billing">Configurações</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($items === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">💳</div>
        <div style="font-size:14px;">Nenhuma assinatura encontrada.</div>
    </div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($items as $it): ?>
        <?php
        $cid = (int)($it['id'] ?? 0);
        $st = (string)($it['subscription_status'] ?? '');
        $stLbl = $statusLabel[$st] ?? ($st !== '' ? $st : '—');
        $stClr = $statusColor[$st] ?? '#6b7280';
        $curPlanId = (int)($it['plan_id'] ?? 0);
        $pLbl = $planLabel[$curPlanId] ?? (trim((string)($it['plan_name'] ?? $it['plan_code'] ?? '')) ?: '—');
        ?>
        <a href="/sys/billing/view?clinic_id=<?= $cid ?>" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 2px 8px rgba(17,24,39,.04);text-decoration:none;color:inherit;flex-wrap:wrap;transition:all 160ms ease;">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;flex-wrap:wrap;">
                <span style="font-weight:750;font-size:14px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)($it['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
                <span style="font-size:12px;color:rgba(31,41,55,.45);"><?= htmlspecialchars($pLbl, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <span style="font-size:12px;color:rgba(129,89,1,1);font-weight:600;">Ver detalhes →</span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
