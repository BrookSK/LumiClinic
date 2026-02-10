<?php
$title = 'Portal do Paciente';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body lc-body--auth">
<div class="lc-auth">
    <div class="lc-auth__panel">
        <div class="lc-auth__grid">
            <div class="lc-auth__left">
                <div class="lc-auth__brand">
                    <div class="lc-brand__logo">LC</div>
                    <div>
                        <div class="lc-auth__title">Portal do Paciente</div>
                        <div class="lc-auth__subtitle">Acesso seguro</div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" class="lc-form" action="/portal/login" autocomplete="off">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                    <label class="lc-label">E-mail</label>
                    <input class="lc-input" type="email" name="email" required />

                    <label class="lc-label">Senha</label>
                    <input class="lc-input" type="password" name="password" required />

                    <button class="lc-btn lc-btn--primary" type="submit">Entrar</button>
                </form>

                <div style="margin-top: 10px;">
                    <a class="lc-link" href="/portal/forgot">Esqueci minha senha</a>
                </div>
            </div>

            <div class="lc-auth__right">
                <div class="lc-auth__rightInner">
                    <div class="lc-auth__promoTitle">Sua clínica na TV por apenas R$99/mês</div>
                    <div class="lc-auth__promoText">Ofereça mais conforto aos pacientes e mais eficiência no seu negócio com a chamada na TV.</div>
                    <button class="lc-btn lc-btn--primary" type="button" onclick="window.open('https://lumiclinic.onsolutionsbrasil.com.br', '_blank')">Saiba mais</button>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
