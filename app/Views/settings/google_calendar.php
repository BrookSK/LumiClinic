<?php
$title = 'Configurações - Google Calendar';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$connected = isset($connected) ? (bool)$connected : false;
$calendarId = isset($calendar_id) ? (string)$calendar_id : 'primary';
$clientReady = isset($client_ready) ? (bool)$client_ready : false;
$libReady = isset($lib_ready) ? (bool)$lib_ready : false;

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $p = $_SESSION['permissions'] ?? [];
    if (!is_array($p)) return false;
    if (isset($p['allow'],$p['deny'])&&is_array($p['allow'])&&is_array($p['deny'])) {
        if (in_array($permissionCode,$p['deny'],true)) return false;
        return in_array($permissionCode,$p['allow'],true);
    }
    return in_array($permissionCode,$p,true);
};
ob_start();
?>

<a href="/settings" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para configurações
</a>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <span style="font-size:24px;">📅</span>
        <div>
            <div style="font-weight:850;font-size:18px;">Google Calendar</div>
            <div style="font-size:13px;color:rgba(31,41,55,.50);">Sincronize a agenda da clínica com o Google Calendar automaticamente.</div>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid <?= $connected ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;background:<?= $connected ? 'rgba(22,163,74,.06)' : 'rgba(107,114,128,.04)' ?>;margin-bottom:16px;">
        <span style="font-size:16px;"><?= $connected ? '✅' : '⚠️' ?></span>
        <span style="font-weight:700;font-size:13px;color:<?= $connected ? '#16a34a' : '#6b7280' ?>;"><?= $connected ? 'Conectado' : 'Não conectado' ?></span>
    </div>

    <?php if (!$clientReady || !$libReady): ?>
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.03);margin-bottom:16px;">
        <div style="font-weight:750;font-size:14px;color:rgba(185,28,28,.80);margin-bottom:10px;">⚠️ Configuração pendente</div>

        <?php if (!$clientReady): ?>
        <div style="padding:14px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.12);margin-bottom:12px;">
            <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.80);margin-bottom:8px;">Google OAuth não configurado</div>
            <div style="font-size:13px;color:rgba(31,41,55,.60);line-height:1.6;">
                Para usar o Google Calendar, o administrador do sistema precisa configurar as credenciais OAuth do Google. Siga os passos:
            </div>
            <div style="margin-top:10px;padding:12px;border-radius:10px;background:rgba(255,255,255,.60);font-size:13px;color:rgba(31,41,55,.70);line-height:1.8;">
                <div style="font-weight:700;margin-bottom:6px;">Passo a passo:</div>
                1. Acesse o <a href="https://console.cloud.google.com/" target="_blank" rel="noopener" style="color:rgba(129,89,1,1);font-weight:600;">Google Cloud Console</a><br>
                2. Crie um projeto (ou use um existente)<br>
                3. Vá em "APIs e Serviços" → "Credenciais"<br>
                4. Clique em "Criar credenciais" → "ID do cliente OAuth"<br>
                5. Tipo: "Aplicativo da Web"<br>
                6. Em "URIs de redirecionamento autorizados", adicione:<br>
                <code style="display:inline-block;margin-top:4px;padding:4px 8px;border-radius:6px;background:rgba(0,0,0,.04);font-size:12px;"><?= htmlspecialchars(rtrim((string)(getenv('APP_BASE_URL') ?: ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'seudominio.com')), '/'), ENT_QUOTES, 'UTF-8') ?>/settings/google-calendar/callback</code><br>
                7. Copie o Client ID e Client Secret gerados<br>
                8. Acesse o painel de super admin: <a href="/sys/settings/google-oauth" style="color:rgba(129,89,1,1);font-weight:600;">Configurações → Google OAuth</a><br>
                9. Cole o Client ID e Client Secret e salve
            </div>
            <?php
            $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
            ?>
            <?php if ($isSuperAdmin): ?>
            <div style="margin-top:12px;">
                <a class="lc-btn lc-btn--primary lc-btn--sm" href="/sys/settings/google-oauth">Configurar Google OAuth agora</a>
            </div>
            <?php else: ?>
            <div style="margin-top:10px;font-size:12px;color:rgba(31,41,55,.50);">
                Peça ao administrador do sistema para configurar isso em: Painel Admin → Configurações → Google OAuth
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!$libReady): ?>
        <div style="padding:14px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.12);">
            <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.80);margin-bottom:6px;">Dependência ausente</div>
            <div style="font-size:13px;color:rgba(31,41,55,.60);line-height:1.6;">
                A biblioteca <code>google/apiclient</code> não está instalada. O administrador do servidor precisa executar:
            </div>
            <code style="display:block;margin-top:8px;padding:8px 12px;border-radius:8px;background:rgba(0,0,0,.04);font-size:12px;">composer require google/apiclient</code>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div style="font-size:13px;color:rgba(31,41,55,.55);line-height:1.6;margin-bottom:16px;">
        Ao conectar, consultas criadas, editadas ou canceladas no sistema são sincronizadas automaticamente com o Google Calendar.
    </div>

    <?php if ($can('settings.update')): ?>
    <form method="post" action="/settings/google-calendar/connect">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <div class="lc-field" style="max-width:400px;">
            <label class="lc-label">Calendar ID</label>
            <input class="lc-input" type="text" name="calendar_id" value="<?= htmlspecialchars($calendarId, ENT_QUOTES, 'UTF-8') ?>" placeholder="primary" autocomplete="off" />
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Deixe "primary" para o calendário principal. Só mude se souber o que está fazendo.</div>
        </div>
        <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit" <?= (!$clientReady || !$libReady) ? 'disabled' : '' ?>>Conectar Google Calendar</button>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/google-calendar/logs">Ver logs</a>
        </div>
    </form>

    <?php if ($connected): ?>
    <details style="margin-top:14px;">
        <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;">Desconectar</summary>
        <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
            <div style="font-size:12px;color:rgba(185,28,28,.70);margin-bottom:8px;">Isso vai parar a sincronização.</div>
            <form method="post" action="/settings/google-calendar/disconnect" style="margin:0;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit" onclick="return confirm('Desconectar?');">Confirmar</button>
            </form>
        </div>
    </details>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
