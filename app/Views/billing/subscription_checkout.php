<?php
/** @var array<string,mixed> $subscription */
/** @var array<string,mixed> $plan */
/** @var string $error */

$title = 'Checkout';
$csrf = $_SESSION['_csrf'] ?? '';
$error = isset($error) ? (string)$error : '';

$planName = (string)($plan['name'] ?? $plan['code'] ?? '');
$price = '-';
if (isset($plan['price_cents'])) {
    $cents = (int)$plan['price_cents'];
    $price = 'R$ ' . number_format(max(0, $cents) / 100, 2, ',', '.');
}

ob_start();
?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Checkout - Alterar plano</div>
    <div class="lc-card__body">
        <div class="lc-muted" style="margin-bottom:10px;">
            Plano selecionado: <strong><?= htmlspecialchars($planName, ENT_QUOTES, 'UTF-8') ?></strong>
            <br />
            Valor a cobrar agora: <strong><?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>

        <?php if ($error !== ''): ?>
            <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/billing/subscription/checkout" class="lc-form" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="plan_id" value="<?= (int)($plan['id'] ?? 0) ?>" />

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr;">
                <div>
                    <label class="lc-label">Nome no cartão</label>
                    <input class="lc-input" type="text" name="cc_holder" required />
                </div>
                <div>
                    <label class="lc-label">CPF do titular</label>
                    <input class="lc-input" type="text" name="cpf" required />
                </div>
                <div>
                    <label class="lc-label">Número do cartão</label>
                    <input class="lc-input" type="text" name="cc_number" required inputmode="numeric" />
                </div>
                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                    <div>
                        <label class="lc-label">Mês</label>
                        <input class="lc-input" type="text" name="cc_exp_month" required inputmode="numeric" placeholder="MM" />
                    </div>
                    <div>
                        <label class="lc-label">Ano</label>
                        <input class="lc-input" type="text" name="cc_exp_year" required inputmode="numeric" placeholder="AAAA" />
                    </div>
                    <div>
                        <label class="lc-label">CVV</label>
                        <input class="lc-input" type="password" name="cc_cvv" required inputmode="numeric" />
                    </div>
                </div>
                <div>
                    <label class="lc-label">CEP</label>
                    <input class="lc-input" type="text" name="postal_code" required />
                </div>
                <div>
                    <label class="lc-label">Número</label>
                    <input class="lc-input" type="text" name="address_number" required />
                </div>
                <div>
                    <label class="lc-label">Telefone (opcional)</label>
                    <input class="lc-input" type="text" name="phone" />
                </div>
                <div>
                    <label class="lc-label">Celular (opcional)</label>
                    <input class="lc-input" type="text" name="mobile" />
                </div>
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:12px; align-items:center;">
                <button class="lc-btn lc-btn--primary" type="submit">Pagar e alterar plano</button>
                <a class="lc-btn lc-btn--secondary" href="/billing/subscription">Cancelar</a>
            </div>

            <div class="lc-muted" style="margin-top:10px; font-size:12px; line-height:1.55;">
                A cobrança é feita por cartão de crédito e o plano só é efetivado após confirmação do pagamento.
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
