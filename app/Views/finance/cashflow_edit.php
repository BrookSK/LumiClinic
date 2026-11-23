<?php
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Financeiro - Editar lançamento';
$row = $row ?? [];
$locked = !empty($locked);
$cost_centers = $cost_centers ?? [];
$from = (string)($from ?? '');
$to = (string)($to ?? '');
$error = $error ?? '';
$paymentMethods = \App\Services\Finance\ChartOfAccountsService::PAYMENT_METHODS;
$selectedCc = (int)($row['cost_center_id'] ?? 0);
$currentKind = (string)($row['kind'] ?? 'in');
$currentMethod = (string)($row['method'] ?? '');
$backUrl = '/finance/cashflow' . ($from !== '' && $to !== '' ? '?from=' . urlencode($from) . '&to=' . urlencode($to) : '');
$amountValue = number_format((float)($row['amount'] ?? 0), 2, ',', '');

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Editar lançamento</div>
    <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($locked): ?>
    <div class="lc-alert lc-alert--info" style="margin-bottom:12px;">
        Este lançamento foi gerado por uma venda, pagamento ou conta a pagar. Tipo, valor e data não podem ser alterados aqui; ajuste na origem do lançamento.
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Lançamento</div>
    <div class="lc-card__body">
        <form method="post" action="/finance/cashflow/update" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>" />
            <input type="hidden" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 140px 1fr 140px 160px; align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Tipo</label>
                    <select class="lc-select" name="kind" <?= $locked ? 'disabled' : '' ?>>
                        <option value="in" <?= $currentKind === 'in' ? 'selected' : '' ?>>Entrada</option>
                        <option value="out" <?= $currentKind === 'out' ? 'selected' : '' ?>>Saída</option>
                    </select>
                    <?php if ($locked): ?>
                        <input type="hidden" name="kind" value="<?= htmlspecialchars($currentKind, ENT_QUOTES, 'UTF-8') ?>" />
                    <?php endif; ?>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Descrição</label>
                    <input class="lc-input" type="text" name="description" maxlength="255" value="<?= htmlspecialchars((string)($row['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Valor (R$)</label>
                    <input class="lc-input" type="text" name="amount" required value="<?= htmlspecialchars($amountValue, ENT_QUOTES, 'UTF-8') ?>" <?= $locked ? 'readonly' : '' ?> />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Data</label>
                    <input class="lc-input" type="date" name="occurred_on" value="<?= htmlspecialchars((string)($row['occurred_on'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $locked ? 'readonly' : '' ?> />
                </div>
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:8px;">
                <div class="lc-field">
                    <label class="lc-label">Centro de custo (opcional)</label>
                    <select class="lc-select" name="cost_center_id" style="max-width:300px;">
                        <option value="0">Nenhum</option>
                        <?php foreach ($cost_centers as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $selectedCc ? 'selected' : '' ?>><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Forma de pagamento (opcional)</label>
                    <select class="lc-select" name="method" style="max-width:240px;">
                        <option value="">Não informada</option>
                        <?php foreach ($paymentMethods as $k => $label): ?>
                            <?php if ($k === 'card' && $currentMethod !== 'card') continue; ?>
                            <option value="<?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?>" <?= (string)$k === $currentMethod ? 'selected' : '' ?>><?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>">Voltar</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
