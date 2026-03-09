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
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Google Calendar</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="lc-card__body">
        <?php if (!$clientReady): ?>
            <div class="lc-alert lc-alert--info" style="margin-bottom:12px;">
                Google OAuth ainda não foi configurado no sistema (client_id/client_secret).
            </div>
        <?php endif; ?>

        <?php if (!$libReady): ?>
            <div class="lc-alert lc-alert--info" style="margin-bottom:12px;">
                Dependência ausente: instale <code>google/apiclient</code> via Composer.
            </div>
        <?php endif; ?>

        <label class="lc-label">Status</label>
        <div class="lc-badge <?= $connected ? 'lc-badge--success' : 'lc-badge--secondary' ?>">
            <?= $connected ? 'Conectado' : 'Não conectado' ?>
        </div>

        <div class="lc-muted" style="margin-top:10px;">
            Ao conectar, o sistema sincroniza automaticamente seus agendamentos (criar/atualizar/cancelar).
        </div>

        <?php if ($can('settings.update')): ?>
            <form method="post" class="lc-form" action="/settings/google-calendar/connect" style="margin-top:14px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Calendar ID</label>
            <input class="lc-input" type="text" name="calendar_id" value="<?= htmlspecialchars($calendarId, ENT_QUOTES, 'UTF-8') ?>" placeholder="primary" autocomplete="off" />
            <div class="lc-muted" style="margin-top:6px;">Deixe <code>primary</code> para o padrão. Opcional.</div>

                <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                    <button class="lc-btn lc-btn--primary" type="submit" <?= (!$clientReady || !$libReady) ? 'disabled' : '' ?>>Conectar Google Calendar</button>
                    <a class="lc-btn lc-btn--secondary" href="/settings/google-calendar/logs">Ver logs</a>
                    <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
                </div>
            </form>

            <form method="post" class="lc-form" action="/settings/google-calendar/disconnect" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--danger" type="submit" onclick="return confirm('Desconectar Google Calendar?');" <?= $connected ? '' : 'disabled' ?>>Desconectar</button>
            </form>
        <?php else: ?>
            <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
