<?php
$title = 'Tutorial do Sistema - Pacientes';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Pacientes (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/patients">Abrir Pacientes</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visão geral do módulo de Pacientes</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O módulo de Pacientes centraliza todas as informações dos pacientes da clínica. Aqui você cadastra novos pacientes, consulta históricos, acessa prontuários, gerencia documentos e muito mais.
            <br /><br />
            <strong>Quem usa:</strong> Admin, Recepção e Profissional — cada um com permissões diferentes.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Lista de pacientes e busca</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Ao acessar <strong>Pacientes</strong> no menu, você vê a lista completa de pacientes cadastrados:
            <br /><br />
            - <strong>Busca:</strong> Use o campo de busca para encontrar pacientes por nome, CPF, telefone ou e-mail.
            <br />- <strong>Ordenação:</strong> Clique nos cabeçalhos das colunas para ordenar por nome, data de cadastro, etc.
            <br />- <strong>Filtros:</strong> Filtre por status (ativo/inativo), origem, ou outros critérios disponíveis.
            <br />- <strong>Ações:</strong> Cada linha tem botões para editar, ver detalhes ou acessar a ficha clínica.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Cadastrar um novo paciente</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Passo a passo:</strong>
            <br /><br />
            1. Clique no botão <strong>"Novo paciente"</strong> na lista de pacientes.
            <br /><br />
            2. Preencha os dados obrigatórios:
            <br />- <strong>Nome completo</strong>
            <br />- <strong>Telefone</strong> (usado para WhatsApp e contato)
            <br />- <strong>CPF</strong> (opcional, mas recomendado para identificação única)
            <br />- <strong>E-mail</strong> (necessário se quiser ativar o Portal do Paciente)
            <br />- <strong>Data de nascimento</strong>
            <br /><br />
            3. Preencha dados complementares (opcional):
            <br />- Endereço, gênero, profissão, como conheceu a clínica (origem).
            <br /><br />
            4. Clique em <strong>"Salvar"</strong>.
            <br /><br />
            <strong>Dica:</strong> Você também pode cadastrar pacientes diretamente na tela de agendamento, sem precisar sair da agenda.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Ficha clínica</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A <strong>Ficha Clínica</strong> é a página central do paciente. Ela reúne todas as informações em um só lugar:
            <br /><br />
            - <strong>Dados pessoais</strong> — nome, contato, CPF, data de nascimento.
            <br />- <strong>Histórico de agendamentos</strong> — todas as consultas passadas e futuras.
            <br />- <strong>Prontuários</strong> — registros clínicos de cada atendimento.
            <br />- <strong>Prescrições</strong> — receitas e orientações emitidas.
            <br />- <strong>Imagens médicas</strong> — fotos e exames de imagem.
            <br />- <strong>Documentos</strong> — termos, consentimentos e outros documentos.
            <br />- <strong>Anamnese</strong> — questionários de saúde preenchidos.
            <br /><br />
            <strong>Como acessar:</strong> Na lista de pacientes, clique no nome do paciente ou no botão "Ficha clínica".
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Timeline do paciente</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A <strong>Timeline</strong> mostra todo o histórico de interações do paciente com a clínica em ordem cronológica:
            <br /><br />
            - Agendamentos criados, confirmados, cancelados.
            <br />- Atendimentos realizados.
            <br />- Prontuários criados e finalizados.
            <br />- Documentos assinados.
            <br />- Pagamentos realizados.
            <br />- Mensagens enviadas por WhatsApp.
            <br /><br />
            <strong>Como acessar:</strong> Na ficha do paciente, clique na aba <strong>"Timeline"</strong>.
            <br /><br />
            <strong>Dica:</strong> A timeline é útil para entender rapidamente todo o histórico do paciente antes de um atendimento.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Documentos do paciente</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Na aba <strong>Documentos</strong> da ficha do paciente, você gerencia:
            <br /><br />
            - <strong>Termos de consentimento</strong> — documentos legais que o paciente precisa assinar.
            <br />- <strong>Contratos</strong> — documentos de tratamento ou orçamento.
            <br />- <strong>Uploads</strong> — exames, laudos e outros arquivos enviados pelo paciente ou pela clínica.
            <br /><br />
            <strong>Para adicionar um documento:</strong>
            <br />1. Acesse a ficha do paciente > aba Documentos.
            <br />2. Clique em <strong>"Novo documento"</strong> ou <strong>"Upload"</strong>.
            <br />3. Selecione o tipo, faça o upload do arquivo e salve.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Prescrições</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O módulo de <strong>Prescrições</strong> permite criar receitas e orientações para o paciente:
            <br /><br />
            1. Acesse a ficha do paciente > aba <strong>Prescrições</strong>.
            <br />2. Clique em <strong>"Nova prescrição"</strong>.
            <br />3. Preencha os medicamentos, dosagens e orientações.
            <br />4. Salve e, se desejar, imprima ou envie por WhatsApp.
            <br /><br />
            <strong>Impressão:</strong> As prescrições podem ser impressas em formato padronizado com o cabeçalho da clínica e dados do profissional.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Anamnese</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A <strong>Anamnese</strong> é o questionário de saúde que o paciente preenche antes do atendimento:
            <br /><br />
            - <strong>Templates:</strong> O admin pode criar modelos de anamnese personalizados em Configurações.
            <br />- <strong>Envio automático:</strong> Quando configurado, o sistema envia o link da anamnese automaticamente ao confirmar um agendamento.
            <br />- <strong>Preenchimento:</strong> O paciente pode preencher pelo Portal do Paciente ou por um link público enviado por WhatsApp.
            <br />- <strong>Visualização:</strong> O profissional vê as respostas da anamnese na ficha clínica do paciente antes do atendimento.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Envio de WhatsApp</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Se a integração com WhatsApp estiver configurada, você pode enviar mensagens diretamente da ficha do paciente:
            <br /><br />
            - <strong>Confirmação de agendamento</strong> — envia lembrete com data, hora e profissional.
            <br />- <strong>Link de anamnese</strong> — envia o questionário para preenchimento prévio.
            <br />- <strong>Documentos</strong> — envia prescrições, orientações e outros documentos.
            <br />- <strong>Mensagens personalizadas</strong> — usando templates configurados pelo admin.
            <br /><br />
            <strong>Como enviar:</strong> Na ficha do paciente, procure o botão de WhatsApp (ícone 💬) nas ações disponíveis.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Acesso ao Portal do Paciente</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Você pode ativar o acesso ao <strong>Portal do Paciente</strong> diretamente da ficha:
            <br /><br />
            1. Acesse a ficha do paciente.
            <br />2. Procure a seção <strong>"Acesso ao Portal"</strong>.
            <br />3. Clique em <strong>"Ativar acesso"</strong>.
            <br />4. O paciente precisa ter um e-mail cadastrado para receber as credenciais.
            <br />5. Um e-mail é enviado com o link e instruções de primeiro acesso.
            <br /><br />
            <strong>O que o paciente vê no portal:</strong> Seus agendamentos, documentos, resultados de exames, anamneses e notificações. Veja mais detalhes na seção <strong>Portal do Paciente</strong> deste tutorial.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>