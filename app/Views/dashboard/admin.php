<?php
$title = 'Admin - Dashboard';
$totalClinics = $total_clinics ?? 0;
$activeClinics = $active_clinics ?? 0;
$subsByStatus = $subs_by_status ?? [];
$totalUsers = $total_users ?? 0;
$totalPatients = $total_patients ?? 0;
$todayAppts = $today_appts ?? 0;
$queuePending = $queue_pending ?? 0;
$queueDead = $queue_dead ?? 0;
$recentErrors = $recent_errors ?? 0;
$recentClinics = $recent_clinics ?? [];
$mrr = $mrr ?? 0.0;

$subsActive = (int)($subsByStatus['active'] ?? 0);
$subsTrial = (int)($subsByStatus['trial'] ?? 0);
$subsPastDue = (int)($subsByStatus['past_due'] ?? 0);
$subsCanceled = (int)($subsByStatus['canceled'] ?? 0);

ob_start();
?>

<style>
.ad-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:18px}
.ad-card{padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06)}
.ad-card__label{font-size:12px;font-weight:600;color:rgba(31,41,55,.45);margin-bottom:6px}
.ad-card__value{font-size:26px;font-weight:900;color:rgba(31,41,55,.96)}
.ad-card__sub{font-size:12px;color:rgba(31,41,55,.45);margin-top:4px}
.ad-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.ad-section__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:12px}
.ad-table{width:100%;border-collapse:collapse;font-size:13px}
.ad-table th{text-align:left;padding:8px 10px;border-bottom:1px solid rgba(17,24,39,.08);color:rgba(31,41,55,.50);font-weight:600;font-size:11px}
.ad-table td{padding:8px 10px;border-bottom:1px solid rgba(17,24,39,.04);color:rgba(31,41,55,.80)}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:22px;color:rgba(31,41,55,.96);">Dashboard</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Visão geral da plataforma</div>
    </div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/clinics">Clínicas</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/billing">Assinaturas</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/error-logs">Logs</a>
    </div>
</div>

<!-- KPIs -->
<div class="ad-grid">
    <div class="ad-card">
        <div class="ad-card__label">Clínicas</div>
        <div class="ad-card__value"><?= $totalClinics ?></div>
        <div class="ad-card__sub"><?= $activeClinics ?> ativas</div>
    </div>
    <div class="ad-card" style="border-color:rgba(34,197,94,.18);background:rgba(34,197,94,.03);">
        <div class="ad-card__label">MRR (Receita Mensal)</div>
        <div class="ad-card__value" style="color:#16a34a;">R$ <?= number_format($mrr, 2, ',', '.') ?></div>
        <div class="ad-card__sub"><?= $subsActive ?> assinaturas ativas</div>
    </div>
    <div class="ad-card">
        <div class="ad-card__label">Usuários</div>
        <div class="ad-card__value"><?= $totalUsers ?></div>
        <div class="ad-card__sub">em todas as clínicas</div>
    </div>
    <div class="ad-card">
        <div class="ad-card__label">Pacientes</div>
        <div class="ad-card__value"><?= number_format($totalPatients, 0, ',', '.') ?></div>
        <div class="ad-card__sub">cadastrados no total</div>
    </div>
    <div class="ad-card">
        <div class="ad-card__label">Consultas hoje</div>
        <div class="ad-card__value"><?= $todayAppts ?></div>
        <div class="ad-card__sub">em todas as clínicas</div>
    </div>
</div>

<!-- Assinaturas + Fila + Erros -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
    <div class="ad-section">
        <div class="ad-section__title">Assinaturas por status</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;color:rgba(31,41,55,.70);">✅ Ativas</span>
                <span style="font-weight:800;font-size:15px;color:#16a34a;"><?= $subsActive ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;color:rgba(31,41,55,.70);">🧪 Trial</span>
                <span style="font-weight:800;font-size:15px;color:#eeb810;"><?= $subsTrial ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;color:rgba(31,41,55,.70);">⚠️ Em atraso</span>
                <span style="font-weight:800;font-size:15px;color:#b5841e;"><?= $subsPastDue ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;color:rgba(31,41,55,.70);">❌ Canceladas</span>
                <span style="font-weight:800;font-size:15px;color:#b91c1c;"><?= $subsCanceled ?></span>
            </div>
        </div>
    </div>

    <div class="ad-section">
        <div class="ad-section__title">Sistema</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;color:rgba(31,41,55,.70);">📋 Fila pendente</span>
                <span style="font-weight:800;font-size:15px;color:<?= $queuePending > 0 ? '#eeb810' : '#16a34a' ?>;"><?= $queuePending ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;color:rgba(31,41,55,.70);">💀 Fila morta</span>
                <span style="font-weight:800;font-size:15px;color:<?= $queueDead > 0 ? '#b91c1c' : '#16a34a' ?>;"><?= $queueDead ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;color:rgba(31,41,55,.70);">🐛 Erros (24h)</span>
                <span style="font-weight:800;font-size:15px;color:<?= $recentErrors > 0 ? '#b91c1c' : '#16a34a' ?>;"><?= $recentErrors ?></span>
            </div>
        </div>
        <div style="margin-top:12px;display:flex;gap:8px;">
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/queue-jobs" style="font-size:11px;">Ver fila</a>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/error-logs" style="font-size:11px;">Ver erros</a>
        </div>
    </div>
</div>

<!-- Últimas clínicas -->
<?php if (!empty($recentClinics)): ?>
<div class="ad-section">
    <div class="ad-section__title">Últimas clínicas cadastradas</div>
    <table class="ad-table">
        <thead><tr><th>Nome</th><th>Status</th><th>Criada em</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recentClinics as $c):
            $st = (string)($c['status'] ?? '');
            $stLbl = $st === 'active' ? 'Ativa' : ($st === 'inactive' ? 'Inativa' : $st);
            $stClr = $st === 'active' ? '#16a34a' : '#b91c1c';
        ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars((string)($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span></td>
                <td style="font-size:12px;color:rgba(31,41,55,.50);"><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)($c['created_at'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></td>
                <td><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/clinics/edit?id=<?= (int)($c['id'] ?? 0) ?>" style="font-size:11px;">Editar</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
