<?php
$title = 'Admin - Google OAuth';
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

<a href="/sys/settings/billing" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar
</a>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <span style="font-size:24px;">🔑</span>
        <div>
            <div style="font-weight:850;font-size:18px;">Google OAuth</div>
            <div style="font-size:13px;color:rgba(31,41,55,.50);">Credenciais para integração com Google Calendar.</div>
        </div>
    </div>

    <!-- Status -->
    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid <?= ($clientId !== '' && $clientSecretSet) ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;background:<?= ($clientId !== '' && $clientSecretSet) ? 'rgba(22,163,74,.06)' : 'rgba(107,114,128,.04)' ?>;margin-bottom:16px;">
        <span style="font-size:16px;"><?= ($clientId !== '' && $clientSecretSet) ? '✅' : '⚠️' ?></span>
        <span style="font-weight:700;font-size:13px;color:<?= ($clientId !== '' && $clientSecretSet) ? '#16a34a' : '#6b7280' ?>;"><?= ($clientId !== '' && $clientSecretSet) ? 'Configurado' : 'Não configurado' ?></span>
    </div>

    <!-- Passo a passo -->
    <div style="padding:16px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.10);margin-bottom:16px;">
        <div style="font-weight:700;font-size:13px;color:rgba(129,89,1,1);margin-bottom:8px;">Como obter as credenciais</div>
        <div style="font-size:13px;color:rgba(31,41,55,.65);line-height:1.8;">
            1. Acesse o <a href="https://console.cloud.google.com/" target="_blank" rel="noopener" style="color:rgba(129,89,1,1);font-weight:600;">Google Cloud Console</a><br>
            2. Crie um projeto ou selecione um existente<br>
            3. Ative a API "Google Calendar API" em "APIs e Serviços" → "Biblioteca"<br>
            4. Vá em "APIs e Serviços" → "Credenciais"<br>
            5. Clique em "Criar credenciais" → "ID do cliente OAuth"<br>
            6. Tipo de aplicativo: "Aplicativo da Web"<br>
            7. Em "URIs de redirecionamento autorizados", adicione a URL abaixo<br>
            8. Copie o Client ID e Client Secret e cole nos campos abaixo
        </div>
    </div>

    <!-- Redirect URI -->
    <div style="padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.10);background:rgba(0,0,0,.02);margin-bottom:16px;">
        <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.80);margin-bottom:6px;">URI de redirecionamento (copie e cole no Google Cloud Console)</div>
        <code style="display:block;padding:10px 12px;border-radius:8px;background:rgba(255,255,255,.80);border:1px solid rgba(17,24,39,.08);font-size:13px;word-break:break-all;user-select:all;"><?= htmlspecialchars($redirectUri, ENT_QUOTES, 'UTF-8') ?></code>
    </div>

    <!-- Formulário -->
    <form method="post" action="/sys/settings/google-oauth" class="lc-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-field">
            <label class="lc-label">Client ID</label>
            <input class="lc-input" type="text" name="google_oauth_client_id" value="<?= htmlspecialchars($clientId, ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" placeholder="Ex: 123456789-abc.apps.googleusercontent.com" />
        </div>

        <div class="lc-field">
            <label class="lc-label">Client Secret</label>
            <input class="lc-input" type="password" name="google_oauth_client_secret" placeholder="<?= $clientSecretSet ? 'Já configurado (deixe vazio para manter)' : 'Cole o Client Secret aqui' ?>" autocomplete="off" />
            <?php if ($clientSecretSet): ?>
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Já configurado. Deixe vazio para manter o atual, ou preencha para substituir.</div>
            <?php endif; ?>
        </div>

        <div style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar credenciais</button>
        </div>
    </form>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
