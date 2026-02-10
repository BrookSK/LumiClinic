<?php
// variables expected:
// $title (string)
// $portal_active (string|null)
// $portal_content (string)

$csrf = $_SESSION['_csrf'] ?? '';
$title = isset($title) ? (string)$title : 'Portal do Paciente';
$portal_active = $portal_active ?? null;
$portal_content = $portal_content ?? '';

$items = [
    ['key' => 'dashboard', 'label' => 'Início', 'href' => '/portal'],
    ['key' => 'agenda', 'label' => 'Agenda', 'href' => '/portal/agenda'],
    ['key' => 'documentos', 'label' => 'Documentos', 'href' => '/portal/documentos'],
    ['key' => 'uploads', 'label' => 'Enviar fotos', 'href' => '/portal/uploads'],
    ['key' => 'notificacoes', 'label' => 'Notificações', 'href' => '/portal/notificacoes'],
    ['key' => 'lgpd', 'label' => 'LGPD', 'href' => '/portal/lgpd'],
    ['key' => 'perfil', 'label' => 'Perfil', 'href' => '/portal/perfil'],
    ['key' => 'seguranca', 'label' => 'Segurança', 'href' => '/portal/seguranca'],
];
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
<body class="lc-body">
<div class="lc-app" style="padding: 16px; max-width: 980px; margin: 0 auto;">
    <div class="lc-page__header" style="gap:10px;">
        <div class="lc-flex lc-gap-sm" style="align-items:center;">
            <div class="lc-brand__logo" style="width:36px; height:36px; padding:0; background:#000; border-radius:10px; overflow:hidden;">
                <img src="/icone_1.png" alt="LumiClinic" style="width:100%; height:100%; object-fit:contain; display:block;" />
            </div>
            <div>
                <div class="lc-page__title" style="margin:0;">Portal do Paciente</div>
                <div class="lc-page__subtitle" style="margin-top:2px;"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <?php foreach ($items as $it): ?>
                <?php
                $isActive = is_string($portal_active) && $portal_active !== '' && $portal_active === (string)$it['key'];
                $cls = $isActive ? 'lc-btn lc-btn--primary' : 'lc-btn lc-btn--secondary';
                ?>
                <a class="<?= $cls ?>" href="<?= htmlspecialchars((string)$it['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string)$it['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>

            <form method="post" action="/portal/logout">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
            </form>
        </div>
    </div>

    <?= (string)$portal_content ?>
</div>
</body>
</html>
