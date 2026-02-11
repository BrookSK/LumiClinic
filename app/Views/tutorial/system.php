<?php
$title = 'Tutorial do Sistema';

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
                <div class="lc-page__subtitle" style="margin-top:2px;">Tutorial completo do sistema</div>
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
        <div class="lc-card__title">Índice</div>
        <div class="lc-card__body" style="line-height:1.9;">
            <a href="#primeiros-passos">1) Primeiros passos</a><br />
            <a href="#dashboard">2) Dashboard</a><br />
            <a href="#agenda">3) Agenda</a><br />
            <a href="#pacientes">4) Pacientes</a><br />
            <a href="#prontuarios">5) Prontuários</a><br />
            <a href="#imagens">6) Imagens</a><br />
            <a href="#financeiro">7) Financeiro</a><br />
            <a href="#estoque">8) Estoque</a><br />
            <a href="#servicos">9) Serviços</a><br />
            <a href="#profissionais">10) Profissionais</a><br />
            <a href="#configuracoes">11) Configurações</a><br />
            <a href="#seguranca">12) Segurança e permissões</a><br />
            <a href="#portal">13) Portal do Paciente</a><br />
            <a href="#integracoes">14) Integrações e API</a><br />
            <a href="#faq">15) Dúvidas comuns</a>
        </div>
    </div>

    <div class="lc-card" id="primeiros-passos" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">1) Primeiros passos</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                - No menu lateral, você acessa todas as áreas do sistema.
                <br />- No topo, use a <strong>Busca rápida</strong> para encontrar informações rapidamente.
                <br />- No canto superior direito, o menu do usuário permite <strong>Sair</strong>.
            </div>
        </div>
    </div>

    <div class="lc-card" id="dashboard" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">2) Dashboard</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                O Dashboard reúne indicadores e atalhos para o seu dia a dia.
            </div>
        </div>
    </div>

    <div class="lc-card" id="agenda" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">3) Agenda</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Na Agenda você cria, visualiza e gerencia atendimentos.
            </div>
        </div>
    </div>

    <div class="lc-card" id="pacientes" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">4) Pacientes</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                O cadastro de pacientes centraliza dados pessoais, histórico e acesso ao Portal.
            </div>
        </div>
    </div>

    <div class="lc-card" id="prontuarios" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">5) Prontuários</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Prontuários organizam registros clínicos e evoluções.
            </div>
        </div>
    </div>

    <div class="lc-card" id="imagens" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">6) Imagens</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Armazene e compare imagens do paciente (antes/depois), com controle de visibilidade.
            </div>
        </div>
    </div>

    <div class="lc-card" id="financeiro" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">7) Financeiro</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Controle vendas, caixa, pagamentos e relatórios.
            </div>
        </div>
    </div>

    <div class="lc-card" id="estoque" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">8) Estoque</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Gerencie materiais, categorias, unidades, movimentações e alertas.
            </div>
        </div>
    </div>

    <div class="lc-card" id="servicos" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">9) Serviços</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Cadastre serviços e vincule materiais.
            </div>
        </div>
    </div>

    <div class="lc-card" id="profissionais" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">10) Profissionais</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Controle profissionais, permissões e disponibilidade.
            </div>
        </div>
    </div>

    <div class="lc-card" id="configuracoes" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">11) Configurações</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Ajuste informações da clínica, SEO, e-mail e demais preferências.
            </div>
        </div>
    </div>

    <div class="lc-card" id="seguranca" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">12) Segurança e permissões</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Papéis e permissões controlam o que cada usuário pode ver e fazer.
            </div>
        </div>
    </div>

    <div class="lc-card" id="portal" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">13) Portal do Paciente</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                O Portal permite que o paciente acesse agenda, documentos, notificações e outros recursos.
            </div>
        </div>
    </div>

    <div class="lc-card" id="integracoes" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">14) Integrações e API</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                Para integrações por API, use os tutoriais específicos e boas práticas de segurança.
            </div>
        </div>
    </div>

    <div class="lc-card" id="faq" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">15) Dúvidas comuns</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <div>
                - Se algo não aparecer para você, pode ser falta de permissão.
                <br />- Se a clínica não estiver selecionada/contextualizada, algumas áreas ficam ocultas.
            </div>
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>
