<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';
$dev_alert_emails = isset($dev_alert_emails) ? (string)$dev_alert_emails : '';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Configurações (Alertas de erro)</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/mail">E-mail</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/error-logs">Logs de erros</a>
    </div>
</div>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header">
        <div class="lc-card__title">Para quem avisar quando der erro</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/dev-alerts" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">E-mails dos desenvolvedores</label>
                <textarea class="lc-textarea" name="dev_alert_emails" rows="3" placeholder="dev1@exemplo.com, dev2@exemplo.com"><?= htmlspecialchars($dev_alert_emails, ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="lc-muted" style="margin-top:6px; font-size:12px; line-height:1.45;">
                    Quando ocorrer um erro crítico (ex.: 500/503), o sistema vai mandar um e-mail automático para esses endereços.
                </div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="margin-top:12px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
