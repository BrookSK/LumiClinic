<?php
$csrf   = $_SESSION['_csrf'] ?? '';
$title  = 'Contas a Pagar';
$from   = $from ?? date('Y-m-01');
$to     = $to ?? date('Y-m-t');
$status = (string)($status ?? 'open');
$items  = $items ?? [];
$total  = (float)($total ?? 0);
$cost_centers = $cost_centers ?? [];
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

$ccMap = [];
foreach ($cost_centers as $c) { $ccMap[(int)$c['id']] = $c; }

$statusLabel = ['open' => 'Em aberto', 'paid' => 'Paga', 'all' => 'Todas'];
$typeLabel = ['single' => 'Única', 'installments' => 'Parcelada', 'recurring_monthly' => 'Recorrente'];

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
        <div style="font-weight:800; font-size:18px;">Contas a Pagar</div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">
            Total no período: <strong style="color:#b91c1c;">R$ <?= number_format($total, 2, ',', '.') ?></strong>
        </div>
    </div>
    <?php if ($can('finance.ap.manage')): ?>
        <button type="button" class="lc-btn lc-btn--primary" onclick="toggleForm('form-ap')">+ Nova conta</button>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/finance/accounts-payable" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <?php foreach ($statusLabel as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<!-- Formulário nova conta (oculto) -->
<?php if ($can('finance.ap.manage')): ?>
<div id="form-ap" style="display:none; margin-bottom:14px;">
    <div class="lc-card">
        <div class="lc-card__header" style="font-weight:700;">Nova conta a pagar</div>
        <div class="lc-card__body">
            <div class="lc-muted" style="font-size:12px; margin-bottom:12px;">
                <strong>Única:</strong> paga uma vez só.
                <strong>Parcelada:</strong> divide em X parcelas mensais (ex: equipamento em 12x).
                <strong>Recorrente:</strong> repete todo mês até a data final (ex: aluguel, conta de luz).
            </div>
            <form method="post" action="/finance/accounts-payable/create" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr; align-items:end;">
                    <div class="lc-field">
                        <label class="lc-label">Título</label>
                        <input class="lc-input" type="text" name="title" required placeholder="Ex: Aluguel, Equipamento laser..." />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Fornecedor (opcional)</label>
                        <input class="lc-input" type="text" name="vendor_name" placeholder="Ex: Imobiliária, Fornecedor X..." />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Tipo</label>
                        <select class="lc-select" name="payable_type" id="ap_type" onchange="toggleApFields()">
                            <option value="single">Conta única</option>
                            <option value="installments">Parcelada</option>
                            <option value="recurring_monthly">Recorrente mensal</option>
                        </select>
                    </div>
                </div>

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr 1fr; align-items:end; margin-top:10px;">
                    <div class="lc-field">
                        <label class="lc-label">Valor (R$)</label>
                        <input class="lc-input" type="text" name="amount" required placeholder="0,00" />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">1º vencimento</label>
                        <input class="lc-input" type="date" name="start_due_date" value="<?= date('Y-m-d') ?>" required />
                    </div>
                    <div class="lc-field" id="ap_installments_field">
                        <label class="lc-label">Nº de parcelas</label>
                        <input class="lc-input" type="number" name="total_installments" min="1" max="60" value="1" />
                    </div>
                    <div class="lc-field" id="ap_recurrence_field" style="display:none;">
                        <label class="lc-label">Recorrente até</label>
                        <input class="lc-input" type="date" name="recurrence_until" />
                    </div>
                </div>

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end; margin-top:10px;">
                    <div class="lc-field">
                        <label class="lc-label">Centro de custo (opcional)</label>
                        <select class="lc-select" name="cost_center_id">
                            <option value="0">Nenhum</option>
                            <?php foreach ($cost_centers as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Descrição (opcional)</label>
                        <input class="lc-input" type="text" name="description" />
                    </div>
                </div>

                <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
                    <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleForm('form-ap')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Lista -->
<div class="lc-card">
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($items)): ?>
            <div class="lc-muted" style="padding:24px; text-align:center;">Nenhuma conta no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Vencimento</th>
                    <th>Título</th>
                    <th>Fornecedor</th>
                    <th>Parcela</th>
                    <th style="text-align:right;">Valor</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php
                    $st = (string)($it['status'] ?? '');
                    $isOpen = $st === 'open';
                    $dueDateRaw = (string)($it['due_date'] ?? '');
                    $dueFmt = '';
                    try { $dueFmt = (new \DateTimeImmutable($dueDateRaw))->format('d/m/Y'); } catch (\Throwable $e) { $dueFmt = $dueDateRaw; }
                    $isOverdue = $isOpen && $dueDateRaw !== '' && $dueDateRaw < date('Y-m-d');
                    ?>
                    <tr style="<?= $isOverdue ? 'background:rgba(185,28,28,.04);' : '' ?>">
                        <td style="white-space:nowrap; font-size:13px; <?= $isOverdue ? 'color:#b91c1c; font-weight:700;' : '' ?>">
                            <?= htmlspecialchars($dueFmt, ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($isOverdue): ?><span style="font-size:11px;"> (vencida)</span><?php endif; ?>
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="lc-muted" style="font-size:12px;"><?= htmlspecialchars((string)($it['vendor_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="lc-muted" style="font-size:12px;"><?= (int)($it['installment_no'] ?? 0) > 0 ? ((int)$it['installment_no'] . 'ª') : '—' ?></td>
                        <td style="text-align:right; font-weight:700;">R$ <?= number_format((float)($it['amount'] ?? 0), 2, ',', '.') ?></td>
                        <td>
                            <?php if ($isOpen): ?>
                                <span class="lc-badge lc-badge--primary" style="font-size:11px;">Em aberto</span>
                            <?php else: ?>
                                <span class="lc-badge lc-badge--success" style="font-size:11px;">Paga</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isOpen && $can('finance.ap.manage')): ?>
                                <form method="post" action="/finance/accounts-payable/pay" class="lc-flex lc-gap-sm" style="align-items:center;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="installment_id" value="<?= (int)($it['installment_id'] ?? 0) ?>" />
                                    <input type="hidden" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="paid_on" value="<?= date('Y-m-d') ?>" />
                                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">✓ Pagar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function toggleApFields() {
    var type = document.getElementById('ap_type').value;
    var inst = document.getElementById('ap_installments_field');
    var rec  = document.getElementById('ap_recurrence_field');
    if (inst) inst.style.display = type === 'installments' ? 'block' : 'none';
    if (rec)  rec.style.display  = type === 'recurring_monthly' ? 'block' : 'none';
}
toggleApFields();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
