<?php
// variables expected:
// $title (string)
// $portal_active (string|null)
// $portal_content (string)

$csrf = $_SESSION['_csrf'] ?? '';
$title = isset($title) ? (string)$title : 'Portal do Paciente';
$portal_active = $portal_active ?? null;
$portal_content = $portal_content ?? '';
$patientName = isset($_SESSION['patient_name']) ? trim((string)$_SESSION['patient_name']) : '';

$seo = isset($seo) && is_array($seo) ? $seo : [];
$seoSiteName = trim((string)($seo['site_name'] ?? ''));
$seoDefaultTitle = trim((string)($seo['default_title'] ?? ''));
$seoDescription = trim((string)($seo['meta_description'] ?? ''));
$seoOgImageUrl = trim((string)($seo['og_image_url'] ?? ''));
$seoFaviconUrl = trim((string)($seo['favicon_url'] ?? ''));

$computedTitle = trim((string)($title ?? ''));
if ($computedTitle === '') {
    $computedTitle = $seoDefaultTitle !== '' ? $seoDefaultTitle : 'Portal do Paciente';
}
if ($seoSiteName !== '' && !str_contains($computedTitle, $seoSiteName)) {
    $computedTitle = $computedTitle . ' - ' . $seoSiteName;
}

$path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/portal'), PHP_URL_PATH);
$isActive = function (string $prefix) use ($path): bool {
    if ($prefix === '/portal') {
        return $path === '/portal' || $path === '/portal/';
    }
    return str_starts_with($path, $prefix);
};

$navItem = function (string $href, string $label, bool $active): string {
    $cls = 'lc-nav__item' . ($active ? ' lc-nav__item--active' : '');
    return '<a class="' . $cls . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' .
        '<span class="lc-nav__label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>' .
    '</a>';
};

$portalTitle = $patientName !== '' ? ('Olá, ' . $patientName) : 'Portal do Paciente';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($computedTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <?php if ($seoDescription !== ''): ?>
        <meta name="description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <?php if ($seoFaviconUrl !== ''): ?>
        <link rel="icon" href="<?= htmlspecialchars($seoFaviconUrl, ENT_QUOTES, 'UTF-8') ?>" />
    <?php else: ?>
        <link rel="icon" href="/icone_1.png" />
    <?php endif; ?>
    <meta property="og:title" content="<?= htmlspecialchars($computedTitle, ENT_QUOTES, 'UTF-8') ?>" />
    <?php if ($seoDescription !== ''): ?>
        <meta property="og:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <?php if ($seoOgImageUrl !== ''): ?>
        <meta property="og:image" content="<?= htmlspecialchars($seoOgImageUrl, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= htmlspecialchars($computedTitle, ENT_QUOTES, 'UTF-8') ?>" />
    <?php if ($seoDescription !== ''): ?>
        <meta name="twitter:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <?php if ($seoOgImageUrl !== ''): ?>
        <meta name="twitter:image" content="<?= htmlspecialchars($seoOgImageUrl, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body">
<div class="lc-shell">
    <aside class="lc-sidebar" id="lcSidebar">
        <div class="lc-brand">
            <div class="lc-brand__logo" style="padding:0; background:#000;">
                <img src="/icone_1.png" alt="LumiClinic" style="width:100%; height:100%; object-fit:contain; border-radius:12px; display:block;" />
            </div>
            <div class="lc-brand__name" style="line-height:0;">
                <span style="display:block; font-weight:800; font-size:16px; letter-spacing:0.2px; line-height:1;">LumiClinic</span>
            </div>
        </div>

        <nav class="lc-nav">
            <?= $navItem('/portal', 'Início', $isActive('/portal')) ?>

            <details class="lc-navgroup" <?= $isActive('/portal/agenda') || $isActive('/portal/documentos') || $isActive('/portal/uploads') || $isActive('/portal/notificacoes') || $isActive('/portal/conteudos') ? 'open' : '' ?>>
                <summary class="lc-nav__item lc-navgroup__summary<?= ($isActive('/portal/agenda') || $isActive('/portal/documentos') || $isActive('/portal/uploads') || $isActive('/portal/notificacoes') || $isActive('/portal/conteudos')) ? ' lc-nav__item--active' : '' ?>">
                    <span class="lc-nav__label">Meu Portal</span>
                    <span class="lc-navgroup__chev" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </summary>
                <div class="lc-navgroup__children">
                    <div class="lc-nav__sub">
                        <?= $navItem('/portal/agenda', 'Agenda', $isActive('/portal/agenda')) ?>
                        <?= $navItem('/portal/documentos', 'Documentos', $isActive('/portal/documentos')) ?>
                        <?= $navItem('/portal/uploads', 'Enviar fotos', $isActive('/portal/uploads')) ?>
                        <?= $navItem('/portal/notificacoes', 'Notificações', $isActive('/portal/notificacoes')) ?>
                        <?= $navItem('/portal/conteudos', 'Conteúdos', $isActive('/portal/conteudos')) ?>
                    </div>
                </div>
            </details>

            <details class="lc-navgroup" <?= $isActive('/portal/perfil') || $isActive('/portal/seguranca') || $isActive('/portal/lgpd') || $isActive('/portal/api-tokens') || $isActive('/portal/metricas') ? 'open' : '' ?>>
                <summary class="lc-nav__item lc-navgroup__summary<?= ($isActive('/portal/perfil') || $isActive('/portal/seguranca') || $isActive('/portal/lgpd') || $isActive('/portal/api-tokens') || $isActive('/portal/metricas')) ? ' lc-nav__item--active' : '' ?>">
                    <span class="lc-nav__label">Conta</span>
                    <span class="lc-navgroup__chev" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </summary>
                <div class="lc-navgroup__children">
                    <div class="lc-nav__sub">
                        <?= $navItem('/portal/perfil', 'Perfil', $isActive('/portal/perfil')) ?>
                        <?= $navItem('/portal/seguranca', 'Segurança', $isActive('/portal/seguranca')) ?>
                        <?= $navItem('/portal/lgpd', 'LGPD', $isActive('/portal/lgpd')) ?>
                        <?= $navItem('/portal/api-tokens', 'API Tokens', $isActive('/portal/api-tokens')) ?>
                        <?= $navItem('/portal/metricas', 'Métricas', $isActive('/portal/metricas')) ?>
                    </div>
                </div>
            </details>
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
                    <div class="lc-header__title"><?= htmlspecialchars((string)$portalTitle, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="lc-topbar__search">
                        <input class="lc-input" id="lcQuickSearch" type="search" placeholder="Pesquisar no portal..." autocomplete="off" />
                    </div>
                </div>
                <div class="lc-topbar__right">
                    <details class="lc-actions__more">
                        <summary class="lc-topbar__icon" aria-label="Menu do usuário">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </summary>
                        <div class="lc-actions__menu">
                            <form method="post" action="/portal/logout">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <section class="lc-content">
            <?= (string)$portal_content ?>

            <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
                <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
            </div>
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
    const key = 'lc.portal.sidebar.collapsed';
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

    const qs = document.getElementById('lcQuickSearch');
    if (qs) {
      qs.addEventListener('keydown', function(e){
        if (!e || e.key !== 'Enter') return;
        e.preventDefault();
        const q = (qs.value || '').trim();
        if (!q) return;
        window.location.href = '/portal/busca?q=' + encodeURIComponent(q);
      });
    }
  } catch (e) {}
})();
</script>
</body>
</html>
