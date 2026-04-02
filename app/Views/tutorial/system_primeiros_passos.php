<?php
$title = 'Tutorial do Sistema - Primeiros passos';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Primeiros passos (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/">Ir para o sistema</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Bem-vindo ao LumiClinic</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O LumiClinic é um sistema completo para gestão de clínicas. Nesta página você vai aprender a navegar pelo sistema, entender a estrutura dos menus e conhecer os atalhos que vão agilizar o seu dia a dia.
            <br /><br />
            <strong>Este guia é para todos os perfis</strong> — Admin, Recepção, Profissional e Financeiro. Cada perfil verá menus e opções diferentes de acordo com suas permissões.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Estrutura do menu lateral (sidebar)</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O menu lateral à esquerda é o ponto central de navegação. Ele organiza todos os módulos do sistema em grupos:
            <br /><br />
            <strong>📊 Dashboard</strong> — Visão geral com indicadores do dia (agendamentos, faturamento, pendências).
            <br /><strong>📅 Agenda</strong> — Calendário de agendamentos, visão de operações (Recepção) e fila do profissional.
            <br /><strong>👥 Pacientes</strong> — Cadastro, busca, ficha clínica, prontuários, documentos e timeline.
            <br /><strong>📋 Prontuários</strong> — Registros clínicos dos atendimentos.
            <br /><strong>🖼️ Imagens</strong> — Galeria de imagens médicas com comparador antes/depois.
            <br /><strong>💰 Financeiro</strong> — Orçamentos, vendas, caixa, contas a pagar e relatórios.
            <br /><strong>📦 Estoque</strong> — Materiais, movimentações, categorias e alertas de estoque mínimo.
            <br /><strong>🔧 Serviços</strong> — Catálogo de procedimentos e serviços da clínica.
            <br /><strong>👨‍⚕️ Profissionais</strong> — Gestão de profissionais e usuários do sistema.
            <br /><strong>⚙️ Configurações</strong> — Ajustes gerais, integrações, horários e regras.
            <br /><strong>🔒 Segurança</strong> — Papéis e permissões (RBAC).
            <br /><br />
            <strong>Dica:</strong> Alguns itens possuem submenus — clique na seta para expandir e ver as opções internas.
            <br /><br />
            <strong>Importante:</strong> Os itens do menu aparecem de acordo com as permissões do seu perfil. Se um módulo não aparece para você, é porque seu papel não tem acesso a ele. Fale com o administrador da clínica se precisar de acesso.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Recolher e expandir o menu lateral</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O menu lateral pode ser recolhido para ganhar mais espaço na tela:
            <br /><br />
            1. Clique no ícone de <strong>menu (☰)</strong> no topo da sidebar para recolher.
            <br />2. Quando recolhido, apenas os ícones ficam visíveis.
            <br />3. Clique novamente para expandir e ver os nomes dos módulos.
            <br /><br />
            <strong>Em dispositivos móveis:</strong> O menu lateral funciona como um painel deslizante. Toque no ícone de menu no cabeçalho para abrir e fechar.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Busca rápida</div>
        <div class="lc-card__body" style="line-height:1.6;">
            No topo da página existe um campo de <strong>busca rápida</strong> que permite encontrar informações rapidamente:
            <br /><br />
            <strong>Como usar:</strong>
            <br />1. Clique no campo de busca no cabeçalho (ou use o atalho de teclado).
            <br />2. Digite o nome do paciente, telefone, CPF ou termo que deseja buscar.
            <br />3. Pressione <strong>Enter</strong> para executar a busca.
            <br />4. Os resultados aparecem filtrados de acordo com o seu perfil e contexto.
            <br /><br />
            <strong>O que você pode buscar:</strong>
            <br />- <strong>Pacientes</strong> — por nome, CPF, telefone ou e-mail.
            <br />- <strong>Agendamentos</strong> — por nome do paciente ou profissional.
            <br />- <strong>Módulos</strong> — digite o nome do módulo para navegar rapidamente (ex: "financeiro", "estoque").
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Cabeçalho e menu do usuário</div>
        <div class="lc-card__body" style="line-height:1.6;">
            No canto superior direito você encontra o <strong>menu do usuário</strong>:
            <br /><br />
            - <strong>Nome e perfil</strong> — mostra quem está logado e qual o papel atual.
            <br />- <strong>Minha conta</strong> — acesse para alterar senha, foto e dados pessoais.
            <br />- <strong>Trocar clínica</strong> — se você tem acesso a mais de uma clínica, pode alternar entre elas.
            <br />- <strong>Ajuda</strong> — abre este tutorial do sistema.
            <br />- <strong>Sair</strong> — encerra a sessão de forma segura.
            <br /><br />
            <strong>Dica de segurança:</strong> Sempre clique em "Sair" ao terminar de usar o sistema, especialmente em computadores compartilhados.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Atalhos de teclado</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O sistema oferece atalhos de teclado para agilizar a navegação:
            <br /><br />
            - <strong>/</strong> ou <strong>Ctrl + K</strong> — Foca no campo de busca rápida.
            <br />- <strong>Esc</strong> — Fecha modais, painéis e menus abertos.
            <br />- <strong>Enter</strong> — Confirma ações em formulários e diálogos.
            <br /><br />
            <strong>Dica:</strong> Dentro de formulários, use <strong>Tab</strong> para navegar entre os campos e <strong>Shift + Tab</strong> para voltar ao campo anterior.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Checklist para configurar sua clínica (Admin / Dono)</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Se você é o administrador ou dono da clínica, siga este passo a passo para deixar tudo funcionando:
            <br /><br />
            <strong>1. Configurações da agenda</strong>
            <br />- Acesse <strong>Configurações</strong> e defina os <strong>horários de funcionamento</strong> da clínica.
            <br />- Cadastre <strong>feriados e dias fechados</strong> para que a agenda não mostre horários nesses dias.
            <br />- Configure <strong>regras de agendamento</strong> (antecedência mínima, intervalo entre consultas).
            <br />- Defina <strong>bloqueios</strong> para horários específicos que não devem receber agendamentos.
            <br /><br />
            <strong>2. Cadastro de pessoas e acessos</strong>
            <br />- Crie os <strong>usuários</strong> do sistema (recepcionistas, financeiro, outros admins).
            <br />- Cadastre os <strong>profissionais</strong> (médicos, dentistas, esteticistas, etc.).
            <br />- Revise as <strong>permissões</strong> de cada papel em Segurança > Papéis & Permissões.
            <br /><br />
            <strong>3. Cadastros essenciais</strong>
            <br />- Cadastre os <strong>serviços</strong> oferecidos pela clínica (consultas, procedimentos, exames).
            <br />- Cadastre os <strong>materiais</strong> do estoque, se aplicável.
            <br />- Vincule materiais aos serviços para controle automático de consumo.
            <br /><br />
            <strong>4. Integrações (opcional)</strong>
            <br />- Configure o <strong>WhatsApp</strong> para envio de confirmações e lembretes automáticos.
            <br />- Conecte o <strong>Google Calendar</strong> para sincronizar a agenda dos profissionais.
            <br />- Configure a <strong>Inteligência Artificial</strong> (chave OpenAI) para transcrição de áudio em prontuários.
            <br /><br />
            <strong>5. Primeiro agendamento</strong>
            <br />- Cadastre um <strong>paciente</strong> (ou faça isso na hora do agendamento).
            <br />- Vá em <strong>Agenda</strong> e crie o primeiro <strong>agendamento</strong>.
            <br />- Teste o fluxo completo: agendar → confirmar → atender → finalizar.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Entendendo os perfis do sistema</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O sistema possui perfis pré-definidos que determinam o que cada usuário pode ver e fazer:
            <br /><br />
            <strong>👑 Admin / Dono</strong> — Acesso total. Pode configurar a clínica, gerenciar usuários, ver relatórios financeiros e acessar todos os módulos.
            <br /><br />
            <strong>🏥 Recepção</strong> — Foco no atendimento ao paciente. Pode agendar consultas, cadastrar pacientes, operar a agenda do dia e registrar pagamentos.
            <br /><br />
            <strong>👨‍⚕️ Profissional</strong> — Foco no atendimento clínico. Vê sua própria agenda, acessa prontuários dos seus pacientes, registra evoluções e gerencia imagens médicas.
            <br /><br />
            <strong>💰 Financeiro</strong> — Foco na gestão financeira. Acessa vendas, caixa, contas a pagar e relatórios financeiros.
            <br /><br />
            <strong>Nota:</strong> Esses perfis podem ser personalizados pelo administrador em <strong>Segurança > Papéis & Permissões</strong>. É possível criar novos papéis ou ajustar as permissões dos existentes.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Dicas gerais de uso</div>
        <div class="lc-card__body" style="line-height:1.6;">
            - <strong>Notificações:</strong> Fique atento aos alertas e badges no menu — eles indicam pendências como agendamentos não confirmados ou estoque baixo.
            <br />- <strong>Formulários:</strong> Campos com asterisco (*) são obrigatórios. O sistema valida os dados antes de salvar.
            <br />- <strong>Tabelas:</strong> A maioria das listagens permite ordenar por colunas clicando no cabeçalho. Use os filtros disponíveis para refinar os resultados.
            <br />- <strong>Ações em lote:</strong> Em algumas telas (como pacientes e agendamentos), você pode selecionar vários itens e aplicar ações em lote.
            <br />- <strong>Responsividade:</strong> O sistema funciona em tablets e celulares. A experiência é otimizada para telas maiores, mas todas as funcionalidades estão disponíveis em dispositivos móveis.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>
