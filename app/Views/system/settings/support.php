<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';

$support_whatsapp_number = isset($support_whatsapp_number) ? (string)$support_whatsapp_number : '';
$support_email = isset($support_email) ? (string)$support_email : '';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Configurações (Suporte)</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/seo">SEO</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/mail">E-mail</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header">
        <div class="lc-card__title">Contato do suporte</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/support" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">WhatsApp do suporte</label>
                <input class="lc-input" type="text" name="support_whatsapp_number" value="<?= htmlspecialchars($support_whatsapp_number, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: 5511999999999" />
                <div class="lc-muted" style="font-size:12px; margin-top:6px;">Use o número completo com DDI e DDD (somente números).</div>
            </div>

            <div class="lc-field">
                <label class="lc-label">E-mail do suporte</label>
                <input class="lc-input" type="email" name="support_email" value="<?= htmlspecialchars($support_email, ENT_QUOTES, 'UTF-8') ?>" placeholder="suporte@seudominio.com" />
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
