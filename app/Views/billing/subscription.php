<?php
/** @var array<string,mixed> $subscription */
/** @var array<string,mixed>|null $plan */
/** @var list<array<string,mixed>> $plans */
/** @var list<array<string,mixed>> $payments */
/** @var string $ok */
/** @var string $error */

$title = 'Assinatura';
$csrf = $_SESSION['_csrf'] ?? '';

$statusLabel = [
    'trial' => 'Período de teste',
    'active' => 'Ativa',
    'past_due' => 'Em atraso',
    'canceled' => 'Cancelada',
    'suspended' => 'Suspensa',
    '' => 'Não definida',
];

$fmtDateTime = function ($value): string {
    $v = trim((string)($value ?? ''));
    if ($v === '') {
        return '-';
    }
    try {
        return (new DateTimeImmutable($v))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $v;
    }
};

$price = '-';
if (is_array($plan) && isset($plan['price_cents'])) {
    $cents = (int)$plan['price_cents'];
    $price = 'R$ ' . number_format(max(0, $cents) / 100, 2, ',', '.');
}

$planName = is_array($plan) ? (string)($plan['name'] ?? '') : '';
$planCode = is_array($plan) ? (string)($plan['code'] ?? '') : '';
$planDisplay = trim($planName !== '' ? $planName : $planCode);

$subStatus = (string)($subscription['status'] ?? '');

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Minha assinatura</div>
</div>

<?php if (($ok ?? '') !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$ok, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (($error ?? '') !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Plano atual</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr;">
            <div>
                <div class="lc-muted" style="font-size:12px;">Plano</div>
                <div style="font-weight:700;">
                    <?= htmlspecialchars($planDisplay !== '' ? $planDisplay : '-', ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="lc-muted" style="font-size:12px; margin-top:2px;">Valor: <?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Status</div>
                <div><?= htmlspecialchars((string)($statusLabel[$subStatus] ?? $subStatus), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Período atual até</div>
                <div><?= htmlspecialchars($fmtDateTime($subscription['current_period_end'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:12px; align-items:center;">
            <form method="post" action="/billing/subscription/change-plan" class="lc-flex" style="gap:8px; align-items:center;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <select class="lc-input" name="plan_id">
                    <?php foreach (($plans ?? []) as $p): ?>
                        <?php $pid = (int)($p['id'] ?? 0); ?>
                        <option value="<?= $pid ?>" <?= ((int)($subscription['plan_id'] ?? 0) === $pid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($p['name'] ?? $p['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="lc-btn lc-btn--secondary" type="submit">Trocar plano</button>
            </form>

            <form method="post" action="/billing/subscription/ensure-gateway" style="margin:0;" onsubmit="return confirm('Atualizar cobrança no provedor?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Atualizar cobrança</button>
            </form>

            <form method="post" action="/billing/subscription/cancel" style="margin:0;" onsubmit="return confirm('Cancelar assinatura?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--primary" type="submit">Cancelar</button>
            </form>
        </div>

        <div class="lc-muted" style="margin-top:10px; font-size:12px; line-height:1.55;">
            Forma de cobrança: <?= htmlspecialchars((string)($subscription['gateway_provider'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Boletos / cobranças</div>
    <div class="lc-card__body">
        <?php if (!is_array($payments) || $payments === []): ?>
            <div class="lc-muted">Nenhuma cobrança encontrada.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                        <th>Links</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $p): ?>
                        <?php
                        $due = (string)($p['dueDate'] ?? '');
                        $value = isset($p['value']) ? (float)$p['value'] : null;
                        $st = (string)($p['status'] ?? '');
                        $paidAt = (string)($p['paymentDate'] ?? $p['clientPaymentDate'] ?? '');
                        $invoiceUrl = (string)($p['invoiceUrl'] ?? '');
                        $bankSlipUrl = (string)($p['bankSlipUrl'] ?? $p['bankSlipUrl'] ?? '');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($due !== '' ? $due : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($value !== null ? ('R$ ' . number_format(max(0, $value), 2, ',', '.')) : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($st !== '' ? $st : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($paidAt !== '' ? $paidAt : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($invoiceUrl !== ''): ?>
                                    <a class="lc-link" href="<?= htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">Fatura</a>
                                <?php endif; ?>
                                <?php if ($bankSlipUrl !== ''): ?>
                                    <?php if ($invoiceUrl !== ''): ?>
                                        <span class="lc-sep">|</span>
                                    <?php endif; ?>
                                    <a class="lc-link" href="<?= htmlspecialchars($bankSlipUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer">Boleto</a>
                                <?php endif; ?>
                                <?php if ($invoiceUrl === '' && $bankSlipUrl === ''): ?>
                                    -
                                <?php endif; ?>
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
?>
