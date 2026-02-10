<?php
$title = 'Conteúdos';
$csrf = $_SESSION['_csrf'] ?? '';
$items = $items ?? [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body">
<div class="lc-app" style="padding: 16px; max-width: 980px; margin: 0 auto;">
    <div class="lc-page__header">
        <div>
            <h1 class="lc-page__title">Conteúdos</h1>
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
        <div class="lc-card__title">Disponíveis</div>
        <div class="lc-card__body">
            <?php if (!is_array($items) || $items === []): ?>
                <div>Nenhum conteúdo disponível.</div>
            <?php else: ?>
                <div class="lc-grid" style="gap:10px;">
                    <?php foreach ($items as $c): ?>
                        <div class="lc-card" style="padding:12px;">
                            <div><strong><?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                            <div><?= htmlspecialchars((string)($c['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (isset($c['url']) && (string)$c['url'] !== ''): ?>
                                <div style="margin-top:8px;">
                                    <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars((string)$c['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">Abrir</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
