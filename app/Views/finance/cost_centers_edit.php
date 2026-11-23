<?php
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Financeiro - Editar centro de custo';
$row = $row ?? null;
$chartAccounts = $chart_accounts ?? [];
$currentChartAccount = $current_chart_account ?? null;
$natureLabels = \App\Services\Finance\ChartOfAccountsService::NATURES;
$selectedAccountId = (int)($row['chart_account_id'] ?? 0);

$accountsByNature = [];
foreach ($chartAccounts as $a) {
    $accountsByNature[(string)($a['nature'] ?? '')][] = $a;
}
$selectedInList = false;
foreach ($chartAccounts as $a) {
    if ((int)$a['id'] === $selectedAccountId) {
        $selectedInList = true;
        break;
    }
}
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

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

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Editar centro de custo</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/finance/chart-of-accounts">Plano de Contas</a>
        <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Voltar</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Cadastro</div>
    <div class="lc-card__body">
        <?php if ($can('finance.cost_centers.manage')): ?>
            <form method="post" action="/finance/cost-centers/update" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 200px; align-items:end;">
                    <div class="lc-field">
                        <label class="lc-label">Nome</label>
                        <input class="lc-input" type="text" name="name" maxlength="190" value="<?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Código (opcional)</label>
                        <input class="lc-input" type="text" name="code" maxlength="32" placeholder="Ex: CC-001" value="<?= htmlspecialchars((string)($row['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                    </div>
                </div>

                <div class="lc-field" style="margin-top:10px;">
                    <label class="lc-label">Conta contábil</label>
                    <?php if (empty($chartAccounts) && $currentChartAccount === null): ?>
                        <input type="hidden" name="chart_account_id" value="0" />
                        <div class="lc-muted" style="font-size:13px; line-height:1.5;">
                            A clínica ainda não tem um plano de contas.
                            <a href="/finance/chart-of-accounts">Criar plano de contas</a>
                        </div>
                    <?php else: ?>
                        <select class="lc-select" name="chart_account_id" style="max-width:480px;">
                            <option value="0">Não vinculada</option>
                            <?php if ($currentChartAccount !== null && !$selectedInList): ?>
                                <option value="<?= (int)$currentChartAccount['id'] ?>" selected>
                                    <?= htmlspecialchars((string)$currentChartAccount['code'] . ' - ' . (string)$currentChartAccount['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (string)($currentChartAccount['status'] ?? '') === 'active' ? 'natureza não permitida, escolha outra' : 'inativa' ?>)
                                </option>
                            <?php endif; ?>
                            <?php foreach ($natureLabels as $natureKey => $natureLabel): ?>
                                <?php if (empty($accountsByNature[$natureKey])) continue; ?>
                                <optgroup label="<?= htmlspecialchars($natureLabel, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php foreach ($accountsByNature[$natureKey] as $a): ?>
                                        <option value="<?= (int)$a['id'] ?>" <?= (int)$a['id'] === $selectedAccountId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string)$a['code'] . ' - ' . (string)$a['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <div class="lc-muted" style="font-size:12px; margin-top:4px; line-height:1.5;">
                            Vincule a uma conta do <a href="/finance/chart-of-accounts">Plano de Contas</a> para que os lançamentos deste centro de custo saiam classificados nos relatórios para a contabilidade.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="lc-field" style="margin-top:10px;">
                    <label class="lc-label">Descrição / observação para a contabilidade</label>
                    <textarea class="lc-input" name="description" rows="3" maxlength="255" placeholder="Ex: Provisão mensal do 13º dos funcionários CLT"><?= htmlspecialchars((string)($row['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                    <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Voltar</a>
                </div>
            </form>
        <?php else: ?>
            <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Voltar</a>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
