<?php
$title = 'Notificações';
$csrf = $_SESSION['_csrf'] ?? '';
$notifications = $notifications ?? [];
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
            <h1 class="lc-page__title">Notificações</h1>
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
        <div class="lc-card__title">Últimas</div>
        <div class="lc-card__body">
            <?php if (!is_array($notifications) || $notifications === []): ?>
                <div>Nenhuma notificação.</div>
            <?php else: ?>
                <div class="lc-grid" style="gap:10px;">
                    <?php foreach ($notifications as $n): ?>
                        <div class="lc-card" style="padding:12px;">
                            <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
                                <div>
                                    <div><strong><?= htmlspecialchars((string)($n['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                    <div><?= nl2br(htmlspecialchars((string)($n['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                                    <div style="margin-top:6px; opacity:0.8;">
                                        <?= htmlspecialchars((string)($n['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                                <div class="lc-flex" style="gap:8px; align-items:flex-start;">
                                    <?php if (($n['read_at'] ?? null) === null): ?>
                                        <form method="post" action="/portal/notificacoes/read">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)($n['id'] ?? 0) ?>" />
                                            <button class="lc-btn lc-btn--secondary" type="submit">Marcar como lida</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="lc-badge lc-badge--gray">Lida</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
