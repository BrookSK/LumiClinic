<?php
$title = 'Tutorial do Sistema - Estoque';
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

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

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
                <div class="lc-page__subtitle" style="margin-top:2px;">Estoque (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <?php if ($can('stock.materials.read')): ?>
                <a class="lc-btn lc-btn--secondary" href="/stock/materials">Abrir Estoque</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visão geral do Estoque</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O módulo de <strong>Estoque</strong> controla os materiais e insumos utilizados pela clínica nos procedimentos e atendimentos. Ele permite cadastrar materiais, registrar entradas e saídas, definir estoque mínimo e receber alertas quando um item está acabando.
            <br /><br />
            <strong>Quem usa:</strong> Admin (gestão completa).
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Materiais</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Cadastrar um material:</strong>
            <br /><br />
            1. Acesse <strong>Estoque > Materiais</strong>.
            <br />2. Clique em <strong>"Novo material"</strong>.
            <br />3. Preencha:
            <br />- <strong>Nome</strong> — nome do material (ex: "Ácido hialurônico 1ml", "Luva descartável M").
            <br />- <strong>Categoria</strong> — grupo ao qual pertence (ex: injetáveis, descartáveis, medicamentos).
            <br />- <strong>Unidade de medida</strong> — como o material é contado (unidade, ml, mg, caixa, pacote).
            <br />- <strong>Estoque mínimo</strong> — quantidade mínima que deve ter em estoque. Quando o saldo ficar abaixo desse valor, um alerta é gerado.
            <br />- <strong>Custo unitário</strong> — valor de compra por unidade (para controle de custos).
            <br />4. Clique em <strong>"Salvar"</strong>.
            <br /><br />
            <strong>Editar material:</strong> Na lista de materiais, clique no material desejado para editar seus dados.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Movimentações de estoque</div>
        <div class="lc-card__body" style="line-height:1.6;">
            As movimentações registram toda entrada e saída de materiais:
            <br /><br />
            <strong>Tipos de movimentação:</strong>
            <br />- <strong>Entrada</strong> — quando você compra ou recebe materiais (aumenta o saldo).
            <br />- <strong>Saída</strong> — quando materiais são consumidos em procedimentos (diminui o saldo).
            <br />- <strong>Ajuste</strong> — correção manual do saldo (inventário, perda, vencimento).
            <br /><br />
            <strong>Registrar uma movimentação manual:</strong>
            <br />1. Acesse o material desejado.
            <br />2. Clique em <strong>"Nova movimentação"</strong>.
            <br />3. Selecione o tipo (entrada, saída ou ajuste).
            <br />4. Informe a quantidade e o motivo.
            <br />5. Salve.
            <br /><br />
            <strong>Movimentação automática:</strong> Quando um serviço tem materiais vinculados, o sistema pode dar baixa automaticamente no estoque ao concluir um atendimento.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Categorias e unidades</div>
        <div class="lc-card__body" style="line-height:1.6;">
            <strong>Categorias</strong> organizam os materiais em grupos lógicos:
            <br />- Exemplos: Injetáveis, Descartáveis, Medicamentos, Cosméticos, Equipamentos.
            <br />- Para criar: Estoque > Categorias > "Nova categoria".
            <br /><br />
            <strong>Unidades de medida</strong> definem como cada material é contabilizado:
            <br />- Exemplos: Unidade (un), Mililitro (ml), Grama (g), Caixa (cx), Pacote (pct).
            <br />- Para criar: Estoque > Unidades > "Nova unidade".
            <br /><br />
            <strong>Dica:</strong> Defina categorias e unidades antes de cadastrar os materiais. Isso facilita a organização e os relatórios.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Alertas de estoque mínimo</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O sistema monitora automaticamente o saldo dos materiais e gera alertas quando o estoque está baixo:
            <br /><br />
            - <strong>Alerta amarelo</strong> — estoque próximo do mínimo (atenção).
            <br />- <strong>Alerta vermelho</strong> — estoque abaixo do mínimo (urgente).
            <br />- <strong>Estoque zerado</strong> — material esgotado.
            <br /><br />
            <strong>Onde ver os alertas:</strong>
            <br />- No <strong>Dashboard</strong> (card de alertas de estoque).
            <br />- Em <strong>Estoque > Alertas</strong> (lista completa de materiais com estoque baixo).
            <br /><br />
            <strong>Dica:</strong> Configure o estoque mínimo de cada material de acordo com o consumo médio da clínica. Isso evita surpresas e garante que você sempre tenha os insumos necessários.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Vinculação de materiais a serviços</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Você pode vincular materiais aos serviços da clínica para controle automático de consumo:
            <br /><br />
            1. Acesse <strong>Serviços</strong> e selecione o serviço desejado.
            <br />2. Na seção <strong>"Materiais"</strong>, clique em "Vincular material".
            <br />3. Selecione o material e informe a <strong>quantidade consumida por atendimento</strong>.
            <br />4. Salve.
            <br /><br />
            <strong>Exemplo:</strong> O serviço "Aplicação de Botox" consome 1 unidade de "Toxina botulínica 50U". Ao concluir um atendimento desse serviço, o sistema automaticamente dá baixa de 1 unidade no estoque.
            <br /><br />
            <strong>Benefício:</strong> Isso permite calcular o custo real de cada procedimento e manter o estoque sempre atualizado.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>