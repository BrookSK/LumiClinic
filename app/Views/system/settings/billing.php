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

$baseUrl = getenv('APP_BASE_URL') ?: (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
);
$baseUrl = rtrim((string)$baseUrl, '/');
$webhookAsaasUrl = $baseUrl . '/webhooks/asaas';
$webhookMpUrl = $baseUrl . '/webhooks/mercadopago';

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Configurações de Assinatura</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Configure os gateways de pagamento e webhooks.</div>
    </div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/billing">Assinaturas</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/billing-events">Eventos</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<!-- Webhooks resumo -->
<div style="padding:16px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.10);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;color:rgba(129,89,1,1);margin-bottom:10px;">URLs de Webhook (copie e configure nos gateways)</div>
    <div style="display:flex;flex-direction:column;gap:8px;">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-weight:700;font-size:12px;color:rgba(31,41,55,.60);min-width:120px;">Asaas:</span>
            <code style="padding:6px 10px;border-radius:8px;background:rgba(255,255,255,.70);border:1px solid rgba(17,24,39,.08);font-size:12px;user-select:all;word-break:break-all;"><?= htmlspecialchars($webhookAsaasUrl, ENT_QUOTES, 'UTF-8') ?></code>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-weight:700;font-size:12px;color:rgba(31,41,55,.60);min-width:120px;">Mercado Pago:</span>
            <code style="padding:6px 10px;border-radius:8px;background:rgba(255,255,255,.70);border:1px solid rgba(17,24,39,.08);font-size:12px;user-select:all;word-break:break-all;"><?= htmlspecialchars($webhookMpUrl, ENT_QUOTES, 'UTF-8') ?></code>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-weight:700;font-size:12px;color:rgba(31,41,55,.60);min-width:120px;">Marketing WA:</span>
            <code style="padding:6px 10px;border-radius:8px;background:rgba(255,255,255,.70);border:1px solid rgba(17,24,39,.08);font-size:12px;user-select:all;word-break:break-all;"><?= htmlspecialchars($baseUrl . '/webhooks/marketing/whatsapp', ENT_QUOTES, 'UTF-8') ?></code>
        </div>
    </div>
    <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:8px;">Configure essas URLs nos respectivos painéis dos gateways. Os eventos de pagamento serão processados automaticamente.</div>
</div>

<div class="lc-card lc-card--soft" style="margin-bottom:16px;">
    <div class="lc-card__header">
        <div class="lc-card__title">Asaas</div>
    </div>
    <div class="lc-card__body">
        <div class="lc-alert lc-alert--info" style="margin-bottom:12px;">
            <div style="font-weight:700; margin-bottom:4px;">Webhook (Asaas)</div>
            <div>URL: <code><?= htmlspecialchars($webhookAsaasUrl, ENT_QUOTES, 'UTF-8') ?></code></div>
            <div style="margin-top:6px;">Autenticação: enviar o header <code>x-webhook-secret</code> com o mesmo valor do campo "Segredo do Webhook".</div>
        </div>

        <form method="post" action="/sys/settings/billing" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Base URL</label>
                <input class="lc-input" type="text" name="asaas_base_url" value="<?= htmlspecialchars($asaas_base_url, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://www.asaas.com/api/v3" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Tipo de cobrança</label>
                <input class="lc-input" type="text" name="asaas_billing_type" value="<?= htmlspecialchars($asaas_billing_type, ENT_QUOTES, 'UTF-8') ?>" placeholder="BOLETO" />
                <div class="lc-muted" style="margin-top:6px;">Ex.: BOLETO, CREDIT_CARD, PIX (conforme configuração do Asaas).</div>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">API Key</label>
                <input class="lc-input" type="password" name="asaas_api_key" value="<?= htmlspecialchars($asaas_api_key, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Segredo do Webhook</label>
                <input class="lc-input" type="password" name="asaas_webhook_secret" value="<?= htmlspecialchars($asaas_webhook_secret, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="lc-muted" style="margin-top:6px;">Use o mesmo valor no header <code>x-webhook-secret</code> ao configurar o webhook no Asaas.</div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column: 1 / -1;">
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
        <div class="lc-alert lc-alert--info" style="margin-bottom:12px;">
            <div style="font-weight:700; margin-bottom:4px;">Webhook (Mercado Pago)</div>
            <div>URL: <code><?= htmlspecialchars($webhookMpUrl, ENT_QUOTES, 'UTF-8') ?></code></div>
            <div style="margin-top:6px;">Autenticação: o Mercado Pago envia <code>x-signature</code> e <code>x-request-id</code>. O sistema valida usando o "Segredo do Webhook" abaixo.</div>
        </div>

        <form method="post" action="/sys/settings/billing" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Base URL</label>
                <input class="lc-input" type="text" name="mp_base_url" value="<?= htmlspecialchars($mp_base_url, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://api.mercadopago.com" />
            </div>

            <div class="lc-field">
                <label class="lc-label">E-mail do pagador (padrão)</label>
                <input class="lc-input" type="email" name="mp_payer_email_default" value="<?= htmlspecialchars($mp_payer_email_default, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="lc-muted" style="margin-top:6px;">Usado para criar a assinatura no Mercado Pago (preapproval) no modo atual do sistema.</div>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Access Token</label>
                <input class="lc-input" type="password" name="mp_access_token" value="<?= htmlspecialchars($mp_access_token, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Segredo do Webhook</label>
                <input class="lc-input" type="password" name="mp_webhook_secret" value="<?= htmlspecialchars($mp_webhook_secret, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="lc-muted" style="margin-top:6px;">Esse segredo é o valor fornecido pelo Mercado Pago em "Webhooks" na aplicação.</div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
