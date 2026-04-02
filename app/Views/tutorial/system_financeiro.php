<?php
$title = 'Tutorial do Sistema - Financeiro';
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
                <div class="lc-page__subtitle" style="margin-top:2px;">Financeiro (perfil: <?= htmlspecialchars($perfilLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center; justify-content:flex-end;">
            <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Voltar para o índice</a>
            <?php if ($can('finance.sales.read')): ?>
                <a class="lc-btn lc-btn--secondary" href="/finance/sales">Abrir Financeiro</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Visão geral do módulo Financeiro</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O módulo Financeiro controla toda a movimentação financeira da clínica: vendas (orçamentos), pagamentos, fluxo de caixa, contas a pagar e relatórios. Ele é integrado com a agenda e os serviços, permitindo que o faturamento aconteça de forma natural no fluxo de atendimento.
            <br /><br />
            <strong>Quem usa:</strong> Admin, Recepção e perfil Financeiro.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Vendas e orçamentos</div>
        <div class="lc-card__body" style="line-height:1.6;">
            As <strong>Vendas</strong> (também chamadas de orçamentos) são o registro financeiro dos serviços prestados ao paciente:
            <br /><br />
            <strong>Criar uma venda:</strong>
            <br />1. Acesse <strong>Financeiro > Vendas</strong>.
            <br />2. Clique em <strong>"Nova venda"</strong>.
            <br />3. <strong>Selecione o paciente</strong>.
            <br />4. <strong>Adicione os itens</strong> — serviços, procedimentos ou produtos. Cada item tem valor unitário e quantidade.
            <br />5. Aplique <strong>descontos</strong> se necessário (percentual ou valor fixo).
            <br />6. Salve a venda.
            <br /><br />
            <strong>Status de uma venda:</strong>
            <br />- <strong>Aberta</strong> — orçamento criado, aguardando pagamento.
            <br />- <strong>Parcialmente paga</strong> — parte do valor já foi recebida.
            <br />- <strong>Paga</strong> — valor total recebido.
            <br />- <strong>Cancelada</strong> — venda cancelada.
            <br /><br />
            <strong>Dica:</strong> Vendas podem ser criadas automaticamente a partir de agendamentos, dependendo da configuração da clínica.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Pagamentos</div>
        <div class="lc-card__body" style="line-height:1.6;">
            Para registrar um pagamento em uma venda:
            <br /><br />
            1. Abra a venda desejada.
            <br />2. Clique em <strong>"Registrar pagamento"</strong>.
            <br />3. Informe:
            <br />- <strong>Valor</strong> — pode ser o total ou um valor parcial.
            <br />- <strong>Forma de pagamento</strong> — dinheiro, cartão de crédito, cartão de débito, PIX, transferência, etc.
            <br />- <strong>Data do pagamento</strong>.
            <br />- <strong>Observações</strong> (opcional).
            <br />4. Confirme o pagamento.
            <br /><br />
            <strong>Estornos:</strong> Se necessário, é possível estornar um pagamento. Acesse o pagamento registrado e clique em "Estornar". O valor é devolvido ao saldo da venda.
            <br /><br />
            <strong>Nota:</strong> Pagamentos registrados alimentam automaticamente o fluxo de caixa.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Fluxo de caixa</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O <strong>Fluxo de Caixa</strong> (Financeiro > Caixa) mostra todas as entradas e saídas de dinheiro da clínica:
            <br /><br />
            <strong>O que você encontra:</strong>
            <br />- <strong>Entradas</strong> — pagamentos recebidos de pacientes.
            <br />- <strong>Saídas</strong> — pagamentos de contas, despesas operacionais.
            <br />- <strong>Saldo</strong> — diferença entre entradas e saídas.
            <br />- <strong>Filtros por período</strong> — veja o caixa de hoje, da semana, do mês ou de um período personalizado.
            <br /><br />
            <strong>Lançamentos manuais:</strong>
            <br />- Você pode registrar entradas e saídas manuais que não estão vinculadas a vendas (ex: pagamento de aluguel, compra de material).
            <br />- Clique em <strong>"Nova entrada"</strong> ou <strong>"Nova saída"</strong> e preencha os dados.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Contas a pagar</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O módulo de <strong>Contas a Pagar</strong> (Financeiro > Contas a Pagar) ajuda a controlar as despesas da clínica:
            <br /><br />
            <strong>Cadastrar uma conta:</strong>
            <br />1. Clique em <strong>"Nova conta"</strong>.
            <br />2. Preencha: descrição, valor, data de vencimento, fornecedor, centro de custo.
            <br />3. Salve.
            <br /><br />
            <strong>Gerenciar contas:</strong>
            <br />- <strong>Pendentes</strong> — contas que ainda não foram pagas.
            <br />- <strong>Vencidas</strong> — contas com data de vencimento ultrapassada (destacadas em vermelho).
            <br />- <strong>Pagas</strong> — contas já quitadas.
            <br /><br />
            <strong>Para pagar uma conta:</strong> Clique na conta e depois em "Registrar pagamento". Informe a data e forma de pagamento.
            <br /><br />
            <strong>Centros de custo:</strong> Categorize suas despesas por centro de custo (ex: aluguel, materiais, marketing, folha de pagamento) para ter relatórios mais detalhados.
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Relatórios financeiros</div>
        <div class="lc-card__body" style="line-height:1.6;">
            O sistema oferece relatórios para análise financeira:
            <br /><br />
            - <strong>Faturamento por período</strong> — total faturado por dia, semana ou mês.
            <br />- <strong>Faturamento por profissional</strong> — quanto cada profissional gerou de receita.
            <br />- <strong>Faturamento por serviço</strong> — quais serviços geram mais receita.
            <br />- <strong>Formas de pagamento</strong> — distribuição entre dinheiro, cartão, PIX, etc.
            <br />- <strong>Contas a pagar vs. recebido</strong> — visão de despesas versus receitas.
            <br /><br />
            <strong>Como acessar:</strong> Financeiro > Relatórios. Use os filtros de período e categoria para refinar os dados.
            <br /><br />
            <strong>Exportação:</strong> Os relatórios podem ser exportados para análise externa quando disponível.
        </div>
    </div>

    <div class="lc-muted" style="margin-top:24px; padding: 10px 2px; text-align:center;">
        <?= htmlspecialchars($seoSiteName !== '' ? $seoSiteName : 'LumiClinic', ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
</body>
</html>