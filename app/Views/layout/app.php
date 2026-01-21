<?php
/** @var string $content */
/** @var string $title */
$csrf = $_SESSION['_csrf'] ?? '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title ?? 'LumiClinic', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body">
<div class="lc-shell">
    <aside class="lc-sidebar">
        <div class="lc-brand">
            <div class="lc-brand__logo">LC</div>
            <div class="lc-brand__name">LumiClinic</div>
        </div>

        <nav class="lc-nav">
            <a class="lc-nav__item" href="/">Dashboard</a>
            <a class="lc-nav__item" href="/clinic">Clínica</a>
            <a class="lc-nav__item" href="/users">Usuários</a>
            <a class="lc-nav__item" href="/settings">Configurações</a>
        </nav>

        <form method="post" action="/logout" class="lc-sidebar__footer">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
        </form>
    </aside>

    <main class="lc-main">
        <header class="lc-header">
            <div class="lc-header__title"><?= htmlspecialchars($title ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></div>
        </header>

        <section class="lc-content">
            <?= $content ?>
        </section>
    </main>
</div>
</body>
</html>
