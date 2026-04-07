<?php
$title = 'Admin - Dashboard';
$tc = $total_clinics ?? 0; $ac = $active_clinics ?? 0;
$ss = $subs_by_status ?? []; $tu = $total_users ?? 0;
$tp = $total_patients ?? 0; $ta = $today_appts ?? 0;
$qp = $queue_pending ?? 0; $qd = $queue_dead ?? 0;
$re = $recent_errors ?? 0; $rc = $recent_clinics ?? [];
$mrr = $mrr ?? 0.0;
$sa = (int)($ss['active'] ?? 0); $st = (int)($ss['trial'] ?? 0);
$sp = (int)($ss['past_due'] ?? 0); $sc = (int)($ss['canceled'] ?? 0);
ob_start();
?>
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
<div><div style="font-weight:850;font-size:22px;color:#1f2937;">Dashboard</div><div style="font-size:13px;color:#6b7280;margin-top:2px;">Visão geral da plataforma</div></div>
<div style="display:flex;gap:8px;"><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/clinics">Clínicas</a><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/billing">Assinaturas</a><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/error-logs">Logs</a></div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;margin-bottom:18px;">
<div style="padding:20px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);"><div style="font-size:12px;font-weight:600;color:#9ca3af;margin-bottom:6px;">Clínicas</div><div style="font-size:26px;font-weight:900;color:#1f2937;"><?= $tc ?></div><div style="font-size:12px;color:#9ca3af;margin-top:4px;"><?= $ac ?> ativas</div></div>
<div style="padding:20px;border-radius:14px;border:1px solid rgba(34,197,94,.18);background:rgba(34,197,94,.03);box-shadow:0 4px 16px rgba(0,0,0,.06);"><div style="font-size:12px;font-weight:600;color:#9ca3af;margin-bottom:6px;">MRR</div><div style="font-size:26px;font-weight:900;color:#16a34a;">R$ <?= number_format($mrr, 2, ',', '.') ?></div><div style="font-size:12px;color:#9ca3af;margin-top:4px;"><?= $sa ?> ativas</div></div>
<div style="padding:20px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);"><div style="font-size:12px;font-weight:600;color:#9ca3af;margin-bottom:6px;">Usuários</div><div style="font-size:26px;font-weight:900;color:#1f2937;"><?= $tu ?></div><div style="font-size:12px;color:#9ca3af;margin-top:4px;">total</div></div>
<div style="padding:20px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);"><div style="font-size:12px;font-weight:600;color:#9ca3af;margin-bottom:6px;">Pacientes</div><div style="font-size:26px;font-weight:900;color:#1f2937;"><?= $tp ?></div><div style="font-size:12px;color:#9ca3af;margin-top:4px;">total</div></div>
<div style="padding:20px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);"><div style="font-size:12px;font-weight:600;color:#9ca3af;margin-bottom:6px;">Consultas hoje</div><div style="font-size:26px;font-weight:900;color:#1f2937;"><?= $ta ?></div><div style="font-size:12px;color:#9ca3af;margin-top:4px;">todas clínicas</div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
<div style="padding:18px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);">
<div style="font-weight:750;font-size:14px;color:#1f2937;margin-bottom:12px;">Assinaturas</div>
<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="font-size:13px;color:#6b7280;">✅ Ativas</span><span style="font-weight:800;color:#16a34a;"><?= $sa ?></span></div>
<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="font-size:13px;color:#6b7280;">🧪 Trial</span><span style="font-weight:800;color:#eeb810;"><?= $st ?></span></div>
<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="font-size:13px;color:#6b7280;">⚠️ Atraso</span><span style="font-weight:800;color:#b5841e;"><?= $sp ?></span></div>
<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="font-size:13px;color:#6b7280;">❌ Canceladas</span><span style="font-weight:800;color:#b91c1c;"><?= $sc ?></span></div>
</div>
<div style="padding:18px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);">
<div style="font-weight:750;font-size:14px;color:#1f2937;margin-bottom:12px;">Sistema</div>
<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="font-size:13px;color:#6b7280;">📋 Fila pendente</span><span style="font-weight:800;color:<?= $qp > 0 ? '#eeb810' : '#16a34a' ?>;"><?= $qp ?></span></div>
<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="font-size:13px;color:#6b7280;">💀 Fila morta</span><span style="font-weight:800;color:<?= $qd > 0 ? '#b91c1c' : '#16a34a' ?>;"><?= $qd ?></span></div>
<div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="font-size:13px;color:#6b7280;">🐛 Erros 24h</span><span style="font-weight:800;color:<?= $re > 0 ? '#b91c1c' : '#16a34a' ?>;"><?= $re ?></span></div>
<div style="margin-top:12px;display:flex;gap:8px;"><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/queue-jobs">Fila</a><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/error-logs">Erros</a></div>
</div>
</div>
<?php if (!empty($rc)): ?>
<div style="padding:18px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);">
<div style="font-weight:750;font-size:14px;color:#1f2937;margin-bottom:12px;">Últimas clínicas</div>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<thead><tr><th style="text-align:left;padding:8px 10px;border-bottom:1px solid #e5e7eb;color:#9ca3af;font-weight:600;font-size:11px;">Nome</th><th style="text-align:left;padding:8px 10px;border-bottom:1px solid #e5e7eb;color:#9ca3af;font-weight:600;font-size:11px;">Status</th><th style="text-align:left;padding:8px 10px;border-bottom:1px solid #e5e7eb;color:#9ca3af;font-weight:600;font-size:11px;">Criada</th><th style="padding:8px;"></th></tr></thead>
<tbody>
<?php foreach ($rc as $c): $s=(string)($c['status']??''); $sl=$s==='active'?'Ativa':'Inativa'; $clr=$s==='active'?'#16a34a':'#b91c1c'; ?>
<tr><td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;font-weight:600;"><?= htmlspecialchars((string)($c['name']??''),ENT_QUOTES,'UTF-8') ?></td><td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;"><span style="padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:<?=$clr?>18;color:<?=$clr?>;"><?=$sl?></span></td><td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;font-size:12px;color:#9ca3af;"><?= date('d/m/Y',strtotime((string)($c['created_at']??''))) ?></td><td style="padding:8px;"><a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/clinics/edit?id=<?=(int)($c['id']??0)?>" style="font-size:11px;">Editar</a></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
