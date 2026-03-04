<?php
$title = 'Confirmação de consulta';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$token = $token ?? '';
$clinicName = $clinic_name ?? '';
$startDate = $start_date ?? '';
$startTime = $start_time ?? '';
$appointment = $appointment ?? null;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" href="/icone_1.png" />
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body lc-body--auth">
<div class="lc-auth lc-auth--compact">
    <div class="lc-auth__panel lc-auth__panel--compact">
        <div class="lc-auth__brand">
            <div class="lc-brand__logo" style="padding:0; background:#000;">
                <img src="/icone_1.png" alt="LumiClinic" style="width:100%; height:100%; object-fit:contain; border-radius:12px; display:block;" />
            </div>
            <div>
                <div class="lc-auth__title">Confirmação</div>
                <div class="lc-auth__subtitle" style="line-height:0; margin-top:6px;">
                    <span style="display:block; font-weight:900; font-size:16px; letter-spacing:0.2px; line-height:1;">LumiClinic</span>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!$error && !$success && is_array($appointment)) : ?>
            <div class="lc-card" style="margin: 12px 0; box-shadow:none;">
                <div class="lc-card__body">
                    <div style="font-weight:800; margin-bottom:6px;">Sua consulta</div>
                    <?php if (trim((string)$clinicName) !== ''): ?>
                        <div class="lc-muted" style="margin-bottom:6px;">Clínica: <?= htmlspecialchars((string)$clinicName, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div><strong>Data:</strong> <?= htmlspecialchars((string)$startDate, ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Hora:</strong> <?= htmlspecialchars((string)$startTime, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="lc-muted" style="margin-bottom:10px;">Escolha uma opção:</div>

            <form method="post" class="lc-form" action="/a/confirm">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="token" value="<?= htmlspecialchars((string)$token, ENT_QUOTES, 'UTF-8') ?>" />

                <button class="lc-btn lc-btn--primary" type="submit" name="action" value="confirm" style="width:100%; justify-content:center;">Confirmar consulta</button>
                <button class="lc-btn lc-btn--danger" type="submit" name="action" value="cancel" style="width:100%; justify-content:center; margin-top:10px;" onclick="return confirm('Deseja cancelar esta consulta?');">Cancelar consulta</button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 12px;" class="lc-muted">Você pode fechar esta página após concluir.</div>
    </div>
</div>
</body>
</html>
