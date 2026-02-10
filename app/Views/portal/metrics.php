<?php
$title = 'Métricas';
$csrf = $_SESSION['_csrf'] ?? '';
$summary = $summary ?? ['portal_logins' => 0, 'appointment_confirms' => 0];
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
    <div class="lc-page__header">
        <div>
            <h1 class="lc-page__title">Métricas</h1>
            <div class="lc-page__subtitle">Portal do Paciente</div>
        </div>
        <div class="lc-flex lc-gap-sm">
            <a class="lc-btn lc-btn--secondary" href="/portal">Dashboard</a>
            <form method="post" action="/portal/logout">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
            </form>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Resumo</div>
        <div class="lc-card__body">
            <div>Logins no portal: <?= (int)($summary['portal_logins'] ?? 0) ?></div>
            <div>Confirmações de consulta: <?= (int)($summary['appointment_confirms'] ?? 0) ?></div>
        </div>
    </div>
</div>
</body>
</html>
