<?php
$title = 'Tutorial do Sistema - Imagens Médicas';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Imagens Médicas (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <a class="lc-btn lc-btn--secondary" href="/medical-images">Abrir Imagens</a>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visão geral de Imagens Médicas</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O módulo de <strong>Imagens Médicas</strong> permite armazenar, organizar e comparar fotos e exames de imagem dos pacientes. É especialmente útil para clínicas de estética, dermatologia e odontologia, onde o acompanhamento visual da evolução do tratamento é fundamental.
            <br /><br />
            <strong>Quem usa:</strong> Profissionais e Admins.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Upload de imagens</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Passo a passo para enviar imagens:</strong>
            <br /><br />
            1. Acesse <strong>Imagens</strong> no menu ou pela ficha do paciente > aba Imagens.
            <br /><br />
            2. Clique em <strong>"Nova imagem"</strong> ou <strong>"Upload"</strong>.
            <br /><br />
            3. <strong>Selecione o paciente</strong> (se não vier preenchido).
            <br /><br />
            4. <strong>Escolha as imagens</strong> — arraste os arquivos ou clique para selecionar do computador/celular. Formatos aceitos: JPG, PNG, WEBP.
            <br /><br />
            5. <strong>Preencha os metadados:</strong>
            <br />- <strong>Categoria</strong> — tipo da imagem (ex: face frontal, perfil, intraoral, raio-x).
            <br />- <strong>Data da captura</strong> — quando a foto foi tirada.
            <br />- <strong>Observações</strong> — notas sobre a imagem.
            <br />- <strong>Visibilidade</strong> — se a imagem deve ser visível para o paciente no Portal.
            <br /><br />
            6. Clique em <strong>"Salvar"</strong>.
            <br /><br />
            <strong>Dica:</strong> Você pode enviar várias imagens de uma vez. Cada uma será salva individualmente com os mesmos metadados.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Galeria de imagens</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A galeria mostra todas as imagens do paciente organizadas por data:
            <br /><br />
            - <strong>Visualização em grade</strong> — miniaturas das imagens para navegação rápida.
            <br />- <strong>Visualização ampliada</strong> — clique em uma imagem para ver em tamanho completo.
            <br />- <strong>Filtros</strong> — filtre por categoria, data ou profissional que enviou.
            <br />- <strong>Ordenação</strong> — ordene por data (mais recente ou mais antiga primeiro).
            <br /><br />
            <strong>Ações disponíveis em cada imagem:</strong>
            <br />- Visualizar em tela cheia.
            <br />- Editar metadados (categoria, observações, visibilidade).
            <br />- Excluir (requer permissão).
            <br />- Selecionar para comparação antes/depois.
            <br />- Abrir na ferramenta de anotação.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Comparador antes/depois</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O <strong>comparador</strong> permite colocar duas imagens lado a lado para visualizar a evolução do tratamento:
            <br /><br />
            <strong>Como usar:</strong>
            <br />1. Na galeria do paciente, selecione a primeira imagem (antes).
            <br />2. Selecione a segunda imagem (depois).
            <br />3. Clique em <strong>"Comparar"</strong>.
            <br />4. As duas imagens são exibidas lado a lado.
            <br />5. Use o controle deslizante para sobrepor as imagens e ver as diferenças.
            <br /><br />
            <strong>Dica:</strong> Para melhores resultados, tire as fotos sempre na mesma posição, iluminação e distância. Isso facilita a comparação visual.
            <br /><br />
            <strong>Uso clínico:</strong> O comparador é excelente para mostrar ao paciente a evolução do tratamento durante a consulta.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Ferramenta de anotação</div>
        <div class="lc-card__body" style="line-height:1.6;">
            A <strong>ferramenta de anotação</strong> permite desenhar e marcar pontos de interesse diretamente sobre as imagens:
            <br /><br />
            <strong>Recursos disponíveis:</strong>
            <br />- <strong>Desenho livre</strong> — desenhe com o mouse ou dedo (em telas touch).
            <br />- <strong>Setas e linhas</strong> — aponte para áreas específicas.
            <br />- <strong>Texto</strong> — adicione legendas e observações sobre a imagem.
            <br />- <strong>Cores</strong> — escolha diferentes cores para destacar diferentes aspectos.
            <br />- <strong>Borracha</strong> — apague anotações específicas.
            <br /><br />
            <strong>Como usar:</strong>
            <br />1. Na galeria, clique na imagem desejada.
            <br />2. Clique em <strong>"Anotar"</strong>.
            <br />3. Use as ferramentas de desenho para fazer suas marcações.
            <br />4. Clique em <strong>"Salvar"</strong> para gravar as anotações.
            <br /><br />
            <strong>Nota:</strong> A imagem original é preservada. As anotações são salvas como uma camada separada, podendo ser removidas posteriormente.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Imagens enviadas pelo paciente</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Se o Portal do Paciente estiver ativo, o paciente pode enviar imagens diretamente:
            <br /><br />
            - O paciente faz upload pelo portal.
            <br />- As imagens ficam em uma fila de <strong>moderação</strong>.
            <br />- O profissional ou admin revisa e aprova (ou rejeita) cada imagem.
            <br />- Imagens aprovadas são adicionadas à galeria do paciente.
            <br /><br />
            <strong>Dica:</strong> Isso é útil para acompanhamento remoto — o paciente pode enviar fotos da evolução entre as consultas.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>