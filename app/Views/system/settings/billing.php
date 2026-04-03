<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';

$asaasEnv = isset($asaas_env) && (string)$asaas_env !== '' ? (string)$asaas_env : 'sandbox';
$asaasBillingType = isset($asaas_billing_type) ? (string)$asaas_billing_type : '';

$asaasSandboxBaseUrl = isset($asaas_sandbox_base_url) ? (string)$asaas_sandbox_base_url : 'https://sandbox.asaas.com/api/v3';
$asaasSandboxApiKey = isset($asaas_sandbox_api_key) ? (string)$asaas_sandbox_api_key : '';
$asaasSandboxWebhookSecret = isset($asaas_sandbox_webhook_secret) ? (string)$asaas_sandbox_webhook_secret : '';

$asaasProdBaseUrl = isset($asaas_prod_base_url) ? (string)$asaas_prod_base_url : 'https://www.asaas.com/api/v3';
$asaasProdApiKey = isset($asaas_prod_api_key) ? (string)$asaas_prod_api_key : '';
$asaasProdWebhookSecret = isset($asaas_prod_webhook_secret) ? (string)$asaas_prod_webhook_secret : '';

$mpEnv = isset($mp_env) && (string)$mp_env !== '' ? (string)$mp_env : 'sandbox';
$mpPayerEmail = isset($mp_payer_email_default) ? (string)$mp_payer_email_default : '';

$mpSandboxBaseUrl = isset($mp_sandbox_base_url) ? (string)$mp_sandbox_base_url : 'https://api.mercadopago.com';
$mpSandboxAccessToken = isset($mp_sandbox_access_token) ? (string)$mp_sandbox_access_token : '';
$mpSandboxWebhookSecret = isset($mp_sandbox_webhook_secret) ? (string)$mp_sandbox_webhook_secret : '';

$mpProdBaseUrl = isset($mp_prod_base_url) ? (string)$mp_prod_base_url : 'https://api.mercadopago.com';
$mpProdAccessToken = isset($mp_prod_access_token) ? (string)$mp_prod_access_token : '';
$mpProdWebhookSecret = isset($mp_prod_webhook_secret) ? (string)$mp_prod_webhook_secret : '';

$baseUrl = getenv('APP_BASE_URL') ?: (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
);
$baseUrl = rtrim((string)$baseUrl, '/');
$webhookAsaasUrl = $baseUrl . '/webhooks/asaas';
$webhookMpUrl = $baseUrl . '/webhooks/mercadopago';

$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

ob_start();
?>

<style>
.env-toggle{display:inline-flex;border-radius:8px;overflow:hidden;border:1px solid rgba(17,24,39,.10);margin-bottom:14px}
.env-toggle label{padding:8px 18px;font-size:12px;font-weight:700;cursor:pointer;transition:all 150ms;color:rgba(31,41,55,.55);background:rgba(0,0,0,.02)}
.env-toggle input{display:none}
.env-toggle input:checked+label{background:rgba(99,102,241,.10);color:rgba(99,102,241,.9);box-shadow:inset 0 -2px 0 rgba(99,102,241,.5)}
.env-toggle .env-sandbox input:checked+label{background:rgba(245,158,11,.10);color:rgba(180,110,0,.9);box-shadow:inset 0 -2px 0 rgba(245,158,11,.5)}
.env-panel{display:none}
.env-panel.active{display:block}
.env-badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:800;margin-left:8px}
.env-badge--sandbox{background:rgba(245,158,11,.12);color:rgba(180,110,0,.9)}
.env-badge--prod{background:rgba(34,197,94,.10);color:rgba(22,130,62,.9)}
.evt-table{width:100%;border-collapse:collapse;font-size:12px;margin-top:8px}
.evt-table th{text-align:left;padding:6px 10px;border-bottom:1px solid rgba(17,24,39,.10);color:rgba(31,41,55,.50);font-weight:600;font-size:11px}
.evt-table td{padding:6px 10px;border-bottom:1px solid rgba(17,24,39,.04);color:rgba(31,41,55,.80)}
.evt-table code{font-size:11px;padding:2px 6px;border-radius:4px;background:rgba(99,102,241,.06);color:rgba(99,102,241,.8)}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Configurações de Assinatura</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Configure os gateways de pagamento, ambientes e webhooks.</div>
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
            <code style="padding:6px 10px;border-radius:8px;background:rgba(255,255,255,.70);border:1px solid rgba(17,24,39,.08);font-size:12px;user-select:all;word-break:break-all;"><?= $e($webhookAsaasUrl) ?></code>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-weight:700;font-size:12px;color:rgba(31,41,55,.60);min-width:120px;">Mercado Pago:</span>
            <code style="padding:6px 10px;border-radius:8px;background:rgba(255,255,255,.70);border:1px solid rgba(17,24,39,.08);font-size:12px;user-select:all;word-break:break-all;"><?= $e($webhookMpUrl) ?></code>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-weight:700;font-size:12px;color:rgba(31,41,55,.60);min-width:120px;">Marketing WA:</span>
            <code style="padding:6px 10px;border-radius:8px;background:rgba(255,255,255,.70);border:1px solid rgba(17,24,39,.08);font-size:12px;user-select:all;word-break:break-all;"><?= $e($baseUrl . '/webhooks/marketing/whatsapp') ?></code>
        </div>
    </div>
</div>

<!-- ═══════════════════ ASAAS ═══════════════════ -->
<div class="lc-card lc-card--soft" style="margin-bottom:16px;">
    <div class="lc-card__header">
        <div class="lc-card__title">
            Asaas
            <span class="env-badge <?= $asaasEnv === 'production' ? 'env-badge--prod' : 'env-badge--sandbox' ?>">
                <?= $asaasEnv === 'production' ? '● Produção' : '● Sandbox' ?>
            </span>
        </div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/billing" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />

            <!-- Ambiente -->
            <div style="margin-bottom:14px;">
                <label class="lc-label" style="margin-bottom:6px;">Ambiente ativo</label>
                <div class="env-toggle">
                    <span class="env-sandbox">
                        <input type="radio" name="asaas_env" value="sandbox" id="asaas_env_sandbox" <?= $asaasEnv !== 'production' ? 'checked' : '' ?> onchange="toggleAsaasEnv()">
                        <label for="asaas_env_sandbox">🧪 Sandbox</label>
                    </span>
                    <span>
                        <input type="radio" name="asaas_env" value="production" id="asaas_env_prod" <?= $asaasEnv === 'production' ? 'checked' : '' ?> onchange="toggleAsaasEnv()">
                        <label for="asaas_env_prod">🚀 Produção</label>
                    </span>
                </div>
            </div>

            <div class="lc-field" style="margin-bottom:14px;">
                <label class="lc-label">Tipo de cobrança</label>
                <input class="lc-input" type="text" name="asaas_billing_type" value="<?= $e($asaasBillingType) ?>" placeholder="BOLETO" />
                <div class="lc-muted" style="margin-top:4px;">Ex.: BOLETO, CREDIT_CARD, PIX</div>
            </div>

            <!-- Sandbox fields -->
            <div id="asaas_sandbox_panel" class="env-panel <?= $asaasEnv !== 'production' ? 'active' : '' ?>">
                <div style="padding:14px;border-radius:12px;border:1px solid rgba(245,158,11,.18);background:rgba(245,158,11,.04);margin-bottom:12px;">
                    <div style="font-weight:700;font-size:13px;color:rgba(180,110,0,.9);margin-bottom:10px;">🧪 Credenciais Sandbox</div>
                    <div class="lc-grid lc-grid--2 lc-gap-grid">
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Base URL (Sandbox)</label>
                            <input class="lc-input" type="text" name="asaas_sandbox_base_url" value="<?= $e($asaasSandboxBaseUrl) ?>" placeholder="https://sandbox.asaas.com/api/v3" />
                        </div>
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">API Key (Sandbox)</label>
                            <input class="lc-input" type="password" name="asaas_sandbox_api_key" value="<?= $e($asaasSandboxApiKey) ?>" />
                        </div>
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Segredo do Webhook (Sandbox)</label>
                            <input class="lc-input" type="password" name="asaas_sandbox_webhook_secret" value="<?= $e($asaasSandboxWebhookSecret) ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Production fields -->
            <div id="asaas_prod_panel" class="env-panel <?= $asaasEnv === 'production' ? 'active' : '' ?>">
                <div style="padding:14px;border-radius:12px;border:1px solid rgba(34,197,94,.18);background:rgba(34,197,94,.04);margin-bottom:12px;">
                    <div style="font-weight:700;font-size:13px;color:rgba(22,130,62,.9);margin-bottom:10px;">🚀 Credenciais Produção</div>
                    <div class="lc-grid lc-grid--2 lc-gap-grid">
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Base URL (Produção)</label>
                            <input class="lc-input" type="text" name="asaas_prod_base_url" value="<?= $e($asaasProdBaseUrl) ?>" placeholder="https://www.asaas.com/api/v3" />
                        </div>
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">API Key (Produção)</label>
                            <input class="lc-input" type="password" name="asaas_prod_api_key" value="<?= $e($asaasProdApiKey) ?>" />
                        </div>
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Segredo do Webhook (Produção)</label>
                            <input class="lc-input" type="password" name="asaas_prod_webhook_secret" value="<?= $e($asaasProdWebhookSecret) ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar Asaas</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════ MERCADO PAGO ═══════════════════ -->
<div class="lc-card lc-card--soft" style="margin-bottom:16px;">
    <div class="lc-card__header">
        <div class="lc-card__title">
            Mercado Pago
            <span class="env-badge <?= $mpEnv === 'production' ? 'env-badge--prod' : 'env-badge--sandbox' ?>">
                <?= $mpEnv === 'production' ? '● Produção' : '● Sandbox' ?>
            </span>
        </div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/billing" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />

            <!-- Ambiente -->
            <div style="margin-bottom:14px;">
                <label class="lc-label" style="margin-bottom:6px;">Ambiente ativo</label>
                <div class="env-toggle">
                    <span class="env-sandbox">
                        <input type="radio" name="mp_env" value="sandbox" id="mp_env_sandbox" <?= $mpEnv !== 'production' ? 'checked' : '' ?> onchange="toggleMpEnv()">
                        <label for="mp_env_sandbox">🧪 Sandbox</label>
                    </span>
                    <span>
                        <input type="radio" name="mp_env" value="production" id="mp_env_prod" <?= $mpEnv === 'production' ? 'checked' : '' ?> onchange="toggleMpEnv()">
                        <label for="mp_env_prod">🚀 Produção</label>
                    </span>
                </div>
            </div>

            <div class="lc-field" style="margin-bottom:14px;">
                <label class="lc-label">E-mail do pagador (padrão)</label>
                <input class="lc-input" type="email" name="mp_payer_email_default" value="<?= $e($mpPayerEmail) ?>" />
                <div class="lc-muted" style="margin-top:4px;">Usado para criar a assinatura (preapproval) no Mercado Pago.</div>
            </div>

            <!-- Sandbox fields -->
            <div id="mp_sandbox_panel" class="env-panel <?= $mpEnv !== 'production' ? 'active' : '' ?>">
                <div style="padding:14px;border-radius:12px;border:1px solid rgba(245,158,11,.18);background:rgba(245,158,11,.04);margin-bottom:12px;">
                    <div style="font-weight:700;font-size:13px;color:rgba(180,110,0,.9);margin-bottom:10px;">🧪 Credenciais Sandbox</div>
                    <div class="lc-grid lc-grid--2 lc-gap-grid">
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Base URL (Sandbox)</label>
                            <input class="lc-input" type="text" name="mp_sandbox_base_url" value="<?= $e($mpSandboxBaseUrl) ?>" placeholder="https://api.mercadopago.com" />
                        </div>
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Access Token (Sandbox)</label>
                            <input class="lc-input" type="password" name="mp_sandbox_access_token" value="<?= $e($mpSandboxAccessToken) ?>" />
                        </div>
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Segredo do Webhook (Sandbox)</label>
                            <input class="lc-input" type="password" name="mp_sandbox_webhook_secret" value="<?= $e($mpSandboxWebhookSecret) ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Production fields -->
            <div id="mp_prod_panel" class="env-panel <?= $mpEnv === 'production' ? 'active' : '' ?>">
                <div style="padding:14px;border-radius:12px;border:1px solid rgba(34,197,94,.18);background:rgba(34,197,94,.04);margin-bottom:12px;">
                    <div style="font-weight:700;font-size:13px;color:rgba(22,130,62,.9);margin-bottom:10px;">🚀 Credenciais Produção</div>
                    <div class="lc-grid lc-grid--2 lc-gap-grid">
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Base URL (Produção)</label>
                            <input class="lc-input" type="text" name="mp_prod_base_url" value="<?= $e($mpProdBaseUrl) ?>" placeholder="https://api.mercadopago.com" />
                        </div>
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Access Token (Produção)</label>
                            <input class="lc-input" type="password" name="mp_prod_access_token" value="<?= $e($mpProdAccessToken) ?>" />
                        </div>
                        <div class="lc-field" style="grid-column:1/-1;">
                            <label class="lc-label">Segredo do Webhook (Produção)</label>
                            <input class="lc-input" type="password" name="mp_prod_webhook_secret" value="<?= $e($mpProdWebhookSecret) ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar Mercado Pago</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════ EVENTOS DE WEBHOOK ═══════════════════ -->
<div class="lc-card lc-card--soft" style="margin-bottom:16px;">
    <div class="lc-card__header">
        <div class="lc-card__title">Eventos de Webhook necessários</div>
    </div>
    <div class="lc-card__body">
        <div style="font-size:12px;color:rgba(31,41,55,.55);margin-bottom:14px;line-height:1.6;">
            Ao configurar os webhooks nos painéis dos gateways, selecione os eventos listados abaixo. O sistema só processa esses eventos — os demais serão ignorados.
        </div>

        <!-- Asaas Events -->
        <div style="margin-bottom:18px;">
            <div style="font-weight:750;font-size:13px;color:rgba(31,41,55,.85);margin-bottom:6px;">Asaas</div>
            <div style="font-size:11px;color:rgba(31,41,55,.45);margin-bottom:6px;">
                No painel do Asaas: Configurações → Integrações → Webhooks → Novo webhook → cole a URL acima e marque os eventos:
            </div>
            <table class="evt-table">
                <thead>
                    <tr><th>Evento</th><th>O que faz no sistema</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>PAYMENT_CONFIRMED</code></td>
                        <td>Ativa a assinatura da clínica / confirma upgrade de plano</td>
                    </tr>
                    <tr>
                        <td><code>PAYMENT_RECEIVED</code></td>
                        <td>Ativa a assinatura da clínica / confirma upgrade de plano</td>
                    </tr>
                    <tr>
                        <td><code>PAYMENT_OVERDUE</code></td>
                        <td>Marca a assinatura como inadimplente (past_due)</td>
                    </tr>
                    <tr>
                        <td><code>SUBSCRIPTION_DELETED</code></td>
                        <td>Cancela a assinatura da clínica</td>
                    </tr>
                    <tr>
                        <td><code>SUBSCRIPTION_INACTIVATED</code></td>
                        <td>Cancela a assinatura da clínica</td>
                    </tr>
                </tbody>
            </table>
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:6px;">
                Autenticação: no campo "Token de autenticação" do webhook, use o mesmo valor do campo "Segredo do Webhook" configurado acima. O sistema valida via header <code>x-webhook-secret</code>.
            </div>
        </div>

        <!-- Mercado Pago Events -->
        <div>
            <div style="font-weight:750;font-size:13px;color:rgba(31,41,55,.85);margin-bottom:6px;">Mercado Pago</div>
            <div style="font-size:11px;color:rgba(31,41,55,.45);margin-bottom:6px;">
                No painel do Mercado Pago: Sua aplicação → Webhooks → Configurar notificações → cole a URL acima e selecione os tópicos:
            </div>
            <table class="evt-table">
                <thead>
                    <tr><th>Tópico / Evento</th><th>O que faz no sistema</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>preapproval</code> (Assinaturas)</td>
                        <td>Detecta mudanças de status da assinatura (ativa, cancelada, pausada)</td>
                    </tr>
                    <tr>
                        <td><code>payment</code> (Pagamentos)</td>
                        <td>Detecta pagamentos aprovados, rejeitados ou falhados</td>
                    </tr>
                </tbody>
            </table>
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:8px;">
                <div style="margin-bottom:4px;">Detalhamento dos status processados:</div>
                <div style="padding-left:12px;">
                    • <code>preapproval</code> + authorized/active → ativa assinatura<br>
                    • <code>preapproval</code> + cancelled/paused → cancela assinatura<br>
                    • <code>payment</code> + approved/paid → ativa assinatura<br>
                    • <code>payment</code> + failed/rejected → marca como inadimplente
                </div>
            </div>
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:6px;">
                Autenticação: o Mercado Pago envia headers <code>x-signature</code> e <code>x-request-id</code>. O sistema valida usando HMAC-SHA256 com o "Segredo do Webhook" configurado acima.
            </div>
        </div>
    </div>
</div>

<script>
function toggleAsaasEnv() {
    const isSandbox = document.getElementById('asaas_env_sandbox').checked;
    document.getElementById('asaas_sandbox_panel').classList.toggle('active', isSandbox);
    document.getElementById('asaas_prod_panel').classList.toggle('active', !isSandbox);
}
function toggleMpEnv() {
    const isSandbox = document.getElementById('mp_env_sandbox').checked;
    document.getElementById('mp_sandbox_panel').classList.toggle('active', isSandbox);
    document.getElementById('mp_prod_panel').classList.toggle('active', !isSandbox);
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
