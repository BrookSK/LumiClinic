<?php
/** @var string $from */
/** @var string $to */
/** @var string $status */
/** @var list<array<string,mixed>> $items */
/** @var float $total */
/** @var list<array<string,mixed>> $cost_centers */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Financeiro - Contas a Pagar';

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$ccMap = [];
foreach (($cost_centers ?? []) as $c) {
    $ccMap[(int)$c['id']] = $c;
}

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Contas a Pagar</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/finance/cashflow">Fluxo de caixa</a>
        <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Centros de custo</a>
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

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/finance/accounts-payable" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Em aberto</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Pagas</option>
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todas</option>
                </select>
            </div>
            <button class="lc-btn" type="submit">Filtrar</button>
        </form>

        <div class="lc-muted" style="margin-top:10px;">
            Total no período: <strong>R$ <?= number_format((float)($total ?? 0), 2, ',', '.') ?></strong>
        </div>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Nova conta</div>
    <div class="lc-card__body">
        <form method="post" action="/finance/accounts-payable/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 200px 160px 160px 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="status" value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Fornecedor (opcional)</label>
                <input class="lc-input" type="text" name="vendor_name" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Título</label>
                <input class="lc-input" type="text" name="title" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="payable_type">
                    <option value="single">Única</option>
                    <option value="installments">Parcelada</option>
                    <option value="recurring_monthly">Recorrente mensal</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">1º vencimento</label>
                <input class="lc-input" type="date" name="start_due_date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Valor (R$)</label>
                <input class="lc-input" type="text" name="amount" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Centro de custo</label>
                <select class="lc-select" name="cost_center_id">
                    <option value="0">-</option>
                    <?php foreach (($cost_centers ?? []) as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Parcelas (se parcelada)</label>
                <input class="lc-input" type="number" name="total_installments" min="1" max="60" value="1" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Recorrência até (se recorrente)</label>
                <input class="lc-input" type="date" name="recurrence_until" />
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Descrição (opcional)</label>
                <input class="lc-input" type="text" name="description" />
            </div>

            <div style="grid-column: 1 / -1;">
                <button class="lc-btn" type="submit">Criar</button>
            </div>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Vencimentos</div>
    <div class="lc-card__body">
        <?php if (!is_array($items) || $items === []): ?>
            <div class="lc-muted">Nenhuma conta no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th>Valor</th>
                    <th>Fornecedor</th>
                    <th>Título</th>
                    <th>Centro de custo</th>
                    <th>Parcela</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php
                        $instId = (int)($it['installment_id'] ?? 0);
                        $cc = $it['cost_center_id'] === null ? null : (int)$it['cost_center_id'];
                        $ccName = $cc !== null && isset($ccMap[$cc]) ? (string)$ccMap[$cc]['name'] : '-';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($it['due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= number_format((float)($it['amount'] ?? 0), 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)($it['vendor_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($ccName, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($it['installment_no'] ?? 0) ?></td>
                        <td>
                            <?php if ((string)($it['status'] ?? '') === 'open'): ?>
                                <form method="post" action="/finance/accounts-payable/pay" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:end;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="installment_id" value="<?= $instId ?>" />
                                    <input type="hidden" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="status" value="<?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?>" />

                                    <input class="lc-input" type="date" name="paid_on" value="<?= htmlspecialchars((string)date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" style="max-width:150px;" />
                                    <input class="lc-input" type="text" name="method" placeholder="Método" style="max-width:140px;" />
                                    <button class="lc-btn lc-btn--secondary" type="submit">Marcar como paga</button>
                                </form>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
