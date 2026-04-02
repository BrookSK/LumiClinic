<?php
$title = 'Tutorial do Sistema - Segurança e permissões';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Segurança e permissões (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/rbac">Abrir Papéis & Permissões</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">O que é RBAC (Controle de Acesso Baseado em Papéis)</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O sistema usa <strong>RBAC (Role-Based Access Control)</strong> para controlar quem pode ver e fazer o quê. Cada usuário recebe um ou mais <strong>papéis (roles)</strong>, e cada papel tem um conjunto de <strong>permissões</strong> que determinam o acesso aos módulos e ações do sistema.
            <br /><br />
            <strong>Quem usa:</strong> Admin (gestão de papéis e permissões).
            <br /><br />
            <strong>Conceitos-chave:</strong>
            <br />- <strong>Papel (Role)</strong> — um conjunto nomeado de permissões (ex: "Recepção", "Profissional").
            <br />- <strong>Permissão</strong> — uma ação específica em um módulo (ex: "pacientes.criar", "financeiro.visualizar").
            <br />- <strong>Permitir (Allow)</strong> — o usuário pode executar a ação.
            <br />- <strong>Negar (Deny)</strong> — o usuário é explicitamente impedido de executar a ação (tem prioridade sobre Permitir).
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Papéis padrão do sistema</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O sistema vem com papéis pré-configurados que cobrem os cenários mais comuns:
            <br /><br />
            <strong>👑 Owner (Dono)</strong>
            <br />- Acesso total e irrestrito.
            <br />- Não pode ser editado ou removido.
            <br />- Apenas um por clínica.
            <br /><br />
            <strong>🔧 Admin (Administrador)</strong>
            <br />- Acesso a todos os módulos.
            <br />- Pode gerenciar usuários, configurações e permissões.
            <br />- Permissões podem ser ajustadas.
            <br /><br />
            <strong>👨‍⚕️ Professional (Profissional)</strong>
            <br />- Agenda (própria), prontuários, pacientes, imagens médicas.
            <br />- Não acessa financeiro, estoque ou configurações avançadas.
            <br /><br />
            <strong>🏥 Reception (Recepção)</strong>
            <br />- Agenda (todos os profissionais), pacientes, operações do dia.
            <br />- Acesso limitado ao financeiro (registrar pagamentos).
            <br />- Não acessa prontuários clínicos.
            <br /><br />
            <strong>💰 Finance (Financeiro)</strong>
            <br />- Vendas, caixa, contas a pagar, relatórios financeiros.
            <br />- Não acessa prontuários ou configurações.
            <br /><br />
            <strong>Nota:</strong> Papéis padrão marcados como "somente leitura" não podem ter suas permissões alteradas. Para personalizar, clone o papel e edite a cópia.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Como editar permissões de um papel</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Passo a passo:</strong>
            <br /><br />
            1. Acesse <strong>Segurança > Papéis & Permissões</strong>.
            <br />2. Clique no papel que deseja editar.
            <br />3. Você verá as permissões organizadas por <strong>módulo</strong> (Agenda, Pacientes, Financeiro, etc.).
            <br />4. Para cada permissão, você pode:
            <br />- Marcar <strong>"Permitir"</strong> — o usuário com esse papel poderá executar a ação.
            <br />- Marcar <strong>"Negar"</strong> — o usuário será impedido, mesmo que outra regra permita.
            <br />- Deixar <strong>desmarcado</strong> — a permissão não é concedida (equivale a negar).
            <br />5. Clique em <strong>"Salvar permissões"</strong>.
            <br /><br />
            <strong>Atalhos:</strong>
            <br />- <strong>"Permitir tudo"</strong> — marca todas as permissões como permitidas.
            <br />- <strong>"Limpar tudo"</strong> — remove todas as marcações.
            <br /><br />
            <strong>Módulos disponíveis:</strong>
            <br />Agenda, Pacientes, Prontuários, Imagens Clínicas, Anamnese, Consentimento, Financeiro, Estoque, Marketing, Permissões, Usuários, Configurações, Clínica, Auditoria, Compliance, Relatórios, Procedimentos, Regras de Agenda, Profissionais, Bloqueios, Serviços, Modelos de Prontuário.
            <br /><br />
            <strong>Ações por módulo:</strong>
            <br />- <strong>Visualizar</strong> — ver dados e listagens.
            <br />- <strong>Criar</strong> — adicionar novos registros.
            <br />- <strong>Editar</strong> — alterar registros existentes.
            <br />- <strong>Excluir</strong> — remover registros.
            <br />- <strong>Gerenciar</strong> — ações administrativas especiais.
            <br />- <strong>Operações</strong> — ações operacionais (ex: confirmar agendamento).
            <br />- <strong>Finalizar</strong> — concluir registros (ex: finalizar prontuário).
            <br />- <strong>Estornar</strong> — reverter operações financeiras.
            <br />- <strong>Exportar</strong> — exportar dados e relatórios.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Criar um novo papel</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Você pode criar papéis personalizados para atender necessidades específicas:
            <br /><br />
            <strong>Opção 1 — Clonar um papel existente:</strong>
            <br />1. Abra o papel que deseja usar como base.
            <br />2. Role até o final e clique em <strong>"Clonar este papel"</strong>.
            <br />3. Informe o nome do novo papel (ex: "Enfermeiro", "Estagiário").
            <br />4. Clique em "Clonar".
            <br />5. O novo papel é criado com as mesmas permissões do original.
            <br />6. Edite as permissões conforme necessário.
            <br /><br />
            <strong>Opção 2 — Criar do zero:</strong>
            <br />1. Na lista de papéis, clique em "Novo papel".
            <br />2. Informe o nome.
            <br />3. Configure cada permissão individualmente.
            <br />4. Salve.
            <br /><br />
            <strong>Dica:</strong> Clonar é mais rápido quando o novo papel é parecido com um existente. Basta ajustar as diferenças.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Resetar permissões</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Se as permissões de um papel ficaram confusas, você pode resetar para o padrão:
            <br /><br />
            1. Abra o papel desejado.
            <br />2. Role até o final e clique em <strong>"Resetar para padrão"</strong>.
            <br />3. Confirme a ação.
            <br />4. Todas as permissões voltam ao estado original do sistema.
            <br /><br />
            <strong>Atenção:</strong> Essa ação não pode ser desfeita. Todas as personalizações serão perdidas.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Boas práticas de segurança</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>1. Princípio do menor privilégio</strong>
            <br />- Dê a cada usuário apenas as permissões necessárias para seu trabalho.
            <br />- Evite dar acesso de Admin para todos.
            <br /><br />
            <strong>2. Revise permissões periodicamente</strong>
            <br />- Quando um funcionário muda de função, atualize seu papel.
            <br />- Desative usuários que saíram da clínica.
            <br /><br />
            <strong>3. Use papéis específicos</strong>
            <br />- Em vez de ajustar permissões individualmente, crie papéis para cada função.
            <br />- Exemplo: "Enfermeiro" com acesso a prontuários mas sem acesso ao financeiro.
            <br /><br />
            <strong>4. Proteja dados sensíveis</strong>
            <br />- Prontuários e dados financeiros devem ter acesso restrito.
            <br />- Use a permissão "Negar" para bloquear explicitamente o acesso a módulos sensíveis.
            <br /><br />
            <strong>5. Senhas seguras</strong>
            <br />- Oriente todos os usuários a usar senhas fortes.
            <br />- Troque senhas periodicamente.
            <br />- Nunca compartilhe credenciais entre usuários.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Solução de problemas comuns</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>"Um botão ou menu não aparece para o usuário"</strong>
            <br />- Verifique se o papel do usuário tem a permissão correspondente.
            <br />- Lembre-se: se a permissão não está marcada como "Permitir", o acesso é negado.
            <br /><br />
            <strong>"O usuário não consegue fazer login"</strong>
            <br />- Verifique se o usuário está ativo (não desativado).
            <br />- Verifique se o e-mail e senha estão corretos.
            <br />- Verifique se o usuário está vinculado à clínica correta.
            <br /><br />
            <strong>"Preciso dar acesso temporário"</strong>
            <br />- Crie um papel específico com as permissões necessárias.
            <br />- Atribua ao usuário temporariamente.
            <br />- Após o período, troque o papel de volta ou desative o usuário.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>