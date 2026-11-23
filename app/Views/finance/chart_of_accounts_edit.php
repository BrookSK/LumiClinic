<?php
$csrf    = $_SESSION['_csrf'] ?? '';
$title   = 'Plano de Contas - Editar conta';
$row     = $row ?? [];
$rows    = $rows ?? [];
$error   = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$natureLabels  = \App\Services\Finance\ChartOfAccountsService::NATURES;
$typeLabels    = \App\Services\Finance\ChartOfAccountsService::TYPES;
$methodLabels  = \App\Services\Finance\ChartOfAccountsService::PAYMENT_METHODS;
$defaultLabels = \App\Services\Finance\ChartOfAccountsService::DEFAULT_FOR;

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
$canManage = $can('finance.cost_centers.manage');

$id = (int)($row['id'] ?? 0);
$parentId = (int)($row['parent_id'] ?? 0);
$curNature = (string)($row['nature'] ?? '');
$curType = (string)($row['account_type'] ?? 'analytic');
$curMethod = (string)($row['payment_method'] ?? '');
$curDefault = (string)($row['default_for'] ?? '');
$curCode = (string)($row['code'] ?? '');

$linked = 0;
$parentOptions = [];
foreach ($rows as $r) {
    $rid = (int)($r['id'] ?? 0);
    if ($rid === $id) {
        $linked = (int)($r['linked_cost_centers'] ?? 0);
        continue;
    }
    if ((string)($r['account_type'] ?? '') !== 'synthetic') {
        continue;
    }
    // Não oferece descendentes da própria conta como pai.
    $rcode = (string)($r['code'] ?? '');
    if ($curCode !== '' && str_starts_with($rcode, $curCode . '.')) {
        continue;
    }
    $parentOptions[] = $r;
}

$disabledAttr = $canManage ? '' : 'disabled';

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Editar conta <?= htmlspecialchars($curCode, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Centros de Custo</a>
        <?php if ($can('finance.reports.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/finance/accounting">Relatórios contábeis</a>
        <?php endif; ?>
        <a class="lc-btn lc-btn--secondary" href="/finance/chart-of-accounts">Voltar</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Conta contábil</div>
    <div class="lc-card__body">
        <form method="post" action="/finance/chart-of-accounts/update" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= $id ?>" />

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Conta pai (opcional)</label>
                    <select class="lc-select" name="parent_id" <?= $disabledAttr ?>>
                        <option value="0">Nenhuma (conta raiz)</option>
                        <?php foreach ($parentOptions as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $parentId ? 'selected' : '' ?>><?= htmlspecialchars((string)$p['code'] . ' - ' . (string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Código</label>
                    <input class="lc-input" type="text" name="code" maxlength="32" required pattern="\d+(\.\d+)*" value="<?= htmlspecialchars($curCode, ENT_QUOTES, 'UTF-8') ?>" <?= $disabledAttr ?> />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" maxlength="190" required value="<?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $disabledAttr ?> />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Natureza</label>
                    <select class="lc-select" name="nature" <?= $disabledAttr ?>>
                        <?php foreach ($natureLabels as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === $curNature ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Tipo</label>
                    <select class="lc-select" name="account_type" <?= $disabledAttr ?>>
                        <?php foreach ($typeLabels as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === $curType ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Forma de pagamento</label>
                    <select class="lc-select" name="payment_method" <?= $disabledAttr ?>>
                        <option value="">Nenhuma</option>
                        <?php foreach ($methodLabels as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === $curMethod ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Conta padrão para</label>
                    <select class="lc-select" name="default_for" <?= $disabledAttr ?>>
                        <option value="">Nenhuma</option>
                        <?php foreach ($defaultLabels as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === $curDefault ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Código externo (contador)</label>
                    <input class="lc-input" type="text" name="external_code" maxlength="64" value="<?= htmlspecialchars((string)($row['external_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $disabledAttr ?> />
                </div>
            </div>

            <div class="lc-field" style="margin-top:10px;">
                <label class="lc-label">Descrição</label>
                <textarea class="lc-input" name="description" rows="2" maxlength="255" <?= $disabledAttr ?>><?= htmlspecialchars((string)($row['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="lc-muted" style="font-size:12px; margin-top:8px; line-height:1.5;">
                Centros de custo vinculados a esta conta: <strong><?= $linked ?></strong>.
                A forma de pagamento e a conta financeira padrão só valem para contas analíticas do Ativo. Centros de custo só podem ser vinculados a contas de Receita, Custo / Despesa ou Passivo.
                Contas com contas filhas não podem mudar de código, natureza ou tipo.
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <?php if ($canManage): ?>
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <?php endif; ?>
                <a class="lc-btn lc-btn--secondary" href="/finance/chart-of-accounts">Voltar</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
