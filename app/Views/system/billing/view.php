<?php
$title = 'Assinatura';
$csrf = $_SESSION['_csrf'] ?? '';
$row = $row ?? [];
$ok = isset($ok) ? (string)$ok : '';
$error = isset($error) ? (string)$error : '';

$statusLabel = [
    'trial' => 'Período de teste',
    'active' => 'Ativa',
    'past_due' => 'Em atraso',
    'canceled' => 'Cancelada',
    'suspended' => 'Suspensa',
    '' => 'Não definida',
];

$gatewayLabel = [
    'asaas' => 'Asaas',
    'mercadopago' => 'Mercado Pago',
    '' => 'Não definida',
];

$fmtDateTime = function ($value): string {
    $v = (string)($value ?? '');
    if (trim($v) === '') {
        return '-';
    }

    try {
        $dt = new DateTimeImmutable($v);
        return $dt->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $v;
    }
};

$fmtDate = function ($value): string {
    $v = (string)($value ?? '');
    if (trim($v) === '') {
        return '-';
    }

    try {
        $dt = new DateTimeImmutable($v);
        return $dt->format('d/m/Y');
    } catch (Throwable $e) {
        return $v;
    }
};

$price = '-';
if (isset($row['plan_price_cents']) && $row['plan_price_cents'] !== null) {
    $cents = (int)$row['plan_price_cents'];
    $price = 'R$ ' . number_format(max(0, $cents) / 100, 2, ',', '.');
}

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Assinatura</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/sys/billing">Voltar</a>
    </div>
</div>

<?php if ($ok !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Clínica</div>
    <div class="lc-card__body">
        <div style="font-weight:700; font-size:16px;">
            <?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php $domain = (string)($row['primary_domain'] ?? ''); ?>
        <?php if ($domain !== ''): ?>
            <div class="lc-muted" style="margin-top:4px;">Domínio: <?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="lc-grid lc-gap-grid" style="margin-top:12px;">
            <div>
                <div class="lc-muted" style="font-size:12px;">Identificação</div>
                <div><?= htmlspecialchars((string)($row['tenant_key'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Situação da clínica</div>
                <div><?= htmlspecialchars((string)($row['clinic_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Cadastrada em</div>
                <div><?= htmlspecialchars($fmtDateTime($row['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Dados da assinatura</div>
    <div class="lc-card__body">
        <?php
        $subStatus = (string)($row['subscription_status'] ?? '');
        $gateway = (string)($row['gateway_provider'] ?? '');
        $planName = (string)($row['plan_name'] ?? '');
        $planCode = (string)($row['plan_code'] ?? '');
        $planDisplay = trim($planName !== '' ? $planName : $planCode);
        ?>

        <div class="lc-grid lc-gap-grid">
            <div>
                <div class="lc-muted" style="font-size:12px;">Plano</div>
                <div><?= htmlspecialchars($planDisplay !== '' ? $planDisplay : '-', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="lc-muted" style="font-size:12px; margin-top:2px;">Valor: <?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Status</div>
                <div><?= htmlspecialchars((string)($statusLabel[$subStatus] ?? $subStatus), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Forma de cobrança</div>
                <div><?= htmlspecialchars((string)($gatewayLabel[$gateway] ?? $gateway), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="lc-grid lc-gap-grid" style="margin-top:12px;">
            <div>
                <div class="lc-muted" style="font-size:12px;">Início do período atual</div>
                <div><?= htmlspecialchars($fmtDateTime($row['current_period_start'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Fim do período atual</div>
                <div><?= htmlspecialchars($fmtDateTime($row['current_period_end'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Período de teste até</div>
                <div><?= htmlspecialchars($fmtDate($row['trial_ends_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="lc-grid lc-gap-grid" style="margin-top:12px;">
            <div>
                <div class="lc-muted" style="font-size:12px;">Em atraso desde</div>
                <div><?= htmlspecialchars($fmtDate($row['past_due_since'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Criada em</div>
                <div><?= htmlspecialchars($fmtDateTime($row['subscription_created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Última atualização</div>
                <div><?= htmlspecialchars($fmtDateTime($row['subscription_updated_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Ações administrativas</div>
    <div class="lc-card__body">
        <div class="lc-muted" style="line-height:1.55; margin-bottom:12px;">
            Use estas ações para liberar acesso quando necessário.
            <br />
            "Pular 1 mês" aqui significa conceder mais 1 mês de acesso (mês grátis).
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap">
            <form method="post" action="/sys/billing/grant-month" style="margin:0;" onsubmit="return confirm('Conceder mais 1 mês de acesso para esta clínica?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="clinic_id" value="<?= (int)($row['id'] ?? 0) ?>" />
                <button class="lc-btn lc-btn--primary" type="submit">Conceder +1 mês</button>
            </form>

            <form method="post" action="/sys/billing/skip-month" style="margin:0;" onsubmit="return confirm('Pular 1 mês (conceder mês grátis) para esta clínica?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="clinic_id" value="<?= (int)($row['id'] ?? 0) ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Pular 1 mês</button>
            </form>

            <form method="post" action="/sys/billing/ensure-gateway" style="margin:0;" onsubmit="return confirm('Criar/atualizar cobrança desta clínica no provedor?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="clinic_id" value="<?= (int)($row['id'] ?? 0) ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Criar/atualizar cobrança</button>
            </form>
        </div>

        <div class="lc-muted" style="margin-top:12px; font-size:12px; line-height:1.55;">
            Identificadores do provedor (uso interno):
            <br />
            Asaas: <?= htmlspecialchars((string)($row['asaas_subscription_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            <br />
            Mercado Pago: <?= htmlspecialchars((string)($row['mp_preapproval_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
