<?php
/** @var array<string,mixed> $subscription */
/** @var array<string,mixed>|null $plan */
/** @var list<array<string,mixed>> $plans */
/** @var list<array<string,mixed>> $payments */
/** @var array $transcription */
$title = 'Assinatura';
$csrf = $_SESSION['_csrf'] ?? '';

$statusLabel = ['trial'=>'Teste','active'=>'Ativa','past_due'=>'Em atraso','canceled'=>'Cancelada','suspended'=>'Suspensa',''=>'—'];
$statusColor = ['trial'=>'#eeb810','active'=>'#16a34a','past_due'=>'#b5841e','canceled'=>'#b91c1c','suspended'=>'#6b7280',''=>'#6b7280'];

$fmt = function ($v, string $f = 'd/m/Y'): string {
    $s = trim((string)($v ?? ''));
    if ($s === '') return '—';
    try { return (new \DateTimeImmutable($s))->format($f); } catch (\Throwable $e) { return $s; }
};

$fmtMoney = function (?int $cents): string {
    return $cents === null ? '—' : ('R$ ' . number_format(max(0, $cents) / 100, 2, ',', '.'));
};

$decodeLimits = function ($j): array {
    if (is_array($j)) return $j;
    $r = trim((string)($j ?? ''));
    $d = $r !== '' ? json_decode($r, true) : null;
    return is_array($d) ? $d : [];
};

$planName = is_array($plan) ? trim((string)($plan['name'] ?? $plan['code'] ?? '')) : '';
$price = is_array($plan) && isset($plan['price_cents']) ? $fmtMoney((int)$plan['price_cents']) : '—';
$subStatus = (string)($subscription['status'] ?? '');
$stLbl = $statusLabel[$subStatus] ?? $subStatus;
$stClr = $statusColor[$subStatus] ?? '#6b7280';

$transcription = $transcription ?? ['limit'=>null,'used'=>0,'remaining'=>null,'blocked'=>false];
$tLimit = $transcription['limit'];
$tUsed = (int)$transcription['used'];
$tRemaining = $transcription['remaining'];
$tBlocked = (bool)$transcription['blocked'];

$isOwner = (function (): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $roles = $_SESSION['role_codes'] ?? [];
    return is_array($roles) && in_array('owner', $roles, true);
})();

ob_start();
?>

<div style="font-weight:850;font-size:22px;color:rgba(31,41,55,.96);margin-bottom:18px;">Minha assinatura</div>

<?php if (($ok ?? '') !== ''): ?><div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$ok, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (($error ?? '') !== ''): ?><div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<!-- Resumo -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-size:12px;color:rgba(31,41,55,.45);font-weight:600;">Plano</div>
        <div style="font-weight:800;font-size:18px;margin-top:4px;"><?= htmlspecialchars($planName !== '' ? $planName : '—', ENT_QUOTES, 'UTF-8') ?></div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;"><?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>/mês</div>
    </div>
    <div style="padding:18px;border-radius:14px;border:1px solid <?= $stClr ?>22;background:<?= $stClr ?>06;box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-size:12px;color:rgba(31,41,55,.45);font-weight:600;">Status</div>
        <div style="font-weight:800;font-size:18px;margin-top:4px;color:<?= $stClr ?>;"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></div>
        <div style="font-size:12px;color:rgba(31,41,55,.45);margin-top:2px;">Até <?= htmlspecialchars($fmt($subscription['current_period_end'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div style="padding:18px;border-radius:14px;border:1px solid <?= $tBlocked ? 'rgba(185,28,28,.22)' : 'rgba(17,24,39,.08)' ?>;background:<?= $tBlocked ? 'rgba(185,28,28,.04)' : 'var(--lc-surface)' ?>;box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-size:12px;color:rgba(31,41,55,.45);font-weight:600;">Transcrição de áudio</div>
        <?php if ($tLimit === null): ?>
            <div style="font-weight:800;font-size:18px;margin-top:4px;">Ilimitado</div>
        <?php else: ?>
            <div style="font-weight:800;font-size:18px;margin-top:4px;color:<?= $tBlocked ? '#b91c1c' : 'rgba(31,41,55,.96)' ?>;"><?= $tUsed ?> / <?= $tLimit ?> min</div>
            <div style="margin-top:6px;height:6px;border-radius:999px;background:rgba(17,24,39,.08);overflow:hidden;">
                <?php $pct = $tLimit > 0 ? min(100, round(($tUsed / $tLimit) * 100)) : 0; ?>
                <div style="height:100%;width:<?= $pct ?>%;border-radius:999px;background:<?= $tBlocked ? '#b91c1c' : ($pct > 80 ? '#b5841e' : '#16a34a') ?>;"></div>
            </div>
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">
                <?php if ($tBlocked): ?>
                    Limite atingido. Faça upgrade para continuar transcrevendo.
                <?php elseif ($tRemaining !== null): ?>
                    <?= $tRemaining ?> min restantes este mês
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Planos disponíveis -->
<?php if (is_array($plans) && $plans !== []): ?>
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="font-weight:750;font-size:15px;color:rgba(31,41,55,.90);margin-bottom:14px;">Planos disponíveis</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
        <?php foreach ($plans as $p): ?>
            <?php
            $pid = (int)($p['id'] ?? 0);
            $pName = trim((string)($p['name'] ?? $p['code'] ?? ''));
            $pCents = isset($p['price_cents']) ? (int)$p['price_cents'] : null;
            $limits = $decodeLimits($p['limits_json'] ?? null);
            $isCurrent = ((int)($subscription['plan_id'] ?? 0) === $pid);
            $tMin = (int)($limits['transcription_minutes'] ?? 0);
            ?>
            <div style="padding:16px;border-radius:14px;border:<?= $isCurrent ? '2px solid rgba(238,184,16,.40)' : '1px solid rgba(17,24,39,.08)' ?>;background:<?= $isCurrent ? 'rgba(253,229,159,.06)' : 'rgba(0,0,0,.01)' ?>;">
                <div style="font-weight:800;font-size:15px;color:rgba(31,41,55,.96);"><?= htmlspecialchars($pName, ENT_QUOTES, 'UTF-8') ?></div>
                <div style="font-size:20px;font-weight:900;color:rgba(129,89,1,1);margin-top:6px;"><?= $fmtMoney($pCents) ?><span style="font-size:12px;font-weight:600;color:rgba(31,41,55,.40);">/mês</span></div>
                <div style="font-size:12px;color:rgba(31,41,55,.50);margin-top:8px;line-height:1.6;">
                    Transcrição: <?= $tMin > 0 ? $tMin . ' min/mês' : 'Ilimitado' ?><br>
                    Usuários: <?= (int)($limits['users'] ?? 0) > 0 ? (int)$limits['users'] : 'Ilimitado' ?><br>
                    Pacientes: <?= (int)($limits['patients'] ?? 0) > 0 ? (int)$limits['patients'] : 'Ilimitado' ?>
                </div>
                <?php if ($isOwner && !$isCurrent): ?>
                    <form method="post" action="/billing/subscription/change-plan" style="margin-top:10px;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="plan_id" value="<?= $pid ?>" />
                        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit" style="width:100%;justify-content:center;">Selecionar</button>
                    </form>
                <?php elseif ($isCurrent): ?>
                    <div style="margin-top:10px;text-align:center;font-size:12px;font-weight:700;color:rgba(129,89,1,1);">Plano atual</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Cobranças -->
<?php if (is_array($payments) && $payments !== []): ?>
<details style="margin-bottom:16px;">
    <summary style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);cursor:pointer;list-style:none;font-weight:750;font-size:14px;color:rgba(31,41,55,.90);display:flex;align-items:center;justify-content:space-between;">
        Cobranças (<?= count($payments) ?>)
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="color:rgba(31,41,55,.35);"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div style="margin-top:8px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);overflow:hidden;">
        <div class="lc-table-wrap">
            <table class="lc-table">
                <thead><tr><th>Vencimento</th><th>Valor</th><th>Status</th><th>Pago em</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($payments as $pm): ?>
                    <?php
                    $stUp = strtoupper((string)($pm['status'] ?? ''));
                    $pmLbl = in_array($stUp,['RECEIVED','CONFIRMED'],true) ? 'Pago' : (in_array($stUp,['PENDING','AWAITING_RISK_ANALYSIS'],true) ? 'Pendente' : ($stUp === 'OVERDUE' ? 'Vencido' : (string)($pm['status'] ?? '')));
                    $pmClr = in_array($stUp,['RECEIVED','CONFIRMED'],true) ? '#16a34a' : ($stUp === 'OVERDUE' ? '#b91c1c' : '#6b7280');
                    ?>
                    <tr>
                        <td style="font-size:13px;"><?= htmlspecialchars((string)($pm['dueDate'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-size:13px;font-weight:600;"><?= isset($pm['value']) ? 'R$ ' . number_format((float)$pm['value'], 2, ',', '.') : '—' ?></td>
                        <td><span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $pmClr ?>18;color:<?= $pmClr ?>;border:1px solid <?= $pmClr ?>30"><?= htmlspecialchars($pmLbl, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td style="font-size:12px;color:rgba(31,41,55,.50);"><?= htmlspecialchars((string)($pm['paymentDate'] ?? $pm['clientPaymentDate'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-size:12px;">
                            <?php $inv = (string)($pm['invoiceUrl'] ?? ''); $bol = (string)($pm['bankSlipUrl'] ?? ''); ?>
                            <?php if ($inv !== ''): ?><a href="<?= htmlspecialchars($inv, ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="color:rgba(129,89,1,1);font-weight:600;">Fatura</a><?php endif; ?>
                            <?php if ($bol !== ''): ?> <a href="<?= htmlspecialchars($bol, ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="color:rgba(129,89,1,1);font-weight:600;">Boleto</a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</details>
<?php endif; ?>

<!-- Cancelar -->
<?php if ($isOwner): ?>
<details>
    <summary style="font-size:12px;color:rgba(185,28,28,.50);cursor:pointer;list-style:none;">Cancelar assinatura</summary>
    <div style="margin-top:8px;padding:14px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
        <div style="font-size:12px;color:rgba(185,28,28,.70);margin-bottom:8px;">O cancelamento encerra sua assinatura ao fim do período atual. Você perderá acesso ao sistema.</div>
        <form method="post" action="/billing/subscription/cancel" style="margin:0;" onsubmit="return confirm('Tem certeza? Esta ação não pode ser desfeita.');">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Confirmar cancelamento</button>
        </form>
    </div>
</details>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
