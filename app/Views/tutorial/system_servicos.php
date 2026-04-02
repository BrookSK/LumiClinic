<?php
$title = 'Tutorial do Sistema - Serviços';
$perfilLabel = 'Geral';
if (isset($_SESSION['patient_user_id']) && (int)$_SESSION['patient_user_id'] > 0) {
    $perfilLabel = 'Paciente';
} elseif (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
    $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
    $roles = $_SESSION['role_codes'] ?? [];

    if ($isSuperAdmin) {
        $perfilLabel = 'Admin';
    } elseif (is_array($roles) && (in_array('owner', $roles, true) || in_array('admin', $roles, true))) {
        $perfilLabel = 'Admin';
    } elseif (is_array($roles) && in_array('reception', $roles, true)) {
        $perfilLabel = 'Recepção';
    } elseif (is_array($roles) && in_array('professional', $roles, true)) {
        $perfilLabel = 'Profissional';
    } elseif (is_array($roles) && in_array('finance', $roles, true)) {
        $perfilLabel = 'Recepção';
    } else {
        $perfilLabel = 'Admin';
    }
}

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
                <div class="lc-page__subtitle" style="margin-top:2px;">Serviços (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/services">Abrir Serviços</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">O que são Serviços</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Serviços</strong> são o catálogo de procedimentos, consultas e atendimentos oferecidos pela clínica. Eles são a base para agendamentos e faturamento — ao criar um agendamento, você seleciona qual serviço será realizado, e o valor é usado para gerar a venda.
            <br /><br />
            <strong>Quem usa:</strong> Admin (cadastro e gestão). Recepção e Profissional usam os serviços ao agendar e atender.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Cadastrar um serviço</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Passo a passo:</strong>
            <br /><br />
            1. Acesse <strong>Serviços</strong> no menu lateral.
            <br />2. Clique em <strong>"Novo serviço"</strong>.
            <br />3. Preencha os campos:
            <br />- <strong>Nome</strong> — nome do serviço (ex: "Consulta inicial", "Limpeza de pele", "Aplicação de botox").
            <br />- <strong>Categoria</strong> — grupo ao qual pertence (ex: Consultas, Estética facial, Odontologia).
            <br />- <strong>Duração</strong> — tempo estimado do atendimento em minutos. Isso define o tamanho do bloco na agenda.
            <br />- <strong>Valor</strong> — preço do serviço. Usado como base para o faturamento.
            <br />- <strong>Descrição</strong> (opcional) — detalhes sobre o serviço.
            <br />- <strong>Status</strong> — ativo ou inativo. Serviços inativos não aparecem nas opções de agendamento.
            <br />4. Clique em <strong>"Salvar"</strong>.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Categorias de serviço</div>
        <div class="lc-card__body" style="line-height:1.6;">
            As categorias organizam os serviços em grupos, facilitando a busca e os relatórios:
            <br /><br />
            <strong>Exemplos de categorias:</strong>
            <br />- Consultas (consulta inicial, retorno, avaliação).
            <br />- Estética facial (limpeza de pele, peeling, botox, preenchimento).
            <br />- Estética corporal (drenagem, criolipólise, radiofrequência).
            <br />- Odontologia (profilaxia, restauração, clareamento).
            <br /><br />
            <strong>Criar uma categoria:</strong>
            <br />1. Acesse <strong>Serviços > Categorias</strong>.
            <br />2. Clique em <strong>"Nova categoria"</strong>.
            <br />3. Informe o nome e salve.
            <br /><br />
            <strong>Dica:</strong> Crie as categorias antes de cadastrar os serviços. Assim, ao criar cada serviço, você já pode associá-lo à categoria correta.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Procedimentos</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Além dos serviços, o sistema permite cadastrar <strong>procedimentos</strong> que detalham as etapas técnicas de um atendimento:
            <br /><br />
            - Procedimentos podem ser vinculados a serviços.
            <br />- Útil para clínicas que precisam detalhar o que foi feito em cada atendimento (ex: para fins de auditoria ou convênio).
            <br />- Cada procedimento pode ter um código, descrição e valor específico.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Vinculação de materiais</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Serviços podem ter <strong>materiais do estoque vinculados</strong>, permitindo controle automático de consumo:
            <br /><br />
            1. Edite o serviço desejado.
            <br />2. Na seção <strong>"Materiais vinculados"</strong>, clique em "Adicionar material".
            <br />3. Selecione o material do estoque.
            <br />4. Informe a <strong>quantidade consumida por atendimento</strong>.
            <br />5. Salve.
            <br /><br />
            <strong>Benefícios:</strong>
            <br />- O estoque é atualizado automaticamente ao concluir atendimentos.
            <br />- Você sabe exatamente o custo de materiais de cada serviço.
            <br />- Relatórios mostram o consumo real versus o previsto.
            <br /><br />
            <strong>Exemplo:</strong> O serviço "Preenchimento labial" consome 1 seringa de ácido hialurônico. Ao vincular, cada atendimento desse serviço desconta automaticamente 1 unidade do estoque.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Impacto dos serviços no sistema</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Os serviços são usados em vários módulos do sistema:
            <br /><br />
            - <strong>Agenda</strong> — ao criar um agendamento, você seleciona o serviço. A duração define o tamanho do bloco.
            <br />- <strong>Financeiro</strong> — o valor do serviço é usado como base para vendas e orçamentos.
            <br />- <strong>Estoque</strong> — materiais vinculados são consumidos automaticamente.
            <br />- <strong>Relatórios</strong> — faturamento por serviço, serviços mais realizados, etc.
            <br /><br />
            <strong>Importante:</strong> Cadastre os serviços antes de começar a agendar. Sem serviços cadastrados, não é possível criar agendamentos.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>