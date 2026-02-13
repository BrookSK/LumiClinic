<?php
/** @var string $content */
/** @var string $title */
$csrf = $_SESSION['_csrf'] ?? '';

$seo = isset($seo) && is_array($seo) ? $seo : [];
$seoSiteName = trim((string)($seo['site_name'] ?? ''));
$seoDefaultTitle = trim((string)($seo['default_title'] ?? ''));
$seoDescription = trim((string)($seo['meta_description'] ?? ''));
$seoOgImageUrl = trim((string)($seo['og_image_url'] ?? ''));
$seoFaviconUrl = trim((string)($seo['favicon_url'] ?? ''));

$computedTitle = trim((string)($title ?? ''));
if ($computedTitle === '') {
    $computedTitle = $seoDefaultTitle !== '' ? $seoDefaultTitle : 'LumiClinic';
} elseif ($seoSiteName !== '' && !str_contains($computedTitle, $seoSiteName)) {
    $computedTitle = $computedTitle . ' - ' . $seoSiteName;
}

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

$requiredLegalDocs = $_SESSION['required_legal_docs'] ?? [];
if (!is_array($requiredLegalDocs)) {
    $requiredLegalDocs = [];
}

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
    'help' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.82 1c0 2-3 2-3 4"/><path d="M12 17h.01"/></svg>',
];
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
            <?php if ($isSuperAdmin): ?>
                <?= $navItem('/sys/clinics', 'Clínicas', $ico['sys'], $isActive('/sys/clinics')) ?>
                <?= $navItem('/sys/billing', 'Assinaturas', $ico['finance'], $isActive('/sys/billing')) ?>
                <?= $navItem('/sys/plans', 'Planos', $ico['finance'], $isActive('/sys/plans')) ?>
                <?= $navItem('/sys/legal-owner-documents', 'LGPD & Termos (Owners)', $ico['shield'], $isActive('/sys/legal-owner-documents') || $isActive('/sys/legal-owner-acceptances')) ?>

                <details class="lc-navgroup" <?= $isActive('/sys/settings') ? 'open' : '' ?>>
                    <summary class="lc-nav__item lc-navgroup__summary<?= $isActive('/sys/settings') ? ' lc-nav__item--active' : '' ?>">
                        <span class="lc-nav__icon" aria-hidden="true"><?= $ico['settings'] ?></span>
                        <span class="lc-nav__label">Configurações</span>
                        <span class="lc-navgroup__chev" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </summary>
                    <div class="lc-navgroup__children">
                        <div class="lc-nav__sub">
                            <?= $navItem('/sys/settings/billing', 'Assinatura', $ico['settings'], $isActive('/sys/settings/billing')) ?>
                            <?= $navItem('/sys/settings/seo', 'SEO', $ico['settings'], $isActive('/sys/settings/seo')) ?>
                            <?= $navItem('/sys/settings/support', 'Suporte', $ico['settings'], $isActive('/sys/settings/support')) ?>
                            <?= $navItem('/sys/settings/mail', 'E-mail', $ico['settings'], $isActive('/sys/settings/mail')) ?>
                            <?= $navItem('/sys/settings/dev-alerts', 'Alertas de erro', $ico['settings'], $isActive('/sys/settings/dev-alerts')) ?>
                        </div>
                    </div>
                </details>

                <?= $navItem('/sys/error-logs', 'Logs de erro', $ico['settings'], $isActive('/sys/error-logs')) ?>
                <?= $navItem('/sys/queue-jobs', 'Fila', $ico['stock'], $isActive('/sys/queue-jobs')) ?>
                <?= $navItem('/tutorial/sistema', 'Ajuda', $ico['help'], $isActive('/tutorial/sistema')) ?>
            <?php else: ?>
                <?= $navItem('/', 'Dashboard', $ico['dashboard'], $isActive('/')) ?>

                <?php
                    $configNav = '';
                    ob_start();
                ?>
                <?php
                    $cfgActive = $isActive('/settings')
                        || $isActive('/clinic')
                        || $isActive('/users')
                        || $isActive('/professionals')
                        || $isActive('/rbac')
                        || $isActive('/services')
                        || $isActive('/anamnesis')
                        || $isActive('/blocks')
                        || $isActive('/schedule-rules')
                        || $isActive('/consent-terms');
                ?>
                <details class="lc-navgroup" <?= $cfgActive ? 'open' : '' ?>>
                    <summary class="lc-nav__item lc-navgroup__summary<?= $cfgActive ? ' lc-nav__item--active' : '' ?>">
                        <span class="lc-nav__icon" aria-hidden="true"><?= $ico['settings'] ?></span>
                        <span class="lc-nav__label">Configurações</span>
                        <span class="lc-navgroup__chev" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </summary>
                    <div class="lc-navgroup__children">
                        <div class="lc-nav__sub">
                            <?php if ($can('settings.read')): ?>
                                <?= $navItem('/settings', 'Geral', $ico['settings'], $isActive('/settings') && !$isActive('/settings/legal-documents')) ?>
                            <?php endif; ?>

                            <?php if ($can('clinics.read') && $hasClinicContext): ?>
                                <?= $navItem('/clinic', 'Clínica', $ico['clinic'], $isActive('/clinic') && !$isActive('/clinic/working-hours') && !$isActive('/clinic/closed-days') && !$isActive('/clinic/legal-documents') && !$isActive('/clinic/legal-acceptances/portal')) ?>
                            <?php endif; ?>

                            <?php if ($hasClinicContext): ?>
                                <?php $agendaCfgActive = $isActive('/clinic/working-hours') || $isActive('/clinic/closed-days') || $isActive('/schedule-rules') || $isActive('/blocks'); ?>
                                <?php if ($can('clinics.read') || $can('schedule_rules.manage') || $can('blocks.manage')): ?>
                                    <details class="lc-navgroup" <?= $agendaCfgActive ? 'open' : '' ?> style="margin-left:10px;">
                                        <summary class="lc-nav__item lc-navgroup__summary<?= $agendaCfgActive ? ' lc-nav__item--active' : '' ?>">
                                            <span class="lc-nav__icon" aria-hidden="true"><?= $ico['calendar'] ?></span>
                                            <span class="lc-nav__label">Agenda</span>
                                            <span class="lc-navgroup__chev" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                            </span>
                                        </summary>
                                        <div class="lc-navgroup__children">
                                            <div class="lc-nav__sub">
                                                <?php if ($can('clinics.read')): ?>
                                                    <?= $navItem('/clinic/working-hours', 'Horários', $ico['calendar'], $isActive('/clinic/working-hours')) ?>
                                                    <?= $navItem('/clinic/closed-days', 'Feriados', $ico['calendar'], $isActive('/clinic/closed-days')) ?>
                                                <?php endif; ?>
                                                <?php if ($can('schedule_rules.manage')): ?>
                                                    <?= $navItem('/schedule-rules', 'Regras', $ico['calendar'], $isActive('/schedule-rules')) ?>
                                                <?php endif; ?>
                                                <?php if ($can('blocks.manage')): ?>
                                                    <?= $navItem('/blocks', 'Bloqueios', $ico['calendar'], $isActive('/blocks')) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php $accessActive = $isActive('/users') || $isActive('/professionals') || $isActive('/rbac'); ?>
                            <?php if ($can('users.read') && $hasClinicContext): ?>
                                <details class="lc-navgroup" <?= $accessActive ? 'open' : '' ?> style="margin-left:10px;">
                                    <summary class="lc-nav__item lc-navgroup__summary<?= $accessActive ? ' lc-nav__item--active' : '' ?>">
                                        <span class="lc-nav__icon" aria-hidden="true"><?= $ico['users'] ?></span>
                                        <span class="lc-nav__label">Usuários & Acesso</span>
                                        <span class="lc-navgroup__chev" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                        </span>
                                    </summary>
                                    <div class="lc-navgroup__children">
                                        <div class="lc-nav__sub">
                                            <?= $navItem('/users', 'Usuários', $ico['users'], $isActive('/users')) ?>
                                            <?php if ($can('professionals.manage')): ?>
                                                <?= $navItem('/professionals', 'Profissionais', $ico['users'], $isActive('/professionals')) ?>
                                            <?php endif; ?>
                                            <?php if ($can('rbac.manage')): ?>
                                                <?= $navItem('/rbac', 'Papéis & Permissões', $ico['shield'], $isActive('/rbac')) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </details>
                            <?php endif; ?>

                            <?php $docsActive = $isActive('/settings/legal-documents') || $isActive('/clinic/legal-documents') || $isActive('/clinic/legal-acceptances/portal') || $isActive('/consent-terms'); ?>
                            <?php if (($can('settings.read') || $can('clinics.read') || $can('consent_terms.manage')) && $hasClinicContext): ?>
                                <details class="lc-navgroup" <?= $docsActive ? 'open' : '' ?> style="margin-left:10px;">
                                    <summary class="lc-nav__item lc-navgroup__summary<?= $docsActive ? ' lc-nav__item--active' : '' ?>">
                                        <span class="lc-nav__icon" aria-hidden="true"><?= $ico['shield'] ?></span>
                                        <span class="lc-nav__label">Documentos & Termos</span>
                                        <span class="lc-navgroup__chev" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                        </span>
                                    </summary>
                                    <div class="lc-navgroup__children">
                                        <div class="lc-nav__sub">
                                            <?php if ($can('settings.read')): ?>
                                                <?= $navItem('/settings/legal-documents', 'Equipe', $ico['shield'], $isActive('/settings/legal-documents')) ?>
                                            <?php endif; ?>
                                            <?php if ($can('clinics.read')): ?>
                                                <?= $navItem('/clinic/legal-documents', 'Portal (modelos)', $ico['shield'], $isActive('/clinic/legal-documents')) ?>
                                                <?= $navItem('/clinic/legal-acceptances/portal', 'Portal (aceites)', $ico['shield'], $isActive('/clinic/legal-acceptances/portal')) ?>
                                            <?php endif; ?>
                                            <?php if ($can('consent_terms.manage')): ?>
                                                <?= $navItem('/consent-terms', 'Consentimento (Legado)', $ico['shield'], $isActive('/consent-terms')) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </details>
                            <?php endif; ?>

                            <?php if ($can('anamnesis.manage') && $hasClinicContext): ?>
                                <?= $navItem('/anamnesis/templates', 'Anamnese', $ico['shield'], $isActive('/anamnesis')) ?>
                            <?php endif; ?>

                            <?php $servicesActive = $isActive('/services') || $isActive('/services/materials'); ?>
                            <?php if ($can('services.manage') && $hasClinicContext): ?>
                                <details class="lc-navgroup" <?= $servicesActive ? 'open' : '' ?> style="margin-left:10px;">
                                    <summary class="lc-nav__item lc-navgroup__summary<?= $servicesActive ? ' lc-nav__item--active' : '' ?>">
                                        <span class="lc-nav__icon" aria-hidden="true"><?= $ico['clinic'] ?></span>
                                        <span class="lc-nav__label">Serviços</span>
                                        <span class="lc-navgroup__chev" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                        </span>
                                    </summary>
                                    <div class="lc-navgroup__children">
                                        <div class="lc-nav__sub">
                                            <?= $navItem('/services', 'Cadastro', $ico['clinic'], $isActive('/services')) ?>
                                            <?= $navItem('/services/materials', 'Vínculo com estoque', $ico['stock'], $isActive('/services/materials')) ?>
                                        </div>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </div>
                    </div>
                </details>
                <?php $configNav = (string)ob_get_clean(); ?>
                <?php if (isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) && in_array('owner', $_SESSION['role_codes'], true)): ?>
                    <?= $navItem('/billing/subscription', 'Assinatura', $ico['finance'], $isActive('/billing/subscription')) ?>
                <?php endif; ?>

                <?php $agendaActive = $isActive('/schedule') || $isActive('/schedule/ops'); ?>
                <?php if ($can('scheduling.read')): ?>
                    <details class="lc-navgroup" <?= $agendaActive ? 'open' : '' ?>>
                        <summary class="lc-nav__item lc-navgroup__summary<?= $agendaActive ? ' lc-nav__item--active' : '' ?>">
                            <span class="lc-nav__icon" aria-hidden="true"><?= $ico['calendar'] ?></span>
                            <span class="lc-nav__label">Agenda</span>
                            <span class="lc-navgroup__chev" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </summary>
                        <div class="lc-navgroup__children">
                            <div class="lc-nav__sub">
                                <?= $navItem('/schedule', 'Agenda', $ico['calendar'], $isActive('/schedule')) ?>
                                <?php if ($can('scheduling.ops') && $hasClinicContext): ?>
                                    <?= $navItem('/schedule/ops', 'Operação da Agenda', $ico['calendar'], $isActive('/schedule/ops')) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>

                <?php $patientsActive = $isActive('/patients') || $isActive('/patients/profile-requests'); ?>
                <?php if ($can('patients.read') && $hasClinicContext): ?>
                    <details class="lc-navgroup" <?= $patientsActive ? 'open' : '' ?>>
                        <summary class="lc-nav__item lc-navgroup__summary<?= $patientsActive ? ' lc-nav__item--active' : '' ?>">
                            <span class="lc-nav__icon" aria-hidden="true"><?= $ico['patients'] ?></span>
                            <span class="lc-nav__label">Pacientes</span>
                            <span class="lc-navgroup__chev" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </summary>
                        <div class="lc-navgroup__children">
                            <div class="lc-nav__sub">
                                <?= $navItem('/patients', 'Pacientes', $ico['patients'], $isActive('/patients')) ?>
                                <?php if ($can('patients.update') && $hasClinicContext): ?>
                                    <?= $navItem('/patients/profile-requests', 'Solicitações de perfil', $ico['patients'], $isActive('/patients/profile-requests')) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>

                <?php if ($can('finance.sales.read') && $hasClinicContext): ?>
                    <details class="lc-navgroup" <?= $isActive('/finance') ? 'open' : '' ?>>
                        <summary class="lc-nav__item lc-navgroup__summary<?= $isActive('/finance') ? ' lc-nav__item--active' : '' ?>">
                            <span class="lc-nav__icon" aria-hidden="true"><?= $ico['finance'] ?></span>
                            <span class="lc-nav__label">Financeiro</span>
                            <span class="lc-navgroup__chev" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </summary>
                        <div class="lc-navgroup__children">
                            <div class="lc-nav__sub">
                                <?= $navItem('/finance/sales', 'Vendas', $ico['finance'], $isActive('/finance/sales')) ?>
                                <?php if ($can('finance.entries.read')): ?>
                                    <?= $navItem('/finance/cashflow', 'Caixa', $ico['finance'], $isActive('/finance/cashflow')) ?>
                                <?php endif; ?>
                                <?php if ($can('finance.ap.read')): ?>
                                    <?= $navItem('/finance/accounts-payable', 'Contas a Pagar', $ico['finance'], $isActive('/finance/accounts-payable')) ?>
                                <?php endif; ?>
                                <?php if ($can('finance.cost_centers.manage')): ?>
                                    <?= $navItem('/finance/cost-centers', 'Centros de custo', $ico['finance'], $isActive('/finance/cost-centers')) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>

                <?php if ($can('stock.materials.read') && $hasClinicContext): ?>
                    <details class="lc-navgroup" <?= $isActive('/stock') ? 'open' : '' ?>>
                        <summary class="lc-nav__item lc-navgroup__summary<?= $isActive('/stock') ? ' lc-nav__item--active' : '' ?>">
                            <span class="lc-nav__icon" aria-hidden="true"><?= $ico['stock'] ?></span>
                            <span class="lc-nav__label">Estoque</span>
                            <span class="lc-navgroup__chev" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </summary>
                        <div class="lc-navgroup__children">
                            <div class="lc-nav__sub">
                                <?= $navItem('/stock/materials', 'Materiais', $ico['stock'], $isActive('/stock/materials')) ?>
                                <?php if ($can('stock.materials.manage')): ?>
                                    <?= $navItem('/stock/categories', 'Categorias', $ico['stock'], $isActive('/stock/categories')) ?>
                                    <?= $navItem('/stock/units', 'Unidades', $ico['stock'], $isActive('/stock/units')) ?>
                                <?php endif; ?>
                                <?php if ($can('stock.movements.read')): ?>
                                    <?= $navItem('/stock/movements', 'Movimentações', $ico['stock'], $isActive('/stock/movements')) ?>
                                <?php endif; ?>
                                <?php if ($can('stock.alerts.read')): ?>
                                    <?= $navItem('/stock/alerts', 'Alertas', $ico['stock'], $isActive('/stock/alerts')) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>

                <?php $marketingActive = $isActive('/marketing'); ?>
                <?php if ($can('marketing.calendar.read') && $hasClinicContext): ?>
                    <details class="lc-navgroup" <?= $marketingActive ? 'open' : '' ?>>
                        <summary class="lc-nav__item lc-navgroup__summary<?= $marketingActive ? ' lc-nav__item--active' : '' ?>">
                            <span class="lc-nav__icon" aria-hidden="true"><?= $ico['calendar'] ?></span>
                            <span class="lc-nav__label">Marketing</span>
                            <span class="lc-navgroup__chev" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </summary>
                        <div class="lc-navgroup__children">
                            <div class="lc-nav__sub">
                                <?= $navItem('/marketing/calendar', 'Agenda de Marketing', $ico['calendar'], $isActive('/marketing/calendar')) ?>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>

                <?php
                    $reportsActive =
                        $isActive('/finance/reports') ||
                        $isActive('/stock/reports') ||
                        $isActive('/audit-logs') ||
                        $isActive('/bi') ||
                        $isActive('/compliance');
                ?>
                <?php if ($hasClinicContext && ($can('finance.reports.read') || $can('stock.reports.read') || $can('audit.read') || $can('bi.read') || $can('compliance.lgpd.read') || $can('compliance.policies.read') || $can('compliance.incidents.read'))): ?>
                    <details class="lc-navgroup" <?= $reportsActive ? 'open' : '' ?>>
                        <summary class="lc-nav__item lc-navgroup__summary<?= $reportsActive ? ' lc-nav__item--active' : '' ?>">
                            <span class="lc-nav__icon" aria-hidden="true"><?= $ico['dashboard'] ?></span>
                            <span class="lc-nav__label">Relatórios</span>
                            <span class="lc-navgroup__chev" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </summary>
                        <div class="lc-navgroup__children">
                            <div class="lc-nav__sub">
                                <?php if ($can('finance.reports.read')): ?>
                                    <?= $navItem('/finance/reports', 'Financeiro', $ico['finance'], $isActive('/finance/reports')) ?>
                                <?php endif; ?>
                                <?php if ($can('stock.reports.read')): ?>
                                    <?= $navItem('/stock/reports', 'Estoque', $ico['stock'], $isActive('/stock/reports')) ?>
                                <?php endif; ?>
                                <?php if ($can('audit.read')): ?>
                                    <?= $navItem('/audit-logs', 'Auditoria', $ico['shield'], $isActive('/audit-logs')) ?>
                                <?php endif; ?>
                                <?php if ($can('bi.read')): ?>
                                    <?= $navItem('/bi', 'BI', $ico['dashboard'], $isActive('/bi')) ?>
                                <?php endif; ?>
                                <?php if ($can('compliance.lgpd.read')): ?>
                                    <?= $navItem('/compliance/lgpd-requests', 'LGPD (Solicitações)', $ico['shield'], $isActive('/compliance/lgpd-requests')) ?>
                                <?php endif; ?>
                                <?php if ($can('compliance.policies.read')): ?>
                                    <?= $navItem('/compliance/certifications', 'Certificações', $ico['shield'], $isActive('/compliance/certifications')) ?>
                                <?php endif; ?>
                                <?php if ($can('compliance.incidents.read')): ?>
                                    <?= $navItem('/compliance/incidents', 'Incidentes', $ico['shield'], $isActive('/compliance/incidents')) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>

                <?php if ($configNav !== ''): ?>
                    <?= $configNav ?>
                <?php endif; ?>

                <?= $navItem('/tutorial/sistema', 'Ajuda', $ico['help'], $isActive('/tutorial/sistema')) ?>
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
                        <input class="lc-input" id="lcQuickSearch" type="search" placeholder="Busca rápida..." autocomplete="off" />
                    </div>
                </div>
                <div class="lc-topbar__right">
                    <div class="lc-topbar__pill"><?= $isSuperAdmin ? 'ADMINISTRADOR' : 'CLÍNICA' ?></div>

                    <details class="lc-actions__more">
                        <summary class="lc-topbar__icon" aria-label="Menu do usuário">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </summary>
                        <div class="lc-actions__menu">
                            <a class="lc-btn lc-btn--secondary" href="/me">Perfil</a>
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

<?php if ($requiredLegalDocs !== []): ?>
    <style>
        .lc-modal-overlay{position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9999; display:flex; align-items:center; justify-content:center; padding:18px;}
        .lc-modal{width:100%; max-width:820px; background:#fff; border-radius:14px; box-shadow:0 16px 50px rgba(0,0,0,.35); overflow:hidden;}
        .lc-modal__hd{padding:14px 16px; border-bottom:1px solid rgba(0,0,0,.08); font-weight:800;}
        .lc-modal__bd{padding:14px 16px;}
        .lc-modal__ft{padding:14px 16px; border-top:1px solid rgba(0,0,0,.08); display:flex; gap:10px; justify-content:flex-end;}
        .lc-modal__list{margin-top:10px; display:grid; gap:10px;}
        .lc-modal__item{padding:12px; border:1px solid rgba(0,0,0,.08); border-radius:12px; display:flex; gap:12px; align-items:flex-start; justify-content:space-between;}
        .lc-modal__item-title{font-weight:700;}
        .lc-modal__read-overlay{position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:10000; display:none; align-items:center; justify-content:center; padding:18px;}
        .lc-modal__read{width:100%; max-width:900px; max-height:86vh; background:#fff; border-radius:14px; overflow:hidden; display:flex; flex-direction:column;}
        .lc-modal__read-body{padding:14px 16px; overflow:auto; white-space:pre-wrap; line-height:1.6;}
    </style>

    <div class="lc-modal-overlay" id="lcReqOverlay" aria-modal="true" role="dialog">
        <div class="lc-modal">
            <div class="lc-modal__hd">Antes de continuar</div>
            <div class="lc-modal__bd">
                <div class="lc-alert lc-alert--info">Para usar o sistema, você precisa aceitar os termos obrigatórios.</div>

                <div class="lc-modal__list">
                    <?php foreach ($requiredLegalDocs as $d): ?>
                        <div class="lc-modal__item">
                            <div style="min-width:0;">
                                <div class="lc-modal__item-title"><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="lc-flex lc-gap-sm" style="flex-shrink:0;">
                                <button class="lc-btn lc-btn--secondary" type="button" data-lc-read-doc="<?= (int)($d['id'] ?? 0) ?>">Ler</button>

                                <form method="post" action="/legal/accept" style="display:inline-block;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($d['id'] ?? 0) ?>" />
                                    <button class="lc-btn lc-btn--primary" type="submit">Aceitar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="lc-alert lc-alert--danger" style="margin-top:12px;">Você não pode mexer no sistema enquanto houver termos obrigatórios pendentes.</div>
            </div>
        </div>
    </div>

    <div class="lc-modal__read-overlay" id="lcReqRead" aria-modal="true" role="dialog">
        <div class="lc-modal__read">
            <div class="lc-modal__hd" id="lcReqReadTitle">Documento</div>
            <div class="lc-modal__read-body" id="lcReqReadBody"></div>
            <div class="lc-modal__ft">
                <button class="lc-btn lc-btn--secondary" type="button" id="lcReqReadClose">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        (function(){
            try {
                var docs = <?php echo (string)json_encode($requiredLegalDocs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                var docsMap = {};
                if (Array.isArray(docs)) {
                    for (var i=0; i<docs.length; i++) {
                        var it = docs[i] || {};
                        var id = parseInt(it.id || 0, 10);
                        if (id > 0) docsMap[id] = it;
                    }
                }

                var readOverlay = document.getElementById('lcReqRead');
                var readTitle = document.getElementById('lcReqReadTitle');
                var readBody = document.getElementById('lcReqReadBody');
                var closeBtn = document.getElementById('lcReqReadClose');

                function openRead(t,b){
                    if (!readOverlay) return;
                    if (readTitle) readTitle.textContent = t || 'Documento';
                    if (readBody) readBody.textContent = b || '';
                    readOverlay.style.display = 'flex';
                }
                function closeRead(){
                    if (!readOverlay) return;
                    readOverlay.style.display = 'none';
                }

                document.addEventListener('click', function(e){
                    var el = e && e.target ? e.target : null;
                    if (!el) return;
                    var btn = el.closest ? el.closest('[data-lc-read-doc]') : null;
                    if (btn) {
                        e.preventDefault();
                        var id = parseInt(btn.getAttribute('data-lc-read-doc') || '0', 10);
                        var d = docsMap[id] || null;
                        openRead(d && d.title ? String(d.title) : 'Documento', d && d.body ? String(d.body) : '');
                    }
                });

                if (closeBtn) closeBtn.addEventListener('click', closeRead);
                if (readOverlay) {
                    readOverlay.addEventListener('click', function(e){
                        if (e && e.target === readOverlay) closeRead();
                    });
                }
            } catch (e) {}
        })();
    </script>
<?php endif; ?>

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

    const qs = document.getElementById('lcQuickSearch');
    if (qs) {
      qs.addEventListener('keydown', function(e){
        if (!e || e.key !== 'Enter') return;
        e.preventDefault();
        const q = (qs.value || '').trim();
        if (!q) return;
        const isSuperAdmin = <?= $isSuperAdmin ? 'true' : 'false' ?>;
        const canPatients = <?= ($can('patients.read') && $hasClinicContext) ? 'true' : 'false' ?>;
        const url = isSuperAdmin
          ? ('/sys/clinics?q=' + encodeURIComponent(q))
          : (canPatients ? ('/patients?q=' + encodeURIComponent(q) + '&page=1') : ('/?q=' + encodeURIComponent(q)));
        window.location.href = url;
      });
    }
  } catch (e) {}
})();
</script>
</body>
</html>
