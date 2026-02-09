<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';

$asaas_base_url = isset($asaas_base_url) ? (string)$asaas_base_url : '';
$asaas_api_key = isset($asaas_api_key) ? (string)$asaas_api_key : '';
$asaas_billing_type = isset($asaas_billing_type) ? (string)$asaas_billing_type : '';
$asaas_webhook_secret = isset($asaas_webhook_secret) ? (string)$asaas_webhook_secret : '';

$mp_base_url = isset($mp_base_url) ? (string)$mp_base_url : '';
$mp_access_token = isset($mp_access_token) ? (string)$mp_access_token : '';
$mp_payer_email_default = isset($mp_payer_email_default) ? (string)$mp_payer_email_default : '';
$mp_webhook_secret = isset($mp_webhook_secret) ? (string)$mp_webhook_secret : '';

ob_start();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:12px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Configurações (Billing)</div>
    <div style="display:flex; gap:10px;">
        <a class="lc-btn lc-btn--secondary" href="/sys/billing">Assinaturas</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<div class="lc-card lc-card--soft" style="margin-bottom:16px;">
    <div class="lc-card__header">
        <div class="lc-card__title">Asaas</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/billing" class="lc-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Base URL</label>
                <input class="lc-input" type="text" name="asaas_base_url" value="<?= htmlspecialchars($asaas_base_url, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://www.asaas.com/api/v3" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Billing Type</label>
                <input class="lc-input" type="text" name="asaas_billing_type" value="<?= htmlspecialchars($asaas_billing_type, ENT_QUOTES, 'UTF-8') ?>" placeholder="BOLETO" />
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">API Key</label>
                <input class="lc-input" type="password" name="asaas_api_key" value="<?= htmlspecialchars($asaas_api_key, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Webhook Secret</label>
                <input class="lc-input" type="password" name="asaas_webhook_secret" value="<?= htmlspecialchars($asaas_webhook_secret, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div style="grid-column: 1 / -1; display:flex; gap:10px; justify-content:flex-end;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header">
        <div class="lc-card__title">Mercado Pago</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/billing" class="lc-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Base URL</label>
                <input class="lc-input" type="text" name="mp_base_url" value="<?= htmlspecialchars($mp_base_url, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://api.mercadopago.com" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Payer Email (default)</label>
                <input class="lc-input" type="email" name="mp_payer_email_default" value="<?= htmlspecialchars($mp_payer_email_default, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Access Token</label>
                <input class="lc-input" type="password" name="mp_access_token" value="<?= htmlspecialchars($mp_access_token, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Webhook Secret</label>
                <input class="lc-input" type="password" name="mp_webhook_secret" value="<?= htmlspecialchars($mp_webhook_secret, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div style="grid-column: 1 / -1; display:flex; gap:10px; justify-content:flex-end;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
