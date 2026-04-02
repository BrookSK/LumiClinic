<?php
$title = 'Tutorial do Sistema - Profissionais e Usuários';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Profissionais e Usuários (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/professionals">Abrir Profissionais</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visão geral</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Este módulo gerencia as pessoas que usam o sistema: <strong>profissionais de saúde</strong> (médicos, dentistas, esteticistas) e <strong>usuários operacionais</strong> (recepcionistas, financeiro, administradores).
            <br /><br />
            <strong>Quem usa:</strong> Admin (gestão completa de usuários e profissionais).
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Cadastrar um profissional</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Passo a passo:</strong>
            <br /><br />
            1. Acesse <strong>Profissionais</strong> no menu lateral.
            <br />2. Clique em <strong>"Novo profissional"</strong>.
            <br />3. Preencha os dados:
            <br />- <strong>Nome completo</strong>.
            <br />- <strong>E-mail</strong> — será usado para login no sistema.
            <br />- <strong>Telefone</strong>.
            <br />- <strong>Especialidade</strong> — área de atuação (ex: Dermatologia, Odontologia, Estética).
            <br />- <strong>Registro profissional</strong> — CRM, CRO, CREFITO, etc.
            <br />- <strong>Cor na agenda</strong> — cor que identifica o profissional no calendário.
            <br />4. <strong>Defina o papel (role)</strong> — geralmente "Profissional", mas pode ter papéis personalizados.
            <br />5. <strong>Defina a senha inicial</strong> — o profissional poderá alterar depois.
            <br />6. Clique em <strong>"Salvar"</strong>.
            <br /><br />
            <strong>Importante:</strong> Após cadastrar, o profissional já pode fazer login e acessar o sistema com as permissões do papel atribuído.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Cadastrar um usuário (não-profissional)</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Usuários operacionais (recepção, financeiro, admin) são cadastrados da mesma forma:
            <br /><br />
            1. Acesse <strong>Profissionais / Usuários</strong>.
            <br />2. Clique em <strong>"Novo usuário"</strong>.
            <br />3. Preencha nome, e-mail e telefone.
            <br />4. <strong>Atribua o papel correto:</strong>
            <br />- <strong>Admin</strong> — acesso total ao sistema.
            <br />- <strong>Recepção</strong> — foco em agendamentos e atendimento ao paciente.
            <br />- <strong>Financeiro</strong> — foco em vendas, caixa e relatórios financeiros.
            <br />- Ou um <strong>papel personalizado</strong> criado em Segurança > Papéis.
            <br />5. Defina a senha e salve.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Papéis e permissões (RBAC)</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Cada usuário tem um ou mais <strong>papéis (roles)</strong> que determinam o que ele pode ver e fazer no sistema:
            <br /><br />
            <strong>Papéis padrão do sistema:</strong>
            <br />- <strong>owner</strong> — Dono da clínica. Acesso total, não pode ser removido.
            <br />- <strong>admin</strong> — Administrador. Acesso total, pode gerenciar outros usuários.
            <br />- <strong>professional</strong> — Profissional de saúde. Acesso à agenda, prontuários e pacientes.
            <br />- <strong>reception</strong> — Recepção. Acesso à agenda, pacientes e operações do dia.
            <br />- <strong>finance</strong> — Financeiro. Acesso ao módulo financeiro e relatórios.
            <br /><br />
            <strong>Personalização:</strong> O admin pode criar novos papéis e ajustar permissões individuais em <strong>Segurança > Papéis & Permissões</strong>. Veja mais detalhes na seção de Segurança deste tutorial.
            <br /><br />
            <strong>Dica:</strong> Atribua sempre o papel com o menor nível de acesso necessário. Isso segue o princípio de menor privilégio e protege os dados da clínica.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Editar e desativar usuários</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Editar:</strong>
            <br />- Na lista de profissionais/usuários, clique no nome para abrir a edição.
            <br />- Altere os dados necessários e salve.
            <br />- Você pode trocar o papel, alterar a senha ou atualizar dados de contato.
            <br /><br />
            <strong>Desativar:</strong>
            <br />- Para remover o acesso de um usuário sem excluí-lo, desative-o.
            <br />- Usuários desativados não conseguem fazer login.
            <br />- Os registros históricos (prontuários, agendamentos) são preservados.
            <br /><br />
            <strong>Importante:</strong> Nunca exclua um profissional que já tem atendimentos registrados. Desative-o para manter o histórico íntegro.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Impacto na agenda</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Os profissionais cadastrados aparecem como opções na agenda:
            <br /><br />
            - Cada profissional tem sua <strong>coluna na visão diária</strong> da agenda.
            <br />- A <strong>cor do profissional</strong> identifica seus agendamentos no calendário.
            <br />- Os <strong>horários de atendimento</strong> do profissional podem ser configurados em Configurações > Horários de funcionamento.
            <br />- <strong>Bloqueios</strong> específicos podem ser criados para cada profissional (férias, folgas, etc.).
            <br /><br />
            <strong>Dica:</strong> Após cadastrar um profissional, configure seus horários de atendimento para que a agenda mostre corretamente os horários disponíveis.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>