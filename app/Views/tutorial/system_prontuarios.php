<?php
$title = 'Tutorial do Sistema - Prontuários';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Prontuários (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">O que são Prontuários</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Os <strong>Prontuários</strong> são os registros clínicos dos atendimentos realizados. Cada vez que um profissional atende um paciente, ele pode criar um registro de prontuário documentando o que foi feito, observações clínicas, diagnósticos e plano de tratamento.
            <br /><br />
            <strong>Quem usa:</strong> Profissionais (médicos, dentistas, esteticistas) e Admins. A recepção geralmente não tem acesso ao conteúdo dos prontuários.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Criar um prontuário</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Passo a passo:</strong>
            <br /><br />
            1. Acesse o prontuário de uma das seguintes formas:
            <br />- Pela <strong>Fila do Profissional</strong> — ao finalizar um atendimento, clique em "Criar prontuário".
            <br />- Pela <strong>Ficha do Paciente</strong> — aba Prontuários > "Novo prontuário".
            <br />- Pelo menu <strong>Prontuários</strong> > "Novo registro".
            <br /><br />
            2. <strong>Selecione o paciente</strong> (se não vier preenchido automaticamente).
            <br /><br />
            3. <strong>Escolha um template</strong> (opcional) — templates são modelos pré-definidos que estruturam o prontuário com campos específicos para cada tipo de atendimento.
            <br /><br />
            4. <strong>Preencha o conteúdo</strong> — descreva o atendimento, observações clínicas, diagnóstico, procedimentos realizados e plano de tratamento.
            <br /><br />
            5. <strong>Adicione alertas</strong> (opcional) — marque informações importantes que devem aparecer em destaque nos próximos atendimentos (ex: alergias, condições especiais).
            <br /><br />
            6. Clique em <strong>"Salvar"</strong> para salvar como rascunho ou <strong>"Finalizar"</strong> para concluir o registro.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Templates de prontuário</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Templates são modelos que padronizam a estrutura do prontuário. Eles são úteis para:
            <br /><br />
            - Garantir que todas as informações necessárias sejam registradas.
            <br />- Agilizar o preenchimento com campos pré-definidos.
            <br />- Padronizar os registros entre diferentes profissionais.
            <br /><br />
            <strong>Exemplos de templates:</strong>
            <br />- Consulta inicial (anamnese + exame físico + diagnóstico + plano).
            <br />- Retorno (evolução + ajustes no tratamento).
            <br />- Procedimento estético (área tratada + produto + quantidade + observações).
            <br /><br />
            <strong>Como criar templates:</strong>
            <br />1. Acesse <strong>Prontuários > Templates</strong> (requer permissão de admin).
            <br />2. Clique em <strong>"Novo template"</strong>.
            <br />3. Defina o nome, os campos e a estrutura.
            <br />4. Salve. O template ficará disponível para todos os profissionais ao criar novos prontuários.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Alertas clínicos</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Os <strong>alertas</strong> são informações críticas que ficam em destaque na ficha do paciente:
            <br /><br />
            - <strong>Alergias</strong> — medicamentos, substâncias ou materiais.
            <br />- <strong>Condições especiais</strong> — gravidez, doenças crônicas, uso de anticoagulantes.
            <br />- <strong>Observações importantes</strong> — qualquer informação que o próximo profissional precisa saber.
            <br /><br />
            <strong>Como adicionar:</strong> Ao criar ou editar um prontuário, use o campo de alertas. Os alertas ficam visíveis em destaque no topo da ficha clínica do paciente.
            <br /><br />
            <strong>Importante:</strong> Alertas são compartilhados entre todos os profissionais que atendem o paciente.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Transcrição de áudio com IA</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Se a integração com <strong>Inteligência Artificial (OpenAI)</strong> estiver configurada, você pode usar a transcrição de áudio para preencher prontuários:
            <br /><br />
            1. No formulário do prontuário, clique no botão <strong>"Gravar áudio"</strong> (ícone de microfone 🎤).
            <br />2. Fale normalmente descrevendo o atendimento.
            <br />3. Ao parar a gravação, o sistema envia o áudio para transcrição.
            <br />4. O texto transcrito é inserido automaticamente no campo do prontuário.
            <br />5. Revise e ajuste o texto conforme necessário antes de salvar.
            <br /><br />
            <strong>Dica:</strong> A transcrição funciona melhor em ambientes silenciosos e com fala clara. Você pode editar o texto transcrito livremente.
            <br /><br />
            <strong>Configuração:</strong> O admin precisa configurar a chave da OpenAI em Configurações > Inteligência Artificial.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Finalizar atendimento</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O fluxo completo de um atendimento com prontuário é:
            <br /><br />
            1. <strong>Iniciar atendimento</strong> — na fila do profissional ou na agenda, mude o status para "Em atendimento".
            <br />2. <strong>Atender o paciente</strong> — realize a consulta ou procedimento.
            <br />3. <strong>Criar o prontuário</strong> — registre as informações do atendimento.
            <br />4. <strong>Finalizar o prontuário</strong> — clique em "Finalizar" para marcar como concluído.
            <br />5. <strong>Concluir o agendamento</strong> — o status do agendamento muda para "Concluído".
            <br /><br />
            <strong>Importante:</strong> Prontuários finalizados não podem ser editados (por questões legais e de auditoria). Certifique-se de que todas as informações estão corretas antes de finalizar.
            <br /><br />
            <strong>Dica:</strong> Se precisar adicionar informações após finalizar, crie um novo registro de evolução vinculado ao mesmo paciente.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Editar prontuários (rascunho)</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Enquanto o prontuário estiver em <strong>rascunho</strong> (não finalizado), você pode editá-lo livremente:
            <br /><br />
            1. Acesse a lista de prontuários ou a ficha do paciente.
            <br />2. Clique no prontuário em rascunho.
            <br />3. Faça as alterações necessárias.
            <br />4. Salve novamente como rascunho ou finalize.
            <br /><br />
            <strong>Nota:</strong> Prontuários em rascunho ficam sinalizados na lista para que você lembre de finalizá-los.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>