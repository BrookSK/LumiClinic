<?php
$title = 'Admin - Assinatura';
$csrf = $_SESSION['_csrf'] ?? '';
$row = $row ?? [];
$plans = $plans ?? [];
$ok = isset($ok) ? (string)$ok : '';
$error = isset($error) ? (string)$error : '';

$statusLabel = ['trial'=>'Teste','active'=>'Ativa','past_due'=>'Em atraso','canceled'=>'Cancelada','suspended'=>'Suspensa',''=>'—'];
$statusColor = ['trial'=>'#eeb810','active'=>'#16a34a','past_due'=>'#b5841e','canceled'=>'#b91c1c','suspended'=>'#6b7280',''=>'#6b7280'];
$gatewayLabel = ['asaas'=>'Asaas','mercadopago'=>'Mercado Pago',''=>'—'];

$planLabel = [];
foreach ($plans as $p) {
    $name = trim((string)($p['name'] ?? ''));
    $code = trim((string)($p['code'] ?? ''));
    $planLabel[(int)($p['id'] ?? 0)] = $name !== '' ? $name : $code;
}

$fmt = function ($v, string $f = 'd/m/Y H:i'): string {
    $s = trim((string)($v ?? ''));
    if ($s === '') return '—';
    try { return (new \DateTimeImmutable($s))->format($f); } catch (\Throwable $e) { return $s; }
};

$subStatus = (string)($row['subscription_status'] ?? '');
$stLbl = $statusLabel[$subStatus] ?? $subStatus;
$stClr = $statusColor[$subStatus] ?? '#6b7280';
$gateway = (string)($row['gateway_provider'] ?? '');
$gwLbl = $gatewayLabel[$gateway] ?? ($gateway !== '' ? $gateway : '—');
$planName = trim((string)($row['plan_name'] ?? $row['plan_code'] ?? ''));
$curPlanId = (int)($row['plan_id'] ?? 0);

$price = '—';
if (isset($row['plan_price_cents']) && $row['plan_price_cents'] !== null) {
    $price = 'R$ ' . number_format(max(0, (int)$row['plan_price_cents']) / 100, 2, ',', '.');
}

ob_start();
?>

<a href="/sys/billing" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para assinaturas
</a>

<?php if ($ok !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Header -->
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
</div>

<!-- Resumo -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px;">
    <div style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-size:12px;color:rgba(31,41,55,.45);font-weight:600;">Plano</div>
        <div style="font-weight:800;font-size:16px;margin-top:4px;"><?= htmlspecialchars($planName !== '' ? $planName : '—', ENT_QUOTES, 'UTF-8') ?></div>
        <div style="font-size:12px;color:rgba(31,41,55,.45);margin-top:2px;"><?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>/mês</div>
    </div>
    <div style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-size:12px;color:rgba(31,41,55,.45);font-weight:600;">Gateway</div>
        <div style="font-weight:800;font-size:16px;margin-top:4px;"><?= htmlspecialchars($gwLbl, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-size:12px;color:rgba(31,41,55,.45);font-weight:600;">Período atual</div>
        <div style="font-weight:700;font-size:13px;margin-top:4px;"><?= htmlspecialchars($fmt($row['current_period_start'] ?? null, 'd/m/Y'), ENT_QUOTES, 'UTF-8') ?></div>
        <div style="font-size:12px;color:rgba(31,41,55,.45);">até <?= htmlspecialchars($fmt($row['current_period_end'] ?? null, 'd/m/Y'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div style="padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
        <div style="font-size:12px;color:rgba(31,41,55,.45);font-weight:600;">Teste até</div>
        <div style="font-weight:700;font-size:13px;margin-top:4px;"><?= htmlspecialchars($fmt($row['trial_ends_at'] ?? null, 'd/m/Y'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>

<!-- Ações -->
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:14px;">Gerenciar</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
        <form method="post" action="/sys/billing/set-plan" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="clinic_id" value="<?= (int)($row['id'] ?? 0) ?>" />
            <div class="lc-field">
                <label class="lc-label">Alterar plano</label>
                <select class="lc-select" name="plan_id" required>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === $curPlanId) ? 'selected' : '' ?>><?= htmlspecialchars($planLabel[(int)$p['id']] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="margin-top:8px;">Salvar plano</button>
        </form>

        <form method="post" action="/sys/billing/set-status" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="clinic_id" value="<?= (int)($row['id'] ?? 0) ?>" />
            <div class="lc-field">
                <label class="lc-label">Alterar status</label>
                <select class="lc-select" name="status" required>
                    <?php foreach ($statusLabel as $k=>$v): if ($k === '') continue; ?>
                        <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $subStatus === $k ? 'selected' : '' ?>><?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="margin-top:8px;">Salvar status</button>
        </form>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <form method="post" action="/sys/billing/grant-month" style="margin:0;" onsubmit="return confirm('Conceder +1 mês de acesso?');">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="clinic_id" value="<?= (int)($row['id'] ?? 0) ?>" />
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Conceder +1 mês</button>
        </form>
        <form method="post" action="/sys/billing/ensure-gateway" style="margin:0;" onsubmit="return confirm('Criar/atualizar cobrança no gateway?');">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="clinic_id" value="<?= (int)($row['id'] ?? 0) ?>" />
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Sincronizar gateway</button>
        </form>
    </div>
</div>

<!-- Info técnica -->
<details>
    <summary style="font-size:12px;color:rgba(31,41,55,.40);cursor:pointer;list-style:none;">Informações técnicas</summary>
    <div style="margin-top:8px;padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);font-size:12px;color:rgba(31,41,55,.50);">
        <div>Asaas ID: <code><?= htmlspecialchars((string)($row['asaas_subscription_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></code></div>
        <div>Mercado Pago ID: <code><?= htmlspecialchars((string)($row['mp_preapproval_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></code></div>
        <div>Em atraso desde: <?= htmlspecialchars($fmt($row['past_due_since'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
        <div>Criada em: <?= htmlspecialchars($fmt($row['subscription_created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
        <div>Atualizada em: <?= htmlspecialchars($fmt($row['subscription_updated_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</details>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
