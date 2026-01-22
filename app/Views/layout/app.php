<?php
/** @var string $content */
/** @var string $title */
$csrf = $_SESSION['_csrf'] ?? '';

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};

$isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
$hasClinicContext = isset($_SESSION['active_clinic_id']) && is_int($_SESSION['active_clinic_id']);
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
            <?php if ($can('clinics.read')): ?>
                <a class="lc-nav__item" href="/clinic">Clínica</a>
                <a class="lc-nav__item" href="/clinic/working-hours">Horários</a>
                <a class="lc-nav__item" href="/clinic/closed-days">Dias não atendidos</a>
            <?php endif; ?>

            <?php if ($can('users.read')): ?>
                <a class="lc-nav__item" href="/users">Usuários</a>
            <?php endif; ?>

            <?php if ($can('settings.read')): ?>
                <a class="lc-nav__item" href="/settings">Configurações</a>
            <?php endif; ?>

            <?php if ($can('audit.read') && $hasClinicContext): ?>
                <a class="lc-nav__item" href="/audit-logs">Auditoria</a>
            <?php endif; ?>

            <?php if ($can('rbac.manage') && $hasClinicContext): ?>
                <a class="lc-nav__item" href="/rbac">Papéis & Permissões</a>
            <?php endif; ?>

            <?php if ($isSuperAdmin): ?>
                <a class="lc-nav__item" href="/sys/clinics">Admin do Sistema</a>
            <?php endif; ?>
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
