<?php
$title = 'Recuperar senha';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$reset_token = $reset_token ?? null;
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
                <div class="lc-auth__title">Recuperar senha</div>
                <div class="lc-auth__subtitle">Portal do Paciente</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" class="lc-form" action="/portal/forgot" autocomplete="off">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">E-mail</label>
            <input class="lc-input" type="email" name="email" required />

            <button class="lc-btn lc-btn--primary" type="submit">Gerar link</button>
        </form>

        <div style="margin-top: 10px;">
            <a class="lc-link" href="/portal/login">Voltar</a>
        </div>
    </div>
</div>
</body>
</html>
