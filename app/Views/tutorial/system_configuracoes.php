<?php
$title = 'Tutorial do Sistema - Configurações';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Configurações (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/settings">Abrir Configurações</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visão geral das Configurações</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A área de <strong>Configurações</strong> reúne todos os ajustes do sistema. Aqui você personaliza a clínica, configura integrações, define horários de funcionamento e muito mais.
            <br /><br />
            <strong>Quem usa:</strong> Admin (acesso completo). Outros perfis podem ter acesso limitado dependendo das permissões.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Configurações gerais</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Na página principal de Configurações, você encontra:
            <br /><br />
            <strong>📝 Terminologia</strong>
            <br />Personalize os termos usados no sistema para se adequar à sua clínica:
            <br />- <strong>Paciente / Cliente</strong> — se sua clínica chama de "cliente" em vez de "paciente", altere aqui.
            <br />- <strong>Consulta / Sessão</strong> — adapte o termo para "sessão", "atendimento" ou outro.
            <br />- <strong>Profissional / Especialista</strong> — use "doutor", "terapeuta" ou o termo que preferir.
            <br />O sistema inteiro se adapta à terminologia escolhida.
            <br /><br />
            <strong>📅 Agenda e anamnese</strong>
            <br />- <strong>Início da semana</strong> — defina qual dia a semana começa na agenda (domingo, segunda, etc.).
            <br />- <strong>Anamnese padrão</strong> — selecione qual template de anamnese é enviado automaticamente quando uma consulta é confirmada.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">💬 Configuração do WhatsApp</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Em <strong>Configurações > WhatsApp</strong>, você conecta o sistema ao WhatsApp para envio automático de mensagens:
            <br /><br />
            <strong>O que é necessário:</strong>
            <br />- Uma instância da <strong>Evolution API</strong> (serviço de integração com WhatsApp).
            <br />- A <strong>URL da API</strong> e a <strong>chave de autenticação (API Key)</strong>.
            <br /><br />
            <strong>Passo a passo:</strong>
            <br />1. Acesse Configurações > WhatsApp.
            <br />2. Informe a URL da Evolution API e a API Key.
            <br />3. Clique em "Conectar" e escaneie o QR Code com o WhatsApp do celular da clínica.
            <br />4. Após conectar, configure os templates de mensagem.
            <br /><br />
            <strong>Templates de mensagem:</strong>
            <br />- Em <strong>Templates WhatsApp</strong>, crie modelos de mensagem para diferentes situações:
            <br />- Confirmação de agendamento.
            <br />- Lembrete de consulta.
            <br />- Envio de anamnese.
            <br />- Mensagens personalizadas.
            <br />- Use variáveis como {nome_paciente}, {data}, {hora}, {profissional} para personalizar automaticamente.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">🤖 Configuração de Inteligência Artificial</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Em <strong>Configurações > Inteligência Artificial</strong>, configure a integração com a OpenAI:
            <br /><br />
            <strong>O que é necessário:</strong>
            <br />- Uma <strong>chave de API da OpenAI</strong> (obtida em platform.openai.com).
            <br /><br />
            <strong>Funcionalidades habilitadas:</strong>
            <br />- <strong>Transcrição de áudio</strong> — converte gravações de voz em texto para prontuários.
            <br />- <strong>Assistente de texto</strong> — ajuda a redigir e formatar conteúdo clínico.
            <br /><br />
            <strong>Passo a passo:</strong>
            <br />1. Acesse Configurações > Inteligência Artificial.
            <br />2. Cole a chave da API da OpenAI.
            <br />3. Salve.
            <br />4. A funcionalidade de transcrição ficará disponível nos prontuários.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">⚙️ Configurações operacionais</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Em <strong>Configurações > Operacional</strong>, gerencie listas e opções usadas no dia a dia:
            <br /><br />
            - <strong>Funil de vendas</strong> — etapas do processo comercial (lead, orçamento, fechado).
            <br />- <strong>Motivos de cancelamento</strong> — opções padronizadas para quando um agendamento é cancelado.
            <br />- <strong>Origens de paciente</strong> — como o paciente conheceu a clínica (indicação, Instagram, Google, etc.).
            <br /><br />
            <strong>Dica:</strong> Padronizar essas listas melhora a qualidade dos relatórios e facilita a análise de dados.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">📅 Google Calendar</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Em <strong>Configurações > Google Calendar</strong>, conecte a agenda do sistema ao Google Calendar:
            <br /><br />
            - Os agendamentos criados no LumiClinic são sincronizados com o Google Calendar do profissional.
            <br />- Alterações (cancelamento, reagendamento) são refletidas automaticamente.
            <br />- A conexão usa <strong>OAuth 2.0</strong> — cada profissional autoriza o acesso à sua conta Google.
            <br /><br />
            <strong>Passo a passo:</strong>
            <br />1. Acesse Configurações > Google Calendar.
            <br />2. Clique em "Conectar com Google".
            <br />3. Autorize o acesso na tela do Google.
            <br />4. Selecione qual calendário usar para sincronização.
            <br />5. Pronto! Os agendamentos serão sincronizados automaticamente.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">🕐 Horários de funcionamento</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Em <strong>Configurações > Horários de funcionamento</strong>, defina quando a clínica atende:
            <br /><br />
            - Configure o <strong>horário de início e fim</strong> para cada dia da semana.
            <br />- Defina <strong>intervalos</strong> (ex: horário de almoço).
            <br />- Dias sem horário configurado são considerados como não-atendimento.
            <br /><br />
            <strong>Importante:</strong> Esses horários determinam quais slots ficam disponíveis na agenda. Agendamentos só podem ser criados dentro dos horários configurados.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">📅 Dias fechados</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Em <strong>Configurações > Dias fechados</strong>, cadastre feriados e recessos:
            <br /><br />
            - <strong>Feriados nacionais</strong> — Natal, Ano Novo, Carnaval, etc.
            <br />- <strong>Feriados locais</strong> — aniversário da cidade, padroeiro, etc.
            <br />- <strong>Recessos</strong> — férias coletivas, recesso de fim de ano.
            <br /><br />
            <strong>Como cadastrar:</strong>
            <br />1. Clique em "Novo dia fechado".
            <br />2. Informe a data e o motivo.
            <br />3. Salve.
            <br /><br />
            Nos dias fechados, a agenda não permite novos agendamentos e mostra o dia como indisponível.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">📋 Regras de agendamento</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Em <strong>Configurações > Regras</strong>, defina regras que controlam como os agendamentos são criados:
            <br /><br />
            - <strong>Antecedência mínima</strong> — tempo mínimo antes da consulta para permitir agendamento (ex: 2 horas).
            <br />- <strong>Antecedência máxima</strong> — até quantos dias no futuro é possível agendar (ex: 90 dias).
            <br />- <strong>Intervalo entre consultas</strong> — tempo mínimo entre um atendimento e outro do mesmo profissional.
            <br />- <strong>Limite de agendamentos por dia</strong> — máximo de consultas por profissional por dia.
            <br /><br />
            <strong>Dica:</strong> Regras bem configuradas evitam agendamentos em horários inadequados e ajudam a manter o fluxo organizado.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">🚫 Bloqueios de agenda</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Em <strong>Configurações > Bloqueios</strong>, crie bloqueios para horários específicos:
            <br /><br />
            - <strong>Bloqueio por profissional</strong> — férias, folga, compromisso pessoal.
            <br />- <strong>Bloqueio geral</strong> — manutenção, evento, reunião de equipe.
            <br /><br />
            <strong>Como criar:</strong>
            <br />1. Clique em "Novo bloqueio".
            <br />2. Selecione o profissional (ou "Todos" para bloqueio geral).
            <br />3. Defina data/hora de início e fim.
            <br />4. Informe o motivo.
            <br />5. Salve.
            <br /><br />
            Horários bloqueados ficam indisponíveis na agenda e não permitem novos agendamentos.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">📄 Documentos legais</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Em <strong>Configurações > Documentos legais</strong>, gerencie os termos e documentos que os pacientes precisam assinar:
            <br /><br />
            - <strong>Termos de consentimento</strong> — para procedimentos específicos.
            <br />- <strong>Política de privacidade</strong> — LGPD e proteção de dados.
            <br />- <strong>Contratos de tratamento</strong> — acordos sobre planos de tratamento.
            <br /><br />
            <strong>Como criar:</strong>
            <br />1. Clique em "Novo documento".
            <br />2. Defina o título, tipo e conteúdo do documento.
            <br />3. Use o editor para formatar o texto.
            <br />4. Salve e ative o documento.
            <br /><br />
            Documentos ativos podem ser enviados para assinatura do paciente pelo sistema ou pelo Portal do Paciente.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>