<?php
$csrf    = $_SESSION['_csrf'] ?? '';
$title   = 'Fluxo de Caixa';
$from    = $from ?? date('Y-m-01');
$to      = $to ?? date('Y-m-t');
$entries = $entries ?? [];
$totals  = $totals ?? ['in' => 0, 'out' => 0, 'balance' => 0];
$cost_centers = $cost_centers ?? [];
$page    = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 100;
$hasNext = isset($has_next) ? (bool)$has_next : false;

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

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div style="font-weight:800; font-size:18px;">Fluxo de Caixa</div>
    <div class="lc-flex lc-gap-sm">
        <?php if ($can('finance.entries.create')): ?>
            <button type="button" class="lc-btn lc-btn--primary" onclick="toggleForm('form-entry')">+ Lançamento</button>
        <?php endif; ?>
    </div>
</div>

<!-- Totais -->
<div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(3, minmax(0,1fr)); margin-bottom:14px;">
    <div class="lc-card" style="margin:0; border-left:4px solid #16a34a;">
        <div class="lc-card__body" style="padding:14px;">
            <div class="lc-muted" style="font-size:12px;">Entradas</div>
            <div style="font-weight:800; font-size:22px; color:#16a34a; margin-top:4px;">R$ <?= number_format((float)$totals['in'], 2, ',', '.') ?></div>
        </div>
    </div>
    <div class="lc-card" style="margin:0; border-left:4px solid #b91c1c;">
        <div class="lc-card__body" style="padding:14px;">
            <div class="lc-muted" style="font-size:12px;">Saídas</div>
            <div style="font-weight:800; font-size:22px; color:#b91c1c; margin-top:4px;">R$ <?= number_format((float)$totals['out'], 2, ',', '.') ?></div>
        </div>
    </div>
    <div class="lc-card" style="margin:0; border-left:4px solid #eeb810;">
        <div class="lc-card__body" style="padding:14px;">
            <div class="lc-muted" style="font-size:12px;">Saldo</div>
            <div style="font-weight:800; font-size:22px; color:<?= (float)$totals['balance'] >= 0 ? '#16a34a' : '#b91c1c' ?>; margin-top:4px;">R$ <?= number_format((float)$totals['balance'], 2, ',', '.') ?></div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/finance/cashflow" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
            <input type="hidden" name="page" value="1" />
            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<!-- Formulário de lançamento (oculto) -->
<?php if ($can('finance.entries.create')): ?>
<div id="form-entry" style="display:none; margin-bottom:14px;">
    <div class="lc-card">
        <div class="lc-card__header" style="font-weight:700;">Novo lançamento</div>
        <div class="lc-card__body">
            <form method="post" action="/finance/entries/create" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 120px 1fr 120px 1fr; align-items:end;">
                    <div class="lc-field">
                        <label class="lc-label">Tipo</label>
                        <select class="lc-select" name="kind">
                            <option value="in">Entrada</option>
                            <option value="out">Saída</option>
                        </select>
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Descrição</label>
                        <input class="lc-input" type="text" name="description" placeholder="Ex: Pagamento consulta, Aluguel..." />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Valor (R$)</label>
                        <input class="lc-input" type="text" name="amount" required placeholder="0,00" />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Data</label>
                        <input class="lc-input" type="date" name="occurred_on" value="<?= date('Y-m-d') ?>" />
                    </div>
                </div>
                <div class="lc-field" style="margin-top:8px;">
                    <label class="lc-label">Centro de custo (opcional)</label>
                    <select class="lc-select" name="cost_center_id" style="max-width:300px;">
                        <option value="0">Nenhum</option>
                        <?php foreach ($cost_centers as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                    <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleForm('form-entry')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Lançamentos -->
<div class="lc-card">
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($entries)): ?>
            <div class="lc-muted" style="padding:24px; text-align:center;">Nenhum lançamento no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Centro de custo</th>
                    <th style="text-align:right;">Valor</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $e): ?>
                    <?php
                    $kind = (string)($e['kind'] ?? '');
                    $isIn = $kind === 'in';
                    $ccId = (int)($e['cost_center_id'] ?? 0);
                    $ccName = $ccId > 0 && isset($ccMap[$ccId]) ? (string)$ccMap[$ccId]['name'] : '';
                    $dateFmt = '';
                    try { $dateFmt = (new \DateTimeImmutable((string)($e['occurred_on'] ?? '')))->format('d/m/Y'); } catch (\Throwable $ex) { $dateFmt = (string)($e['occurred_on'] ?? ''); }
                    ?>
                    <tr>
                        <td style="white-space:nowrap; font-size:13px;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div style="font-size:13px;"><?= htmlspecialchars((string)($e['description'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td class="lc-muted" style="font-size:12px;"><?= htmlspecialchars($ccName !== '' ? $ccName : '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:right; font-weight:700; color:<?= $isIn ? '#16a34a' : '#b91c1c' ?>;">
                            <?= $isIn ? '+' : '-' ?> R$ <?= number_format((float)$e['amount'], 2, ',', '.') ?>
                        </td>
                        <td>
                            <?php if ($can('finance.entries.delete')): ?>
                                <form method="post" action="/finance/entries/delete" onsubmit="return confirm('Excluir?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="entry_id" value="<?= (int)$e['id'] ?>" />
                                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px;">✕</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="lc-flex lc-flex--between lc-flex--wrap" style="padding:12px 16px;">
                <div class="lc-muted" style="font-size:12px;">Página <?= (int)$page ?></div>
                <div class="lc-flex lc-gap-sm">
                    <?php if ($page > 1): ?>
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/cashflow?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&per_page=<?= $perPage ?>&page=<?= $page-1 ?>">Anterior</a>
                    <?php endif; ?>
                    <?php if ($hasNext): ?>
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/cashflow?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&per_page=<?= $perPage ?>&page=<?= $page+1 ?>">Próxima</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
