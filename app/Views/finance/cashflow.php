<?php
/** @var string $from */
/** @var string $to */
/** @var list<array<string,mixed>> $entries */
/** @var array{in:float,out:float,balance:float} $totals */
/** @var list<array<string,mixed>> $cost_centers */
/** @var string $error */
/** @var int $page */
/** @var int $per_page */
/** @var bool $has_next */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Financeiro - Fluxo de Caixa';

$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 100;
$hasNext = isset($has_next) ? (bool)$has_next : false;

$ccMap = [];
foreach ($cost_centers as $c) {
    $ccMap[(int)$c['id']] = $c;
}

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom: 16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/finance/cashflow" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
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
            <button class="lc-btn" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Novo lançamento</div>
    <div class="lc-card__body">
        <div class="lc-flex lc-flex--between lc-flex--wrap" style="gap:10px; margin-bottom:10px;">
            <div></div>
            <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Centros de custo</a>
        </div>
        <form method="post" action="/finance/entries/create" class="lc-form lc-grid lc-gap-grid lc-grid--end" style="grid-template-columns: 1fr 1fr 1fr 1fr 2fr;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="kind">
                    <option value="in">Entrada</option>
                    <option value="out">Saída</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Data</label>
                <input class="lc-input" type="date" name="occurred_on" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Valor (R$)</label>
                <input class="lc-input" type="text" name="amount" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Centro de custo</label>
                <select class="lc-select" name="cost_center_id">
                    <option value="0">-</option>
                    <?php foreach ($cost_centers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Descrição</label>
                <input class="lc-input" type="text" name="description" />
            </div>

            <div style="grid-column: 1 / -1;">
                <button class="lc-btn" type="submit">Adicionar</button>
            </div>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Totais</div>
    <div class="lc-card__body lc-grid lc-grid--3 lc-gap-grid">
        <div><strong>Entradas:</strong> R$ <?= number_format((float)$totals['in'], 2, ',', '.') ?></div>
        <div><strong>Saídas:</strong> R$ <?= number_format((float)$totals['out'], 2, ',', '.') ?></div>
        <div><strong>Saldo:</strong> R$ <?= number_format((float)$totals['balance'], 2, ',', '.') ?></div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Lançamentos</div>
    <div class="lc-card__body">
        <?php if ($entries === []): ?>
            <div class="lc-muted">Nenhum lançamento no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Centro de custo</th>
                    <th>Descrição</th>
                    <th>Vinculado</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $e): ?>
                    <?php
                        $cc = $e['cost_center_id'] === null ? null : (int)$e['cost_center_id'];
                        $ccName = $cc !== null && isset($ccMap[$cc]) ? (string)$ccMap[$cc]['name'] : '-';
                        $linked = [];
                        if ($e['sale_id'] !== null) { $linked[] = 'sale#' . (int)$e['sale_id']; }
                        if ($e['payment_id'] !== null) { $linked[] = 'payment#' . (int)$e['payment_id']; }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$e['occurred_on'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$e['kind'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$e['amount'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($ccName, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($e['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $linked === [] ? '-' : htmlspecialchars(implode(', ', $linked), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="post" action="/finance/entries/delete">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="entry_id" value="<?= (int)$e['id'] ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
            <div class="lc-muted">Página <?= (int)$page ?></div>
            <div class="lc-flex lc-gap-sm">
                <?php if ($page > 1): ?>
                    <a class="lc-btn lc-btn--secondary" href="/finance/cashflow?from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
                <?php endif; ?>
                <?php if ($hasNext): ?>
                    <a class="lc-btn lc-btn--secondary" href="/finance/cashflow?from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
