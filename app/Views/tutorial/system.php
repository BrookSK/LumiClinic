<?php
$title = 'Tutorial do Sistema';

$perfil = 'guest';
$perfilLabel = 'Geral';

if (isset($_SESSION['patient_user_id']) && (int)$_SESSION['patient_user_id'] > 0) {
    $perfil = 'patient';
    $perfilLabel = 'Paciente';
} elseif (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
    $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
    $roles = $_SESSION['role_codes'] ?? [];

    if ($isSuperAdmin) {
        $perfil = 'admin';
        $perfilLabel = 'Admin';
    } elseif (is_array($roles) && (in_array('owner', $roles, true) || in_array('admin', $roles, true))) {
        $perfil = 'admin';
        $perfilLabel = 'Admin';
    } elseif (is_array($roles) && in_array('reception', $roles, true)) {
        $perfil = 'recepcao';
        $perfilLabel = 'Recepção';
    } elseif (is_array($roles) && in_array('professional', $roles, true)) {
        $perfil = 'profissional';
        $perfilLabel = 'Profissional';
    } elseif (is_array($roles) && in_array('finance', $roles, true)) {
        $perfil = 'financeiro';
        $perfilLabel = 'Financeiro';
    } else {
        $perfil = 'admin';
        $perfilLabel = 'Admin';
    }
}

$modules = [
    [
        'key' => 'primeiros-passos',
        'label' => 'Primeiros passos',
        'href' => '/tutorial/sistema/primeiros-passos',
        'perfils' => ['admin', 'recepcao', 'profissional', 'guest'],
        'desc' => 'Visão geral do menu, navegação e atalhos do sistema.',
    ],
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'href' => '/tutorial/sistema/dashboard',
        'perfils' => ['admin', 'recepcao', 'profissional', 'guest'],
        'desc' => 'Entenda indicadores, atalhos e leitura rápida do dia.',
    ],
    [
        'key' => 'agenda',
        'label' => 'Agenda',
        'href' => '/tutorial/sistema/agenda',
        'perfils' => ['admin', 'recepcao', 'profissional', 'guest'],
        'desc' => 'Criar, editar, cancelar e operar atendimentos.',
    ],
    [
        'key' => 'pacientes',
        'label' => 'Pacientes',
        'href' => '/tutorial/sistema/pacientes',
        'perfils' => ['admin', 'recepcao', 'profissional', 'guest'],
        'desc' => 'Cadastro, busca, histórico e portal do paciente.',
    ],
    [
        'key' => 'prontuarios',
        'label' => 'Prontuários',
        'href' => '/tutorial/sistema/prontuarios',
        'perfils' => ['admin', 'profissional'],
        'desc' => 'Registros clínicos, evoluções e documentos do atendimento.',
    ],
    [
        'key' => 'imagens',
        'label' => 'Imagens',
        'href' => '/tutorial/sistema/imagens',
        'perfils' => ['admin', 'profissional'],
        'desc' => 'Upload, comparação e organização de imagens (antes/depois).',
    ],
    [
        'key' => 'financeiro',
        'label' => 'Financeiro',
        'href' => '/tutorial/sistema/financeiro',
        'perfils' => ['admin', 'recepcao', 'financeiro'],
        'desc' => 'Vendas, pagamentos, caixa e relatórios.',
    ],
    [
        'key' => 'estoque',
        'label' => 'Estoque',
        'href' => '/tutorial/sistema/estoque',
        'perfils' => ['admin'],
        'desc' => 'Materiais, categorias, unidades, movimentações e alertas.',
    ],
    [
        'key' => 'servicos',
        'label' => 'Serviços',
        'href' => '/tutorial/sistema/servicos',
        'perfils' => ['admin'],
        'desc' => 'Cadastro de serviços e vinculação de materiais.',
    ],
    [
        'key' => 'profissionais',
        'label' => 'Profissionais',
        'href' => '/tutorial/sistema/profissionais',
        'perfils' => ['admin'],
        'desc' => 'Gestão de equipe, permissões e configurações.',
    ],
    [
        'key' => 'configuracoes',
        'label' => 'Configurações',
        'href' => '/tutorial/sistema/configuracoes',
        'perfils' => ['admin'],
        'desc' => 'Configurações gerais do sistema e da clínica.',
    ],
    [
        'key' => 'seguranca',
        'label' => 'Segurança e permissões',
        'href' => '/tutorial/sistema/seguranca',
        'perfils' => ['admin'],
        'desc' => 'Papéis, permissões, boas práticas e auditoria.',
    ],
    [
        'key' => 'portal-paciente',
        'label' => 'Portal do Paciente',
        'href' => '/tutorial/sistema/portal-paciente',
        'perfils' => ['admin', 'recepcao', 'profissional', 'guest', 'patient'],
        'desc' => 'Como funciona o portal e o que o paciente consegue ver.',
    ],
    [
        'key' => 'integracoes-api',
        'label' => 'Integrações e API',
        'href' => '/tutorial/sistema/integracoes-api',
        'perfils' => ['admin'],
        'desc' => 'Integrações, API Tokens e boas práticas.',
    ],
    [
        'key' => 'api-token-paciente',
        'label' => 'API Token (Paciente)',
        'href' => '/tutorial/api-tokens/paciente',
        'perfils' => ['patient', 'guest', 'admin'],
        'desc' => 'Como gerar e usar o API Token no Portal do Paciente.',
    ],
];

$seo = isset($seo) && is_array($seo) ? $seo : [];
$seoSiteName = trim((string)($seo['site_name'] ?? ''));
$seoDefaultTitle = trim((string)($seo['default_title'] ?? ''));
$seoDescription = trim((string)($seo['meta_description'] ?? ''));
$seoOgImageUrl = trim((string)($seo['og_image_url'] ?? ''));
$seoFaviconUrl = trim((string)($seo['favicon_url'] ?? ''));

$computedTitle = trim((string)($title ?? ''));
if ($computedTitle === '') {
    $computedTitle = $seoDefaultTitle !== '' ? $seoDefaultTitle : 'LumiClinic';
}
if ($seoSiteName !== '' && !str_contains($computedTitle, $seoSiteName)) {
    $computedTitle = $computedTitle . ' - ' . $seoSiteName;
}
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
<div class="lc-app" style="padding: 16px; max-width: 980px; margin: 0 auto;">
    <div class="lc-page__header" style="gap:10px;">
        <div class="lc-flex lc-gap-sm" style="align-items:center;">
            <div class="lc-brand__logo" style="width:36px; height:36px; padding:0; background:#000; border-radius:10px; overflow:hidden;">
                <img src="/icone_1.png" alt="LumiClinic" style="width:100%; height:100%; object-fit:contain; display:block;" />
            </div>
            <div>
                <div class="lc-page__title" style="margin:0;">Ajuda</div>
                <div class="lc-page__subtitle" style="margin-top:2px;">Tutorial (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/">Voltar para o sistema</a>
            <a class="lc-btn lc-btn--secondary" href="/portal">Portal do Paciente</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Como usar este tutorial</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Esta página é pública e serve como um passo a passo para você aprender a usar cada área do sistema.
            </div>
            <div style="margin-top:10px;">
                Use o índice abaixo para navegar pelas seções.
            </div>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Módulos</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div class="lc-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <?php foreach ($modules as $m): ?>
                    <?php if ($perfil !== 'admin' && !in_array($perfil, $m['perfils'], true)) continue; ?>
                    <a class="lc-card" href="<?= htmlspecialchars($m['href'], ENT_QUOTES, 'UTF-8') ?>" style="display:block; padding:14px; text-decoration:none;">
                        <div style="font-weight:800; color:#0f172a;">
                            <?= htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="lc-muted" style="margin-top:6px;">
                            <?= htmlspecialchars($m['desc'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>
