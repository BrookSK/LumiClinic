<?php
$title = 'Ajuda do Paciente';

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

$isPatientLogged = isset($_SESSION['patient_user_id']) && (int)$_SESSION['patient_user_id'] > 0;
$patientName = isset($_SESSION['patient_name']) ? trim((string)$_SESSION['patient_name']) : '';

$modules = [
    ['label' => 'Visão geral do Portal', 'href' => '/tutorial/paciente/portal', 'desc' => 'Entenda o que é o portal e como navegar.'],
    ['label' => 'Busca', 'href' => '/tutorial/paciente/busca', 'desc' => 'Como pesquisar conteúdos e informações no portal.'],
    ['label' => 'Agenda', 'href' => '/tutorial/paciente/agenda', 'desc' => 'Como confirmar, cancelar e solicitar reagendamento.'],
    ['label' => 'Documentos', 'href' => '/tutorial/paciente/documentos', 'desc' => 'Como visualizar documentos e assinaturas.'],
    ['label' => 'Enviar fotos', 'href' => '/tutorial/paciente/uploads', 'desc' => 'Como enviar arquivos e acompanhar status.'],
    ['label' => 'Notificações', 'href' => '/tutorial/paciente/notificacoes', 'desc' => 'Como ler e marcar notificações como lidas.'],
    ['label' => 'Perfil', 'href' => '/tutorial/paciente/perfil', 'desc' => 'Como revisar seus dados e preferências.'],
    ['label' => 'Segurança', 'href' => '/tutorial/paciente/seguranca', 'desc' => 'Senha, recuperação e boas práticas.'],
    ['label' => 'LGPD', 'href' => '/tutorial/paciente/lgpd', 'desc' => 'Solicitações LGPD e privacidade.'],
    ['label' => 'API Tokens', 'href' => '/tutorial/paciente/api-tokens', 'desc' => 'Como criar, revogar e usar tokens.'],
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
<div class="lc-app" style="padding: 16px; max-width: 980px; margin: 0 auto;">
    <div class="lc-page__header" style="gap:10px;">
        <div class="lc-flex lc-gap-sm" style="align-items:center;">
            <div class="lc-brand__logo" style="width:36px; height:36px; padding:0; background:#000; border-radius:10px; overflow:hidden;">
                <img src="/icone_1.png" alt="LumiClinic" style="width:100%; height:100%; object-fit:contain; display:block;" />
            </div>
            <div>
                <div class="lc-page__title" style="margin:0;">Ajuda</div>
                <div class="lc-page__subtitle" style="margin-top:2px;">Portal do Paciente<?= ($isPatientLogged && $patientName !== '') ? (': ' . htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8')) : '' ?></div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/portal">Ir para o Portal</a>
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Ajuda do Sistema</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Como usar esta central</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Este tutorial explica as funções do <strong>Portal do Paciente</strong>.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Módulos</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div class="lc-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <?php foreach ($modules as $m): ?>
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
