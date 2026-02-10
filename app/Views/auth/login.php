<?php
$title = 'Login';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
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
<div class="lc-auth">
    <div class="lc-auth__panel">
        <div class="lc-auth__grid">
            <div class="lc-auth__left">
                <div class="lc-auth__content">
                    <div class="lc-auth__brand">
                        <div class="lc-brand__logo" style="padding:0; background:#000;">
                            <img src="/icone_1.png" alt="LumiClinic" style="width:100%; height:100%; object-fit:contain; border-radius:12px; display:block;" />
                        </div>
                        <div>
                            <div class="lc-auth__title" style="line-height:0;">
                                <img src="/Principal_1.png" alt="LumiClinic" style="height:28px; width:auto; display:block; object-fit:contain;" />
                            </div>
                            <div class="lc-auth__subtitle">Acesso seguro</div>
                        </div>
                    </div>

                    <div class="lc-auth__heading">Faça login na sua conta</div>

                <?php if ($error): ?>
                    <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                    <form method="post" class="lc-form" action="/login" autocomplete="off">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                        <label class="lc-label">E-mail</label>
                        <input class="lc-input" type="email" name="email" required />

                        <label class="lc-label">Senha</label>
                        <input class="lc-input" type="password" name="password" required />

                        <div class="lc-auth__metaRow">
                            <label class="lc-auth__checkbox">
                                <input type="checkbox" name="remember" value="1" />
                                Lembrar dados de acesso
                            </label>

                            <a class="lc-link" href="/forgot">Esqueci minha senha / primeiro acesso</a>
                        </div>

                        <button class="lc-btn lc-btn--primary" type="submit">Entrar</button>
                    </form>

                    <div class="lc-auth__footnote">Agenda • Pacientes • Financeiro • Estoque</div>
                </div>
            </div>

            <div class="lc-auth__right" style="border-radius: 50px;">
                <div class="lc-auth__rightInner">
                    <div class="lc-auth__promoTitle">Gestão completa da sua clínica</div>
                    <div class="lc-auth__promoText">Agenda, pacientes, prontuário, financeiro, estoque e configurações — tudo em um só lugar.</div>

                    <div class="lc-card space-card-lu" style="margin-top:16px; padding:24px;">
                        <div style="font-weight:700; font-size:14px; margin-bottom:4px;">Acesso para pacientes</div>
                        <div class="lc-muted" style="line-height:1.45; margin-bottom:12px;">
                            Para seus pacientes entrarem com e-mail e senha e acessarem documentos, agenda e envios.
                        </div>
                        <a class="lc-btn lc-btn--secondary" href="/portal" style="width:100%; justify-content:center; font-size:13px;">Entrar no Portal do Paciente</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
