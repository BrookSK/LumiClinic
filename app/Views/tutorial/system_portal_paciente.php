<?php
$title = 'Tutorial do Sistema - Portal do Paciente';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Portal do Paciente (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/portal">Abrir Portal</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">O que é o Portal do Paciente</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O <strong>Portal do Paciente</strong> é uma área exclusiva onde o paciente pode acessar suas informações de forma autônoma, sem precisar ligar para a clínica. É uma interface separada do sistema principal, com login próprio.
            <br /><br />
            <strong>Quem gerencia:</strong> Admin, Recepção e Profissional (cada um com ações diferentes).
            <br /><br />
            <strong>Benefícios:</strong>
            <br />- Reduz ligações e mensagens para a recepção.
            <br />- O paciente tem acesso 24h às suas informações.
            <br />- Melhora a experiência e satisfação do paciente.
            <br />- Facilita o preenchimento de anamneses e assinatura de documentos.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Como ativar o acesso do paciente</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Pré-requisito:</strong> O paciente precisa ter um <strong>e-mail cadastrado</strong> no sistema.
            <br /><br />
            <strong>Passo a passo:</strong>
            <br />1. Acesse a <strong>ficha do paciente</strong>.
            <br />2. Procure a seção <strong>"Acesso ao Portal"</strong>.
            <br />3. Clique em <strong>"Ativar acesso"</strong>.
            <br />4. O sistema gera credenciais de acesso.
            <br />5. Um e-mail é enviado ao paciente com o link do portal e instruções de primeiro acesso.
            <br />6. O paciente define sua senha no primeiro login.
            <br /><br />
            <strong>Dica:</strong> Você também pode informar o link do portal verbalmente ou por WhatsApp. O paciente pode se cadastrar diretamente pelo portal se essa opção estiver habilitada.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">O que o paciente vê no portal</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Ao fazer login, o paciente tem acesso às seguintes áreas:
            <br /><br />
            <strong>📊 Dashboard</strong>
            <br />- Resumo dos próximos agendamentos.
            <br />- Notificações pendentes.
            <br />- Atalhos para as principais ações.
            <br /><br />
            <strong>📅 Agenda</strong>
            <br />- Visualização dos agendamentos futuros e passados.
            <br />- Detalhes de cada consulta (data, hora, profissional, serviço).
            <br />- Possibilidade de confirmar presença (se habilitado).
            <br /><br />
            <strong>📄 Documentos</strong>
            <br />- Termos de consentimento para assinatura digital.
            <br />- Documentos compartilhados pela clínica.
            <br />- Prescrições e orientações.
            <br /><br />
            <strong>📋 Anamnese</strong>
            <br />- Questionários de saúde para preenchimento.
            <br />- Anamneses já preenchidas (histórico).
            <br /><br />
            <strong>🔔 Notificações</strong>
            <br />- Lembretes de consulta.
            <br />- Avisos da clínica.
            <br />- Solicitações de preenchimento de anamnese ou assinatura de documentos.
            <br /><br />
            <strong>👤 Perfil</strong>
            <br />- Dados pessoais (nome, telefone, e-mail, endereço).
            <br />- O paciente pode solicitar alterações nos seus dados.
            <br />- Alteração de senha.
            <br /><br />
            <strong>🖼️ Uploads</strong>
            <br />- Envio de fotos e documentos para a clínica.
            <br />- As imagens enviadas passam por moderação antes de serem adicionadas ao prontuário.
            <br /><br />
            <strong>🔒 Segurança e LGPD</strong>
            <br />- Visualização dos termos de privacidade.
            <br />- Gerenciamento de consentimentos.
            <br />- Solicitação de exclusão de dados (direito LGPD).
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Notificações para o paciente</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O sistema pode enviar notificações ao paciente por diferentes canais:
            <br /><br />
            - <strong>E-mail</strong> — confirmações, lembretes e documentos.
            <br />- <strong>WhatsApp</strong> — lembretes de consulta, links de anamnese (se integração ativa).
            <br />- <strong>Portal</strong> — notificações internas visíveis ao fazer login.
            <br />- <strong>Push</strong> — notificações no navegador (se o paciente autorizar).
            <br /><br />
            <strong>Tipos de notificação:</strong>
            <br />- Lembrete de consulta (1 dia antes, 1 hora antes).
            <br />- Solicitação de preenchimento de anamnese.
            <br />- Documento disponível para assinatura.
            <br />- Confirmação de agendamento.
            <br />- Alteração ou cancelamento de consulta.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Gerenciamento pelo admin/recepção</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Do lado do sistema (admin/recepção), você pode:
            <br /><br />
            - <strong>Ativar/desativar</strong> o acesso ao portal de cada paciente.
            <br />- <strong>Reenviar credenciais</strong> caso o paciente perca o acesso.
            <br />- <strong>Ver o status</strong> do portal (ativo, pendente, nunca acessou).
            <br />- <strong>Moderar uploads</strong> — aprovar ou rejeitar imagens enviadas pelo paciente.
            <br />- <strong>Enviar documentos</strong> — compartilhar termos e prescrições pelo portal.
            <br /><br />
            <strong>Dica:</strong> Incentive os pacientes a usar o portal. Isso reduz a carga de trabalho da recepção e melhora a comunicação com o paciente.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>