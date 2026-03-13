<?php
$title = 'Configurações - WhatsApp';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$diagnose = $diagnose ?? null;
$connectData = $connect_data ?? null;
$evolutionInstance = $evolution_instance ?? null;
$evolutionApiKeySet = $evolution_apikey_set ?? false;
$globalConfigured = $global_configured ?? false;

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
    <div class="lc-card__title">WhatsApp (Evolution API)</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="lc-card__body">
        <?php if ($globalConfigured): ?>
            <div class="lc-alert lc-alert--info" style="margin-bottom:10px;">
                O WhatsApp está configurado globalmente pelo administrador do sistema.
            </div>
        <?php else: ?>
            <div class="lc-muted" style="margin-bottom:10px;">
                A API key é salva criptografada por clínica. Não exibimos o valor após salvar.
            </div>
        <?php endif; ?>

        <?php if ($can('settings.update')): ?>
            <label class="lc-label">Status</label>
            <?php
            $isConfigured = $globalConfigured || ($evolutionInstance !== null && $evolutionInstance !== '' && $evolutionApiKeySet);
            ?>
            <div class="lc-badge <?= $isConfigured ? 'lc-badge--success' : 'lc-badge--secondary' ?>">
                <?= $isConfigured ? 'Configurado' : 'Não configurado' ?>
            </div>

            <?php if ($globalConfigured): ?>
                <form method="post" action="/settings/whatsapp/connect" class="lc-form" style="margin-top:10px;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <button class="lc-btn lc-btn--primary" type="submit">Conectar (Gerar QR Code)</button>
                </form>

                <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                    <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
                </div>
            <?php else: ?>
                <form method="post" class="lc-form" action="/settings/whatsapp">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                    <label class="lc-label" style="margin-top:12px;">Instância</label>
                    <input class="lc-input" type="text" name="evolution_instance" value="<?= htmlspecialchars((string)($evolutionInstance ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="nome-da-instancia" autocomplete="off" />

                    <label class="lc-label" style="margin-top:12px;">API Key</label>
                    <input class="lc-input" type="password" name="evolution_apikey" placeholder="apikey" autocomplete="off" />

                    <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                        <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
                    </div>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
            </div>
        <?php endif; ?>

        <?php if ($can('settings.update')): ?>
            <form method="post" class="lc-form" action="/settings/whatsapp/test" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Testar conexão</button>
            </form>
        <?php endif; ?>

        <?php if (is_array($connectData)): ?>
            <?php
            $pairingCode = isset($connectData['pairingCode']) ? trim((string)$connectData['pairingCode']) : '';
            $code = '';
            if (isset($connectData['qrcode']) && is_array($connectData['qrcode'])) {
                $code = isset($connectData['qrcode']['base64']) ? trim((string)$connectData['qrcode']['base64']) : $code;
                if ($code === '') {
                    $code = isset($connectData['qrcode']['code']) ? trim((string)$connectData['qrcode']['code']) : $code;
                }
            }
            if ($code === '') {
                $code = isset($connectData['base64']) ? trim((string)$connectData['base64']) : $code;
            }
            if ($code === '') {
                $code = isset($connectData['code']) ? trim((string)$connectData['code']) : $code;
            }
            $count = isset($connectData['count']) ? (int)$connectData['count'] : null;
            $imgSrc = null;
            if ($code !== '') {
                if (stripos($code, 'data:image') === 0) {
                    $imgSrc = $code;
                } elseif (preg_match('/^[A-Za-z0-9+\/\r\n]+=*$/', $code) && strlen($code) > 120) {
                    $imgSrc = 'data:image/png;base64,' . $code;
                }
            }
            ?>
            <div class="lc-card" style="margin-top:14px;">
                <div class="lc-card__title">Conectar WhatsApp</div>
                <div class="lc-card__body">
                    <?php if ($imgSrc !== null): ?>
                        <div style="display:flex; justify-content:center;">
                            <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="QR Code" style="max-width:260px; width:100%; height:auto;" />
                        </div>
                    <?php endif; ?>

                    <?php if ($pairingCode !== ''): ?>
                        <div class="lc-muted" style="margin-top:10px;">Pairing code: <code><?= htmlspecialchars($pairingCode, ENT_QUOTES, 'UTF-8') ?></code></div>
                    <?php endif; ?>

                    <?php if ($count !== null): ?>
                        <div class="lc-muted" style="margin-top:6px;">Tentativa: <?= htmlspecialchars((string)$count, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <?php if ($imgSrc === null && $code !== ''): ?>
                        <div class="lc-muted" style="margin-top:10px;">Código retornado pela Evolution:</div>
                        <pre style="white-space:pre-wrap; word-break:break-word; margin-top:6px;"><code><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></code></pre>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($can('settings.update')): ?>
            <form method="post" class="lc-form" action="/settings/whatsapp/diagnose" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Resolver problemas do WhatsApp</button>
            </form>
        <?php endif; ?>

        <?php if ($can('settings.update')): ?>
            <form method="post" class="lc-form" action="/settings/whatsapp/clear" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <?php if (!$globalConfigured): ?>
                    <button class="lc-btn lc-btn--danger" type="submit" onclick="return confirm('Remover a configuração de WhatsApp desta clínica?');">Remover configuração</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <?php if (is_array($diagnose) && isset($diagnose['checks']) && is_array($diagnose['checks'])): ?>
            <div class="lc-card" style="margin-top:14px;">
                <div class="lc-card__title">Diagnóstico</div>
                <div class="lc-card__body">
                    <?php foreach ($diagnose['checks'] as $c): ?>
                        <?php
                        $ok = isset($c['ok']) ? (bool)$c['ok'] : false;
                        $title = (string)($c['title'] ?? '');
                        $message = (string)($c['message'] ?? '');
                        $actionLabel = $c['action_label'] ?? null;
                        $actionLabel = $actionLabel === null ? null : (string)$actionLabel;
                        $actionUrl = $c['action_url'] ?? null;
                        $actionUrl = $actionUrl === null ? null : (string)$actionUrl;
                        ?>
                        <div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="gap:10px; padding:10px 0; border-bottom:1px solid rgba(0,0,0,0.06);">
                            <div>
                                <div style="font-weight:600;">
                                    <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                                    <span class="lc-badge <?= $ok ? 'lc-badge--success' : 'lc-badge--danger' ?>" style="margin-left:8px;">
                                        <?= $ok ? 'OK' : 'Atenção' ?>
                                    </span>
                                </div>
                                <div class="lc-muted"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div>
                                <?php if ($actionLabel !== null && $actionUrl !== null): ?>
                                    <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
