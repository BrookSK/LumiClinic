<?php
$csrf = $_SESSION['_csrf'] ?? '';
$title = isset($title) ? (string)$title : 'Portal do Paciente';
$portal_active = $portal_active ?? null;
$portal_content = $portal_content ?? '';
$patientName = isset($_SESSION['patient_name']) ? trim((string)$_SESSION['patient_name']) : '';

$requiredLegalDocs = $_SESSION['portal_required_legal_docs'] ?? [];
if (!is_array($requiredLegalDocs)) $requiredLegalDocs = [];

$seo = isset($seo) && is_array($seo) ? $seo : [];
$seoSiteName = trim((string)($seo['site_name'] ?? ''));
$seoDefaultTitle = trim((string)($seo['default_title'] ?? ''));
$seoDescription = trim((string)($seo['meta_description'] ?? ''));
$seoOgImageUrl = trim((string)($seo['og_image_url'] ?? ''));
$seoFaviconUrl = trim((string)($seo['favicon_url'] ?? ''));

$computedTitle = trim((string)($title ?? ''));
if ($computedTitle === '') $computedTitle = $seoDefaultTitle !== '' ? $seoDefaultTitle : 'Portal do Paciente';
if ($seoSiteName !== '' && !str_contains($computedTitle, $seoSiteName)) $computedTitle .= ' - ' . $seoSiteName;

$path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/portal'), PHP_URL_PATH);
$isActive = function (string $prefix) use ($path): bool {
    if ($prefix === '/portal') return $path === '/portal' || $path === '/portal/';
    return str_starts_with($path, $prefix);
};

$navItem = function (string $href, string $label, string $iconSvg, bool $active): string {
    $cls = 'lc-nav__item' . ($active ? ' lc-nav__item--active' : '');
    return '<a class="' . $cls . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' .
        '<span class="lc-nav__icon" aria-hidden="true">' . $iconSvg . '</span>' .
        '<span class="lc-nav__label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></a>';
};

$ico = [
    'home'=>'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7"/><path d="M9 22V12h6v10"/><path d="M21 22H3"/></svg>',
    'calendar'=>'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>',
    'docs'=>'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>',
    'upload'=>'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>',
    'bell'=>'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
    'user'=>'<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
];

$portalTitle = $patientName !== '' ? ('Olá, ' . $patientName) : 'Portal do Paciente';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($computedTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <?php if ($seoDescription !== ''): ?><meta name="description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>" /><?php endif; ?>
    <?php if ($seoFaviconUrl !== ''): ?><link rel="icon" href="<?= htmlspecialchars($seoFaviconUrl, ENT_QUOTES, 'UTF-8') ?>" /><?php else: ?><link rel="icon" href="/icone_1.png" /><?php endif; ?>
    <meta property="og:title" content="<?= htmlspecialchars($computedTitle, ENT_QUOTES, 'UTF-8') ?>" />
    <meta name="theme-color" content="#111827" />
    <link rel="manifest" href="/portal/manifest.webmanifest" />
    <link rel="apple-touch-icon" href="/icone_1.png" />
    <link rel="stylesheet" href="/assets/css/design-system.css" />
</head>
<body class="lc-body">
<div class="lc-shell">
    <aside class="lc-sidebar" id="lcSidebar">
        <div class="lc-brand">
            <div class="lc-brand__logo" style="padding:0;background:#000;">
                <img src="/icone_1.png" alt="LumiClinic" style="width:100%;height:100%;object-fit:contain;border-radius:12px;display:block;" />
            </div>
            <div class="lc-brand__name" style="line-height:0;">
                <span style="display:block;font-weight:800;font-size:16px;letter-spacing:0.2px;line-height:1;">LumiClinic</span>
            </div>
        </div>

        <nav class="lc-nav">
            <?= $navItem('/portal', 'Início', $ico['home'], $isActive('/portal') && !$isActive('/portal/agenda') && !$isActive('/portal/documentos') && !$isActive('/portal/uploads') && !$isActive('/portal/perfil') && !$isActive('/portal/notificacoes')) ?>
            <?= $navItem('/portal/agenda', 'Agenda', $ico['calendar'], $isActive('/portal/agenda')) ?>
            <?= $navItem('/portal/anamnese', 'Anamnese', $ico['docs'], $isActive('/portal/anamnese')) ?>
            <?= $navItem('/portal/documentos', 'Documentos', $ico['docs'], $isActive('/portal/documentos')) ?>
            <?= $navItem('/portal/uploads', 'Enviar fotos', $ico['upload'], $isActive('/portal/uploads')) ?>
            <?= $navItem('/portal/perfil', 'Meu perfil', $ico['user'], $isActive('/portal/perfil')) ?>
        </nav>
    </aside>

    <div class="lc-sidebar-backdrop" id="lcSidebarBackdrop" aria-hidden="true"></div>

    <main class="lc-main">
        <header class="lc-header">
            <div class="lc-topbar">
                <div class="lc-topbar__left">
                    <button class="lc-iconbtn" type="button" id="lcSidebarToggle" aria-label="Menu">
                        <span class="lc-iconbtn__bar"></span><span class="lc-iconbtn__bar"></span><span class="lc-iconbtn__bar"></span>
                    </button>
                    <div class="lc-header__title"><?= htmlspecialchars($portalTitle, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="lc-topbar__right">
                    <a href="/portal/notificacoes" class="lc-topbar__icon" aria-label="Notificações" title="Notificações">
                        <?= $ico['bell'] ?>
                    </a>
                    <details class="lc-actions__more">
                        <summary class="lc-topbar__icon" aria-label="Menu do usuário">
                            <?= $ico['user'] ?>
                        </summary>
                        <div class="lc-actions__menu">
                            <a class="lc-btn lc-btn--secondary" href="/portal/perfil">Perfil</a>
                            <form method="post" action="/portal/logout">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Sair</button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <section class="lc-content">
            <?= $portal_content ?>

<?php if ($requiredLegalDocs !== [] && !str_starts_with($path, '/portal/legal/sign')): ?>
<style>
.lc-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;display:flex;align-items:center;justify-content:center;padding:18px}
.lc-req-modal{width:100%;max-width:820px;background:#fff;border-radius:14px;box-shadow:0 16px 50px rgba(0,0,0,.35);overflow:hidden}
.lc-modal__hd{padding:14px 16px;border-bottom:1px solid rgba(0,0,0,.08);font-weight:800}
.lc-modal__bd{padding:14px 16px}
.lc-modal__ft{padding:14px 16px;border-top:1px solid rgba(0,0,0,.08);display:flex;gap:10px;justify-content:flex-end}
.lc-modal__list{margin-top:10px;display:grid;gap:10px}
.lc-modal__item{padding:12px;border:1px solid rgba(0,0,0,.08);border-radius:12px;display:flex;gap:12px;align-items:flex-start;justify-content:space-between}
.lc-modal__item-title{font-weight:700}
.lc-modal__read-overlay{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:10000;display:none;align-items:center;justify-content:center;padding:18px}
.lc-modal__read{width:100%;max-width:900px;max-height:86vh;background:#fff;border-radius:14px;overflow:hidden;display:flex;flex-direction:column}
.lc-modal__read-body{padding:14px 16px;overflow:auto;white-space:pre-wrap;line-height:1.6}
</style>
<div class="lc-modal-overlay" id="lcReqOverlay" aria-modal="true" role="dialog">
    <div class="lc-req-modal">
        <div class="lc-modal__hd">Antes de continuar</div>
        <div class="lc-modal__bd">
            <div class="lc-alert lc-alert--info">Para usar o portal, você precisa aceitar os termos obrigatórios.</div>
            <div class="lc-modal__list">
                <?php foreach ($requiredLegalDocs as $d): ?>
                <div class="lc-modal__item">
                    <div style="min-width:0;"><div class="lc-modal__item-title"><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></div>
                    <div class="lc-flex lc-gap-sm" style="flex-shrink:0;">
                        <button class="lc-btn lc-btn--secondary" type="button" data-lc-read-doc="<?= (int)($d['id'] ?? 0) ?>">Ler</button>
                        <a class="lc-btn lc-btn--primary" href="/portal/legal/sign?id=<?= (int)($d['id'] ?? 0) ?>">Assinar</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="lc-alert lc-alert--danger" style="margin-top:12px;">Você não pode usar o portal enquanto houver termos pendentes.</div>
        </div>
    </div>
</div>
<div class="lc-modal__read-overlay" id="lcReqRead" aria-modal="true" role="dialog">
    <div class="lc-modal__read">
        <div class="lc-modal__hd" id="lcReqReadTitle">Documento</div>
        <div class="lc-modal__read-body" id="lcReqReadBody"></div>
        <div class="lc-modal__ft"><button class="lc-btn lc-btn--secondary" type="button" id="lcReqReadClose">Fechar</button></div>
    </div>
</div>
<script>
(function(){
    try{
        var docs=<?= json_encode($requiredLegalDocs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
        var map={};if(Array.isArray(docs))for(var i=0;i<docs.length;i++){var it=docs[i]||{};var id=parseInt(it.id||0,10);if(id>0)map[id]=it;}
        var ro=document.getElementById('lcReqRead'),rt=document.getElementById('lcReqReadTitle'),rb=document.getElementById('lcReqReadBody'),rc=document.getElementById('lcReqReadClose');
        function openR(t,b){if(!ro)return;if(rt)rt.textContent=t||'Documento';if(rb)rb.textContent=b||'';ro.style.display='flex';}
        function closeR(){if(ro)ro.style.display='none';}
        document.addEventListener('click',function(e){var el=e&&e.target?e.target:null;if(!el)return;var btn=el.closest?el.closest('[data-lc-read-doc]'):null;if(btn){e.preventDefault();var id=parseInt(btn.getAttribute('data-lc-read-doc')||'0',10);var d=map[id]||null;openR(d&&d.title?String(d.title):'Documento',d&&d.body?String(d.body):'');}});
        if(rc)rc.addEventListener('click',closeR);
        if(ro)ro.addEventListener('click',function(e){if(e&&e.target===ro)closeR();});
    }catch(e){}
})();
</script>
<?php endif; ?>

    <div class="lc-muted" style="margin-top:24px;padding:10px 2px;text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
        </section>
    </main>
</div>

<script>
(function(){
  try{
    var body=document.body,toggle=document.getElementById('lcSidebarToggle'),backdrop=document.getElementById('lcSidebarBackdrop'),sidebar=document.getElementById('lcSidebar');
    var mq=window.matchMedia?window.matchMedia('(max-width:920px)'):null;
    var key='lc.portal.sidebar.collapsed';
    var saved=window.localStorage?window.localStorage.getItem(key):null;
    if(saved==='1')body.classList.add('lc-shell--collapsed');
    function closeMobile(){body.classList.remove('lc-shell--sidebar-open');}
    if(backdrop)backdrop.addEventListener('click',closeMobile);
    if(sidebar)sidebar.addEventListener('click',function(e){var isMobile=mq?mq.matches:(window.innerWidth<=920);if(!isMobile)return;var t=e&&e.target?e.target:null;if(!t)return;var link=t.closest?t.closest('a'):null;if(link&&link.getAttribute('href'))closeMobile();});
    window.addEventListener('keydown',function(e){if(e&&e.key==='Escape')closeMobile();});
    if(mq&&typeof mq.addEventListener==='function')mq.addEventListener('change',function(){closeMobile();});
    if(toggle)toggle.addEventListener('click',function(){var isMobile=mq?mq.matches:(window.innerWidth<=920);if(isMobile){body.classList.toggle('lc-shell--sidebar-open');return;}body.classList.toggle('lc-shell--collapsed');var c=body.classList.contains('lc-shell--collapsed');if(window.localStorage)window.localStorage.setItem(key,c?'1':'0');});
  }catch(e){}
})();
</script>
<script>
(function(){try{if(!('serviceWorker' in navigator))return;window.addEventListener('load',function(){navigator.serviceWorker.register('/portal/sw.js',{scope:'/portal/'}).catch(function(){});});}catch(e){}})();
</script>
</body>
</html>
