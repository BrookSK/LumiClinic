<?php
$csrf    = $_SESSION['_csrf'] ?? '';
$title   = 'Centros de Custo';
$rows    = $rows ?? [];
$chartAccounts = $chart_accounts ?? [];
$natureLabels  = \App\Services\Finance\ChartOfAccountsService::NATURES;
$accountsByNature = [];
foreach ($chartAccounts as $a) {
    $accountsByNature[(string)($a['nature'] ?? '')][] = $a;
}
$error   = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) {
        if (in_array($p, $perms['deny'], true)) return false;
        return in_array($p, $perms['allow'], true);
    }
    return in_array($p, $perms, true);
};

ob_start();
?>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;">Centros de Custo</div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px; max-width:620px; line-height:1.5;">
            Centros de custo servem para categorizar suas despesas e receitas. Ex: "Aluguel", "Marketing", "Materiais", "Salários". Assim você sabe para onde o dinheiro está indo.
            Vincule cada centro de custo a uma conta do Plano de Contas para que os lançamentos saiam classificados nos relatórios para a contabilidade.
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/finance/chart-of-accounts">Plano de Contas</a>
        <a class="lc-btn lc-btn--secondary" href="/finance/cashflow">Fluxo de Caixa</a>
    </div>
</div>

<!-- Criar -->
<?php if ($can('finance.cost_centers.manage')): ?>
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="post" action="/finance/cost-centers/create" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="lc-field" style="flex:1; min-width:200px;">
                <label class="lc-label">Nome do centro de custo</label>
                <input class="lc-input" type="text" name="name" maxlength="190" required placeholder="Ex: Aluguel, Marketing, Materiais..." />
            </div>
            <div class="lc-field" style="width:140px;">
                <label class="lc-label">Código (opcional)</label>
                <input class="lc-input" type="text" name="code" maxlength="32" placeholder="Ex: CC-001" />
            </div>
            <div class="lc-field" style="flex:1; min-width:220px;">
                <label class="lc-label">Conta contábil (opcional)</label>
                <?php if (empty($chartAccounts)): ?>
                    <div class="lc-muted" style="font-size:12px; line-height:1.4; padding:8px 0;">
                        Sem plano de contas. <a href="/finance/chart-of-accounts">Criar plano de contas</a>
                    </div>
                <?php else: ?>
                    <select class="lc-select" name="chart_account_id">
                        <option value="0">Não vinculada</option>
                        <?php foreach ($natureLabels as $natureKey => $natureLabel): ?>
                            <?php if (empty($accountsByNature[$natureKey])) continue; ?>
                            <optgroup label="<?= htmlspecialchars($natureLabel, ENT_QUOTES, 'UTF-8') ?>">
                                <?php foreach ($accountsByNature[$natureKey] as $a): ?>
                                    <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars((string)$a['code'] . ' - ' . (string)$a['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Lista -->
<?php if (empty($rows)): ?>
    <div class="lc-card">
        <div class="lc-card__body" style="text-align:center; padding:30px;">
            <div class="lc-muted">Nenhum centro de custo cadastrado.</div>
            <div class="lc-muted" style="font-size:12px; margin-top:6px;">Crie centros de custo para organizar suas finanças por categoria.</div>
        </div>
    </div>
<?php else: ?>
    <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
        <?php foreach ($rows as $r): ?>
            <?php
            $id = (int)($r['id'] ?? 0);
            $st = (string)($r['status'] ?? 'active');
            $active = $st === 'active';
            $ccCode = trim((string)($r['code'] ?? ''));
            $accCode = trim((string)($r['chart_account_code'] ?? ''));
            $accName = trim((string)($r['chart_account_name'] ?? ''));
            ?>
            <div class="lc-card" style="margin:0; opacity:<?= $active ? '1' : '.5' ?>;">
                <div style="padding:16px;">
                    <div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:8px;">
                        <div style="font-weight:700;"><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <span class="lc-badge <?= $active ? 'lc-badge--success' : 'lc-badge--secondary' ?>" style="font-size:11px;">
                            <?= $active ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </div>
                    <?php if ($ccCode !== ''): ?>
                        <div class="lc-muted" style="font-size:12px; margin-bottom:4px;">Código: <?= htmlspecialchars($ccCode, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div style="font-size:12px; margin-bottom:10px;">
                        <?php if ($accCode !== '' || $accName !== ''): ?>
                            <span class="lc-muted">Conta contábil:</span>
                            <?= htmlspecialchars($accCode . ' - ' . $accName, ENT_QUOTES, 'UTF-8') ?>
                        <?php else: ?>
                            <span class="lc-badge lc-badge--secondary" style="font-size:11px;">Sem conta contábil</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($can('finance.cost_centers.manage')): ?>
                        <div class="lc-flex lc-gap-sm lc-flex--wrap">
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/cost-centers/edit?id=<?= $id ?>">Editar</a>
                            <form method="post" action="/finance/cost-centers/status" style="margin:0;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= $id ?>" />
                                <input type="hidden" name="status" value="<?= $active ? 'disabled' : 'active' ?>" />
                                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit"><?= $active ? 'Inativar' : 'Ativar' ?></button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
