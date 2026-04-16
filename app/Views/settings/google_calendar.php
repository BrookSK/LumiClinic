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
    <?php $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1; ?>

    <?php if ($isSuperAdmin): ?>
    <!-- Super admin: mostra detalhes técnicos -->
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.03);margin-bottom:16px;">
        <div style="font-weight:750;font-size:14px;color:rgba(185,28,28,.80);margin-bottom:10px;">⚠️ Configuração pendente</div>

        <?php if (!$clientReady): ?>
        <div style="padding:14px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.12);margin-bottom:12px;">
            <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.80);margin-bottom:8px;">Google OAuth não configurado</div>
            <div style="font-size:13px;color:rgba(31,41,55,.60);line-height:1.6;margin-bottom:10px;">
                Configure as credenciais OAuth no painel de administrador para habilitar o Google Calendar para as clínicas.
            </div>
            <div style="margin-top:8px;">
                <a class="lc-btn lc-btn--primary lc-btn--sm" href="/sys/settings/google-oauth">Configurar Google OAuth</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$libReady): ?>
        <div style="padding:14px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.12);">
            <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.80);margin-bottom:6px;">Dependência ausente</div>
            <div style="font-size:13px;color:rgba(31,41,55,.60);">Execute no servidor: <code>composer require google/apiclient</code></div>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Dono da clínica / usuário normal: mensagem simples -->
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(107,114,128,.18);background:rgba(107,114,128,.04);margin-bottom:16px;text-align:center;">
        <div style="font-size:28px;margin-bottom:8px;">🔒</div>
        <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.80);margin-bottom:6px;">Google Calendar ainda não está disponível</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);line-height:1.5;max-width:400px;margin:0 auto;">
            A integração com o Google Calendar precisa ser habilitada pelo administrador do sistema. Entre em contato com o suporte para ativar essa funcionalidade.
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <div style="font-size:13px;color:rgba(31,41,55,.55);line-height:1.6;margin-bottom:16px;">
        Ao conectar, consultas criadas, editadas ou canceladas no sistema são sincronizadas automaticamente com o Google Calendar.
    </div>

    <?php if ($can('settings.update')): ?>
    <?php if ($connected): ?>
    <!-- Já conectado: mostrar desconectar -->
    <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;align-items:center;">
        <span style="font-size:13px;color:#16a34a;font-weight:600;">✅ Sincronização ativa</span>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/google-calendar/logs">Ver logs</a>
    </div>
    <details style="margin-top:14px;">
        <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;">Desconectar Google Calendar</summary>
        <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
            <div style="font-size:12px;color:rgba(185,28,28,.70);margin-bottom:8px;">Isso vai parar a sincronização. Eventos já criados no Google Calendar não serão removidos.</div>
            <form method="post" action="/settings/google-calendar/disconnect" style="margin:0;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit" onclick="return confirm('Desconectar Google Calendar?');">Confirmar desconexão</button>
            </form>
        </div>
    </details>
    <?php else: ?>
    <!-- Não conectado: mostrar formulário de conexão -->
    <div id="gcal-connect-form">
        <div class="lc-field" style="max-width:400px;">
            <label class="lc-label">Calendar ID</label>
            <input class="lc-input" type="text" id="gcal_calendar_id" value="<?= htmlspecialchars($calendarId, ENT_QUOTES, 'UTF-8') ?>" placeholder="primary" autocomplete="off" />
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Deixe "primary" para o calendário principal. Só mude se souber o que está fazendo.</div>
        </div>
        <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="button" id="gcal-connect-btn" <?= (!$clientReady || !$libReady) ? 'disabled' : '' ?> onclick="connectGcal()">Conectar Google Calendar</button>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/google-calendar/logs">Ver logs</a>
        </div>
    </div>
    <script>
    function connectGcal() {
        var calId = document.getElementById('gcal_calendar_id');
        var val = calId ? calId.value.trim() : 'primary';
        if (!val) val = 'primary';
        var btn = document.getElementById('gcal-connect-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Conectando...'; }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/settings/google-calendar/connect', true);
        xhr.setRequestHeader('X-CSRF-Token', <?= json_encode($csrf) ?>);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    var j = JSON.parse(xhr.responseText);
                    if (j && j.redirect) { window.location.href = j.redirect; return; }
                    if (j && j.error) { alert(j.error); }
                } catch(e) {}
                if (btn) { btn.disabled = false; btn.textContent = 'Conectar Google Calendar'; }
            }
        };
        var fd = new FormData();
        fd.append('_csrf', <?= json_encode($csrf) ?>);
        fd.append('calendar_id', val);
        xhr.send(fd);
    }
    </script>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
