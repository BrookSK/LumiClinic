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

$path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$isActive = function (string $prefix) use ($path): bool {
    if ($prefix === '/') {
        return $path === '/';
    }
    return str_starts_with($path, $prefix);
};

$navItem = function (string $href, string $label, string $iconSvg, bool $active): string {
    $cls = 'lc-nav__item' . ($active ? ' lc-nav__item--active' : '');
    return '<a class="' . $cls . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' .
        '<span class="lc-nav__icon" aria-hidden="true">' . $iconSvg . '</span>' .
        '<span class="lc-nav__label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>' .
    '</a>';
};

$ico = [
    'dashboard' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13h8V3H3z"/><path d="M13 21h8V11h-8z"/><path d="M13 3h8v6h-8z"/><path d="M3 19h8v2H3z"/></svg>',
    'clinic' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M2 12h20"/></svg>',
    'users' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'settings' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 0-.33 1.82A2 2 0 0 1 12 24a2 2 0 0 1-1.82-1.18 1.65 1.65 0 0 0-.33-1.82 1.65 1.65 0 0 0-1-.6 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-.6-1 1.65 1.65 0 0 0-1.82-.33A2 2 0 0 1 0 12a2 2 0 0 1 1.18-1.82 1.65 1.65 0 0 0 1.82-.33 1.65 1.65 0 0 0 .6-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06A2 2 0 0 1 4.2 4.14a2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-.6 1.65 1.65 0 0 0 .33-1.82A2 2 0 0 1 12 0a2 2 0 0 1 1.82 1.18 1.65 1.65 0 0 0 .33 1.82 1.65 1.65 0 0 0 1 .6 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.2.3.4.6.6 1a2 2 0 0 1 1.18 1.82 2 2 0 0 1-1.18 1.82c-.2.4-.4.7-.6 1z"/></svg>',
    'calendar' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>',
    'finance' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>',
    'stock' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73z"/><path d="M3.3 7L12 12l8.7-5"/><path d="M12 22V12"/></svg>',
    'patients' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-3-3.87"/><path d="M4 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'shield' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    'sys' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 1 4 12.7V17a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-2.3A7 7 0 0 1 12 2z"/></svg>',
];
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
    <aside class="lc-sidebar" id="lcSidebar">
        <div class="lc-brand">
            <div class="lc-brand__logo">LC</div>
            <div class="lc-brand__name">LumiClinic</div>
        </div>

        <nav class="lc-nav">
            <?php if ($isSuperAdmin): ?>
                <?= $navItem('/sys/clinics', 'Clínicas', $ico['sys'], $isActive('/sys/clinics')) ?>
                <?= $navItem('/sys/billing', 'Assinaturas', $ico['finance'], $isActive('/sys/billing')) ?>
                <?= $navItem('/sys/settings/billing', 'Configurações', $ico['settings'], $isActive('/sys/settings')) ?>
                <?= $navItem('/sys/queue-jobs', 'Fila', $ico['stock'], $isActive('/sys/queue-jobs')) ?>
            <?php else: ?>
                <?= $navItem('/', 'Dashboard', $ico['dashboard'], $isActive('/')) ?>
                <?php if ($can('clinics.read')): ?>
                    <?= $navItem('/clinic', 'Clínica', $ico['clinic'], $isActive('/clinic')) ?>
                    <?= $navItem('/clinic/working-hours', 'Horários', $ico['calendar'], $isActive('/clinic/working-hours')) ?>
                    <?= $navItem('/clinic/closed-days', 'Feriados e Recesso', $ico['calendar'], $isActive('/clinic/closed-days')) ?>
                <?php endif; ?>

                <?php if ($can('users.read')): ?>
                    <?= $navItem('/users', 'Usuários', $ico['users'], $isActive('/users')) ?>
                <?php endif; ?>

                <?php if ($can('settings.read')): ?>
                    <?= $navItem('/settings', 'Configurações', $ico['settings'], $isActive('/settings')) ?>
                <?php endif; ?>

                <?php if ($can('scheduling.read')): ?>
                    <?= $navItem('/schedule', 'Agenda', $ico['calendar'], $isActive('/schedule')) ?>
                <?php endif; ?>

                <?php if ($can('finance.sales.read') && $hasClinicContext): ?>
                    <?= $navItem('/finance/sales', 'Financeiro', $ico['finance'], $isActive('/finance')) ?>
                    <?php if ($can('finance.entries.read')): ?>
                        <?= $navItem('/finance/cashflow', 'Caixa', $ico['finance'], $isActive('/finance/cashflow')) ?>
                    <?php endif; ?>
                    <?php if ($can('finance.reports.read')): ?>
                        <?= $navItem('/finance/reports', 'Relatórios Financeiros', $ico['finance'], $isActive('/finance/reports')) ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($can('stock.materials.read') && $hasClinicContext): ?>
                    <?= $navItem('/stock/materials', 'Estoque', $ico['stock'], $isActive('/stock')) ?>
                    <?php if ($can('stock.movements.read')): ?>
                        <?= $navItem('/stock/movements', 'Movimentações', $ico['stock'], $isActive('/stock/movements')) ?>
                    <?php endif; ?>
                    <?php if ($can('stock.alerts.read')): ?>
                        <?= $navItem('/stock/alerts', 'Alertas', $ico['stock'], $isActive('/stock/alerts')) ?>
                    <?php endif; ?>
                    <?php if ($can('stock.reports.read')): ?>
                        <?= $navItem('/stock/reports', 'Relatórios', $ico['stock'], $isActive('/stock/reports')) ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($can('scheduling.ops') && $hasClinicContext): ?>
                    <?= $navItem('/schedule/ops', 'Operação da Agenda', $ico['calendar'], $isActive('/schedule/ops')) ?>
                <?php endif; ?>

                <?php if ($can('patients.read') && $hasClinicContext): ?>
                    <?= $navItem('/patients', 'Pacientes', $ico['patients'], $isActive('/patients')) ?>
                <?php endif; ?>

                <?php if ($can('consent_terms.manage') && $hasClinicContext): ?>
                    <?= $navItem('/consent-terms', 'Termos', $ico['shield'], $isActive('/consent-terms')) ?>
                <?php endif; ?>

                <?php if ($can('anamnesis.manage') && $hasClinicContext): ?>
                    <?= $navItem('/anamnesis/templates', 'Anamnese', $ico['shield'], $isActive('/anamnesis')) ?>
                <?php endif; ?>

                <?php if ($can('services.manage') && $hasClinicContext): ?>
                    <?= $navItem('/services', 'Serviços', $ico['clinic'], $isActive('/services')) ?>
                <?php endif; ?>

                <?php if ($can('professionals.manage') && $hasClinicContext): ?>
                    <?= $navItem('/professionals', 'Profissionais', $ico['users'], $isActive('/professionals')) ?>
                <?php endif; ?>

                <?php if ($can('blocks.manage') && $hasClinicContext): ?>
                    <?= $navItem('/blocks', 'Bloqueios', $ico['calendar'], $isActive('/blocks')) ?>
                <?php endif; ?>

                <?php if ($can('schedule_rules.manage') && $hasClinicContext): ?>
                    <?= $navItem('/schedule-rules', 'Regras de Agenda', $ico['calendar'], $isActive('/schedule-rules')) ?>
                <?php endif; ?>

                <?php if ($can('audit.read') && $hasClinicContext): ?>
                    <?= $navItem('/audit-logs', 'Auditoria', $ico['shield'], $isActive('/audit-logs')) ?>
                <?php endif; ?>

                <?php if ($can('bi.read') && $hasClinicContext): ?>
                    <?= $navItem('/bi', 'BI', $ico['dashboard'], $isActive('/bi')) ?>
                <?php endif; ?>

                <?php if ($can('compliance.lgpd.read') && $hasClinicContext): ?>
                    <?= $navItem('/compliance/lgpd-requests', 'LGPD', $ico['shield'], $isActive('/compliance/lgpd-requests')) ?>
                <?php endif; ?>

                <?php if ($can('compliance.policies.read') && $hasClinicContext): ?>
                    <?= $navItem('/compliance/certifications', 'Certificações', $ico['shield'], $isActive('/compliance/certifications')) ?>
                <?php endif; ?>

                <?php if ($can('compliance.incidents.read') && $hasClinicContext): ?>
                    <?= $navItem('/compliance/incidents', 'Incidentes', $ico['shield'], $isActive('/compliance/incidents')) ?>
                <?php endif; ?>

                <?php if ($can('rbac.manage') && $hasClinicContext): ?>
                    <?= $navItem('/rbac', 'Papéis & Permissões', $ico['shield'], $isActive('/rbac')) ?>
                <?php endif; ?>
            <?php endif; ?>
        </nav>

    </aside>

    <div class="lc-sidebar-backdrop" id="lcSidebarBackdrop" aria-hidden="true"></div>

    <main class="lc-main">
        <header class="lc-header">
            <div class="lc-topbar">
                <div class="lc-topbar__left">
                    <button class="lc-iconbtn" type="button" id="lcSidebarToggle" aria-label="Alternar menu">
                        <span class="lc-iconbtn__bar"></span>
                        <span class="lc-iconbtn__bar"></span>
                        <span class="lc-iconbtn__bar"></span>
                    </button>
                    <div class="lc-header__title"><?= htmlspecialchars($title ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="lc-topbar__search">
                        <input class="lc-input" type="search" placeholder="Busca rápida..." />
                    </div>
                </div>
                <div class="lc-topbar__right">
                    <div class="lc-topbar__pill"><?= $isSuperAdmin ? 'SUPER ADMIN' : 'CLÍNICA' ?></div>

                    <details class="lc-actions__more">
                        <summary class="lc-topbar__icon" aria-label="Menu do usuário">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </summary>
                        <div class="lc-actions__menu">
                            <form method="post" action="/logout">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <section class="lc-content">
            <?= $content ?>
        </section>
    </main>
</div>

<script>
(function(){
  try {
    const body = document.body;
    const toggle = document.getElementById('lcSidebarToggle');
    const backdrop = document.getElementById('lcSidebarBackdrop');
    const sidebar = document.getElementById('lcSidebar');
    const mobileMq = window.matchMedia ? window.matchMedia('(max-width: 920px)') : null;
    const key = 'lc.sidebar.collapsed';
    const saved = window.localStorage ? window.localStorage.getItem(key) : null;
    if (saved === '1') body.classList.add('lc-shell--collapsed');

    function closeMobileSidebar(){
      body.classList.remove('lc-shell--sidebar-open');
    }

    if (backdrop) {
      backdrop.addEventListener('click', closeMobileSidebar);
    }

    if (sidebar) {
      sidebar.addEventListener('click', function(e){
        const isMobile = mobileMq ? mobileMq.matches : (window.innerWidth <= 920);
        if (!isMobile) return;
        const target = e && e.target ? e.target : null;
        if (!target) return;
        const link = target.closest ? target.closest('a') : null;
        if (link && link.getAttribute('href')) {
          closeMobileSidebar();
        }
      });
    }

    window.addEventListener('keydown', function(e){
      if (e && e.key === 'Escape') closeMobileSidebar();
    });

    if (mobileMq && typeof mobileMq.addEventListener === 'function') {
      mobileMq.addEventListener('change', function(){
        closeMobileSidebar();
      });
    }

    if (toggle) {
      toggle.addEventListener('click', function(){
        const isMobile = mobileMq ? mobileMq.matches : (window.innerWidth <= 920);
        if (isMobile) {
          body.classList.toggle('lc-shell--sidebar-open');
          return;
        }

        body.classList.toggle('lc-shell--collapsed');
        const isCollapsed = body.classList.contains('lc-shell--collapsed');
        if (window.localStorage) window.localStorage.setItem(key, isCollapsed ? '1' : '0');
      });
    }
  } catch (e) {}
})();
</script>
</body>
</html>
