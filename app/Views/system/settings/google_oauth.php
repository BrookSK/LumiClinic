<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';
$success = $success ?? null;
$error = isset($_GET['error']) ? (string)$_GET['error'] : '';

$clientId = isset($google_oauth_client_id) ? (string)$google_oauth_client_id : '';
$clientSecretSet = isset($google_oauth_client_secret_set) ? (bool)$google_oauth_client_secret_set : false;

$baseUrl = getenv('APP_BASE_URL') ?: (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
);
$baseUrl = rtrim((string)$baseUrl, '/');
$redirectUri = $baseUrl . '/settings/google-calendar/callback';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Google OAuth</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/billing">Billing</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/webpush">WebPush</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header">
        <div class="lc-card__title">Credenciais (OAuth Client)</div>
    </div>
    <div class="lc-card__body">
        <div class="lc-alert lc-alert--info" style="margin-bottom:12px;">
            <div style="font-weight:700; margin-bottom:4px;">Redirect URI</div>
            <div>Cadastre no Google Cloud Console:</div>
            <div style="margin-top:6px;"><code><?= htmlspecialchars($redirectUri, ENT_QUOTES, 'UTF-8') ?></code></div>
        </div>

        <form method="post" action="/sys/settings/google-oauth" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Client ID</label>
            <input class="lc-input" type="text" name="google_oauth_client_id" value="<?= htmlspecialchars($clientId, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" />

            <label class="lc-label" style="margin-top:12px;">Client Secret</label>
            <input class="lc-input" type="password" name="google_oauth_client_secret" placeholder="<?= $clientSecretSet ? 'Já configurado (redefinir)' : 'Informar' ?>" autocomplete="off" />

            <div class="lc-flex lc-flex--end lc-gap-sm" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
