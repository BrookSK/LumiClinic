<?php
$title = 'Tutorial do Sistema - Integrações e API';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Integrações e API (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/tutorial/api-tokens/paciente">Tutorial API Token (Paciente)</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visão geral das Integrações</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O LumiClinic se integra com serviços externos para ampliar suas funcionalidades. As integrações disponíveis são:
            <br /><br />
            - <strong>💬 WhatsApp (Evolution API)</strong> — envio automático de mensagens, confirmações e lembretes.
            <br />- <strong>📅 Google Calendar (OAuth)</strong> — sincronização da agenda com o Google Calendar.
            <br />- <strong>🤖 OpenAI</strong> — transcrição de áudio e assistente de IA para prontuários.
            <br />- <strong>🔗 API REST</strong> — acesso programático aos dados do sistema via tokens.
            <br />- <strong>🔔 Webhooks</strong> — notificações automáticas para sistemas externos quando eventos acontecem.
            <br /><br />
            <strong>Quem configura:</strong> Admin.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">💬 WhatsApp — Evolution API</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A integração com WhatsApp usa a <strong>Evolution API</strong>, um serviço que permite enviar e receber mensagens pelo WhatsApp de forma automatizada.
            <br /><br />
            <strong>O que você precisa:</strong>
            <br />- Uma instância da Evolution API rodando (self-hosted ou serviço contratado).
            <br />- A URL base da API (ex: https://api.suaempresa.com).
            <br />- A API Key de autenticação.
            <br />- Um número de WhatsApp dedicado para a clínica.
            <br /><br />
            <strong>Configuração passo a passo:</strong>
            <br />1. Acesse <strong>Configurações > WhatsApp</strong>.
            <br />2. Informe a <strong>URL da Evolution API</strong>.
            <br />3. Informe a <strong>API Key</strong>.
            <br />4. Clique em <strong>"Conectar"</strong>.
            <br />5. Um <strong>QR Code</strong> será exibido — escaneie com o WhatsApp do celular da clínica.
            <br />6. Após a conexão, o status muda para "Conectado".
            <br /><br />
            <strong>Funcionalidades após conexão:</strong>
            <br />- Envio automático de confirmação de agendamento.
            <br />- Envio de lembretes antes da consulta.
            <br />- Envio de link de anamnese.
            <br />- Envio de prescrições e documentos.
            <br />- Mensagens personalizadas usando templates.
            <br /><br />
            <strong>Logs:</strong> Em <strong>Configurações > Logs WhatsApp</strong>, você pode ver o histórico de todas as mensagens enviadas, com status de entrega.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">📅 Google Calendar — OAuth 2.0</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A integração com Google Calendar sincroniza os agendamentos do LumiClinic com o calendário Google do profissional.
            <br /><br />
            <strong>Como funciona:</strong>
            <br />- Usa autenticação <strong>OAuth 2.0</strong> — o profissional autoriza o acesso à sua conta Google de forma segura.
            <br />- Agendamentos criados no LumiClinic aparecem automaticamente no Google Calendar.
            <br />- Cancelamentos e reagendamentos são sincronizados.
            <br /><br />
            <strong>Configuração:</strong>
            <br />1. Acesse <strong>Configurações > Google Calendar</strong>.
            <br />2. Clique em <strong>"Conectar com Google"</strong>.
            <br />3. Faça login na conta Google do profissional.
            <br />4. Autorize o acesso ao calendário.
            <br />5. Selecione qual calendário usar (principal ou um específico).
            <br />6. Pronto! A sincronização começa automaticamente.
            <br /><br />
            <strong>Notas:</strong>
            <br />- Cada profissional precisa conectar sua própria conta Google.
            <br />- A sincronização é unidirecional: LumiClinic → Google Calendar.
            <br />- Para desconectar, acesse a mesma tela e clique em "Desconectar".
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">🔗 API REST e Tokens</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O sistema oferece uma <strong>API REST</strong> para acesso programático aos dados. Isso permite que sistemas externos (sites, apps, automações) se comuniquem com o LumiClinic.
            <br /><br />
            <strong>Autenticação:</strong>
            <br />- O acesso à API é feito via <strong>API Tokens</strong>.
            <br />- Cada token tem permissões específicas (leitura, escrita).
            <br />- Tokens podem ser gerados pelo admin ou pelo paciente (no portal).
            <br /><br />
            <strong>Gerar um token (admin):</strong>
            <br />1. Acesse a área de API Tokens nas configurações.
            <br />2. Clique em "Gerar novo token".
            <br />3. Defina o nome, as permissões e a validade.
            <br />4. Copie o token gerado (ele só é exibido uma vez).
            <br />5. Use o token no header das requisições: <code>Authorization: Bearer SEU_TOKEN</code>.
            <br /><br />
            <strong>Endpoints disponíveis:</strong>
            <br />- <code>/api/v1/patients</code> — listar e buscar pacientes.
            <br />- <code>/api/v1/appointments</code> — listar e criar agendamentos.
            <br />- <code>/api/v1/services</code> — listar serviços.
            <br />- Outros endpoints conforme documentação da API.
            <br /><br />
            <strong>Segurança:</strong>
            <br />- Nunca compartilhe tokens publicamente.
            <br />- Use tokens com permissões mínimas necessárias.
            <br />- Revogue tokens que não são mais utilizados.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">🔔 Webhooks</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Webhooks</strong> permitem que o LumiClinic notifique sistemas externos quando eventos acontecem:
            <br /><br />
            <strong>Eventos disponíveis:</strong>
            <br />- <strong>appointment.created</strong> — novo agendamento criado.
            <br />- <strong>appointment.confirmed</strong> — agendamento confirmado.
            <br />- <strong>appointment.cancelled</strong> — agendamento cancelado.
            <br />- <strong>appointment.completed</strong> — atendimento concluído.
            <br />- <strong>patient.created</strong> — novo paciente cadastrado.
            <br />- <strong>payment.received</strong> — pagamento registrado.
            <br /><br />
            <strong>Como funciona:</strong>
            <br />- Quando o evento acontece, o sistema envia uma requisição HTTP POST para a URL configurada.
            <br />- O payload contém os dados do evento em formato JSON.
            <br />- O sistema externo processa os dados conforme sua lógica.
            <br /><br />
            <strong>Configuração:</strong>
            <br />1. Acesse a área de Webhooks nas configurações.
            <br />2. Clique em "Novo webhook".
            <br />3. Informe a URL de destino (endpoint do seu sistema).
            <br />4. Selecione quais eventos devem disparar o webhook.
            <br />5. Salve.
            <br /><br />
            <strong>Dica:</strong> Use webhooks para integrar com CRMs, sistemas de marketing, ERPs ou qualquer ferramenta que precise ser notificada sobre eventos da clínica.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">🤖 OpenAI (Inteligência Artificial)</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A integração com a OpenAI habilita funcionalidades de inteligência artificial no sistema:
            <br /><br />
            <strong>Transcrição de áudio:</strong>
            <br />- O profissional grava um áudio durante ou após o atendimento.
            <br />- O áudio é enviado para a API da OpenAI (Whisper).
            <br />- O texto transcrito é inserido no prontuário.
            <br /><br />
            <strong>Configuração:</strong>
            <br />1. Acesse <strong>Configurações > Inteligência Artificial</strong>.
            <br />2. Informe sua <strong>chave de API da OpenAI</strong>.
            <br />3. Salve.
            <br /><br />
            <strong>Custos:</strong> O uso da API da OpenAI é cobrado por uso (por minuto de áudio transcrito ou por tokens de texto). Consulte os preços em platform.openai.com.
            <br /><br />
            <strong>Privacidade:</strong> Os áudios são enviados para processamento e não são armazenados pela OpenAI após a transcrição. Consulte a política de privacidade da OpenAI para mais detalhes.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Solução de problemas</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>"WhatsApp desconectou"</strong>
            <br />- Acesse Configurações > WhatsApp e reconecte escaneando o QR Code novamente.
            <br />- Verifique se o celular com o WhatsApp está conectado à internet.
            <br /><br />
            <strong>"Google Calendar não sincroniza"</strong>
            <br />- Verifique se a autorização OAuth ainda está ativa.
            <br />- Reconecte a conta Google se necessário.
            <br />- Verifique se o calendário selecionado ainda existe.
            <br /><br />
            <strong>"API retorna erro 401"</strong>
            <br />- O token pode ter expirado ou sido revogado.
            <br />- Gere um novo token e atualize no sistema externo.
            <br /><br />
            <strong>"Webhook não está sendo recebido"</strong>
            <br />- Verifique se a URL de destino está acessível publicamente.
            <br />- Verifique os logs de webhook para ver se houve erros de entrega.
            <br />- Certifique-se de que o endpoint retorna status 200.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>