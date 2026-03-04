<?php
$title = 'Configurações - WhatsApp';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$diagnose = $diagnose ?? null;
$zapiInstanceId = $zapi_instance_id ?? null;
$zapiTokenSet = $zapi_token_set ?? false;
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">WhatsApp (Z-API)</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="lc-card__body">
        <div class="lc-muted" style="margin-bottom:10px;">
            O token é salvo criptografado por clínica. Não exibimos o valor após salvar.
        </div>

        <form method="post" class="lc-form" action="/settings/whatsapp">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Status</label>
            <div class="lc-badge <?= ($zapiInstanceId !== null && $zapiInstanceId !== '' && $zapiTokenSet) ? 'lc-badge--success' : 'lc-badge--secondary' ?>">
                <?= ($zapiInstanceId !== null && $zapiInstanceId !== '' && $zapiTokenSet) ? 'Configurado' : 'Não configurado' ?>
            </div>

            <label class="lc-label" style="margin-top:12px;">Z-API instance id</label>
            <input class="lc-input" type="text" name="zapi_instance_id" value="<?= htmlspecialchars((string)($zapiInstanceId ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="YOUR_INSTANCE" autocomplete="off" />

            <label class="lc-label" style="margin-top:12px;">Z-API token</label>
            <input class="lc-input" type="password" name="zapi_token" placeholder="YOUR_TOKEN" autocomplete="off" />

            <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
            </div>
        </form>

        <form method="post" class="lc-form" action="/settings/whatsapp/test" style="margin-top:10px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary" type="submit">Testar conexão</button>
        </form>

        <form method="post" class="lc-form" action="/settings/whatsapp/diagnose" style="margin-top:10px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary" type="submit">Resolver problemas do WhatsApp</button>
        </form>

        <form method="post" class="lc-form" action="/settings/whatsapp/clear" style="margin-top:10px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--danger" type="submit" onclick="return confirm('Remover a configuração de WhatsApp desta clínica?');">Remover configuração</button>
        </form>

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
