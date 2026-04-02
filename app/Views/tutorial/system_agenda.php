<?php
$title = 'Tutorial do Sistema - Agenda';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Agenda (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/schedule">Abrir Agenda</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visão geral da Agenda</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A Agenda é o coração do sistema. É aqui que você gerencia todos os agendamentos da clínica — desde a criação de uma nova consulta até o acompanhamento do atendimento no dia.
            <br /><br />
            A agenda possui <strong>três modos de visualização</strong> e <strong>duas visões operacionais</strong> que atendem diferentes necessidades:
            <br />- <strong>Calendário</strong> (dia, semana, mês) — para visualizar e criar agendamentos.
            <br />- <strong>Operações / Recepção</strong> — para gerenciar o fluxo do dia a dia.
            <br />- <strong>Fila do Profissional</strong> — para o profissional acompanhar seus atendimentos.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visualizações do calendário</div>
        <div class="lc-card__body" style="line-height:1.6;">
            No topo da agenda, você encontra botões para alternar entre as visualizações:
            <br /><br />
            <strong>📅 Visão Dia</strong>
            <br />- Mostra todos os horários de um único dia, divididos por profissional.
            <br />- Ideal para a recepção acompanhar o fluxo do dia.
            <br />- Cada bloco colorido representa um agendamento. A cor indica o status.
            <br />- Clique em um horário vazio para criar um novo agendamento.
            <br />- Clique em um agendamento existente para ver detalhes e ações.
            <br /><br />
            <strong>📅 Visão Semana</strong>
            <br />- Mostra os 7 dias da semana com os agendamentos distribuídos.
            <br />- Útil para ter uma visão geral da ocupação da semana.
            <br />- Permite identificar dias com mais ou menos disponibilidade.
            <br /><br />
            <strong>📅 Visão Mês</strong>
            <br />- Mostra o mês inteiro em formato de calendário.
            <br />- Cada dia exibe a quantidade de agendamentos.
            <br />- Clique em um dia para ir à visão diária daquele dia.
            <br /><br />
            <strong>Navegação:</strong> Use as setas ◀ ▶ para avançar ou voltar no tempo. O botão "Hoje" retorna à data atual.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Como criar um agendamento</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Passo a passo:</strong>
            <br /><br />
            1. Na agenda, clique no botão <strong>"Novo agendamento"</strong> ou clique em um horário vazio no calendário.
            <br /><br />
            2. <strong>Selecione o paciente</strong> — digite o nome, CPF ou telefone para buscar. Se o paciente não existir, você pode cadastrá-lo na hora.
            <br /><br />
            3. <strong>Escolha o profissional</strong> — selecione o profissional que vai realizar o atendimento.
            <br /><br />
            4. <strong>Escolha o serviço</strong> — selecione o tipo de consulta ou procedimento.
            <br /><br />
            5. <strong>Defina data e horário</strong> — o sistema mostra apenas os horários disponíveis, respeitando os horários de funcionamento, bloqueios e regras configuradas.
            <br /><br />
            6. <strong>Adicione observações</strong> (opcional) — notas internas sobre o agendamento.
            <br /><br />
            7. Clique em <strong>"Salvar"</strong> para confirmar o agendamento.
            <br /><br />
            <strong>Dica:</strong> Após salvar, o sistema pode enviar automaticamente uma confirmação por WhatsApp para o paciente, se a integração estiver configurada.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Status dos agendamentos</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Cada agendamento possui um status que indica em que etapa ele está. Os status são:
            <br /><br />
            <strong>🔵 Agendado (scheduled)</strong>
            <br />- O agendamento foi criado mas ainda não foi confirmado pelo paciente.
            <br />- É o status inicial de todo novo agendamento.
            <br /><br />
            <strong>🟢 Confirmado (confirmed)</strong>
            <br />- O paciente confirmou presença (via WhatsApp, telefone ou portal).
            <br />- Indica que o paciente pretende comparecer.
            <br /><br />
            <strong>🟡 Em atendimento (in_progress)</strong>
            <br />- O paciente está sendo atendido pelo profissional.
            <br />- Ativado quando o profissional inicia o atendimento.
            <br /><br />
            <strong>✅ Concluído (completed)</strong>
            <br />- O atendimento foi finalizado com sucesso.
            <br />- O prontuário pode ser preenchido nesta etapa.
            <br /><br />
            <strong>🔴 Cancelado (cancelled)</strong>
            <br />- O agendamento foi cancelado pela clínica ou pelo paciente.
            <br />- Agendamentos cancelados não ocupam mais o horário.
            <br /><br />
            <strong>⚫ Não compareceu (no_show)</strong>
            <br />- O paciente não compareceu à consulta.
            <br />- Importante para controle de faltas e relatórios.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Confirmar, cancelar e reagendar</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Para confirmar um agendamento:</strong>
            <br />1. Clique no agendamento na agenda ou na visão de operações.
            <br />2. Clique no botão <strong>"Confirmar"</strong>.
            <br />3. O status muda para "Confirmado" e a cor do bloco é atualizada.
            <br /><br />
            <strong>Para cancelar um agendamento:</strong>
            <br />1. Clique no agendamento.
            <br />2. Clique em <strong>"Cancelar"</strong>.
            <br />3. Informe o motivo do cancelamento (opcional, mas recomendado).
            <br />4. O horário fica liberado para novos agendamentos.
            <br /><br />
            <strong>Para reagendar:</strong>
            <br />1. Clique no agendamento.
            <br />2. Clique em <strong>"Reagendar"</strong>.
            <br />3. Escolha a nova data e horário.
            <br />4. O agendamento original é atualizado com a nova data.
            <br />5. Se configurado, o paciente recebe uma notificação da mudança via WhatsApp.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visão de Operações (Recepção)</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A visão de <strong>Operações</strong> (acessível pelo menu Agenda > Operações) é a tela principal da recepção para gerenciar o dia a dia:
            <br /><br />
            <strong>O que você encontra:</strong>
            <br />- Lista de todos os agendamentos do dia, organizados por horário.
            <br />- Filtros por profissional, status e serviço.
            <br />- Ações rápidas: confirmar, iniciar atendimento, marcar como não compareceu.
            <br />- Informações do paciente (nome, telefone, observações).
            <br /><br />
            <strong>Fluxo típico da recepção:</strong>
            <br />1. No início do dia, revise os agendamentos e confirme os que ainda estão pendentes.
            <br />2. Quando o paciente chega, marque como <strong>"Confirmado"</strong> (se ainda não estiver).
            <br />3. Quando o profissional chamar, mude para <strong>"Em atendimento"</strong>.
            <br />4. Após o atendimento, o profissional finaliza e o status muda para <strong>"Concluído"</strong>.
            <br />5. Se o paciente não comparecer, marque como <strong>"Não compareceu"</strong>.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Fila do Profissional</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A <strong>Fila do Profissional</strong> (Agenda > Fila) é a visão dedicada ao profissional de saúde:
            <br /><br />
            - Mostra apenas os <strong>seus</strong> agendamentos do dia.
            <br />- Destaca o <strong>próximo paciente</strong> da fila.
            <br />- Permite <strong>iniciar o atendimento</strong> com um clique.
            <br />- Acesso rápido ao <strong>prontuário</strong> e <strong>ficha clínica</strong> do paciente.
            <br />- Ao finalizar, você pode criar o registro do prontuário diretamente.
            <br /><br />
            <strong>Dica:</strong> Use esta visão durante o expediente para manter o fluxo de atendimentos organizado sem precisar navegar pela agenda completa.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Filtros e busca na agenda</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A agenda oferece filtros para facilitar a visualização:
            <br /><br />
            - <strong>Por profissional</strong> — veja apenas os agendamentos de um profissional específico.
            <br />- <strong>Por status</strong> — filtre por agendados, confirmados, em atendimento, etc.
            <br />- <strong>Por serviço</strong> — veja apenas um tipo de procedimento.
            <br />- <strong>Por período</strong> — navegue entre datas usando as setas ou o seletor de data.
            <br /><br />
            <strong>Dica:</strong> Os filtros são combináveis. Por exemplo, você pode ver apenas os agendamentos "confirmados" do "Dr. João" para "Consulta inicial".
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>