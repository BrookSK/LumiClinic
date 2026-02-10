<?php
/** @var string $from */
/** @var string $to */
/** @var list<array<string,mixed>> $movements */
/** @var list<array<string,mixed>> $materials */
/** @var string $error */
/** @var int $page */
/** @var int $per_page */
/** @var bool $has_next */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Estoque - Movimentações';

$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 100;
$hasNext = isset($has_next) ? (bool)$has_next : false;

$matMap = [];
foreach ($materials as $m) {
    $matMap[(int)$m['id']] = $m;
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
        <form method="get" action="/stock/movements" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
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
            <a class="lc-btn lc-btn--secondary" href="/stock/materials">Materiais</a>
        </form>
    </div>
</div>

<?php if (isset($can) && $can('stock.movements.create')): ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Nova movimentação</div>
        <div class="lc-card__body">
            <form method="post" action="/stock/movements/create" class="lc-form lc-grid lc-gap-grid lc-grid--end" style="grid-template-columns: 2fr 1fr 1fr 1fr 2fr;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="lc-field">
                    <label class="lc-label">Material</label>
                    <select class="lc-select" name="material_id">
                        <?php foreach ($materials as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8') ?> (<?= number_format((float)$m['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$m['unit'], ENT_QUOTES, 'UTF-8') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Tipo</label>
                    <select class="lc-select" name="type">
                        <option value="entry">Entrada</option>
                        <option value="exit">Saída</option>
                        <option value="adjustment">Ajuste (define estoque)</option>
                        <option value="loss">Perda</option>
                        <option value="expiration">Vencimento</option>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Quantidade</label>
                    <input class="lc-input" type="text" name="quantity" required />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Motivo perda</label>
                    <select class="lc-select" name="loss_reason">
                        <option value="">-</option>
                        <option value="expiration">Vencimento</option>
                        <option value="breakage">Quebra</option>
                        <option value="contamination">Contaminação</option>
                        <option value="operational_error">Erro operacional</option>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Observações</label>
                    <input class="lc-input" type="text" name="notes" />
                </div>

                <div style="grid-column: 1 / -1;">
                    <button class="lc-btn" type="submit">Registrar</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Movimentações</div>
    <div class="lc-card__body">
        <?php if ($movements === []): ?>
            <div class="lc-muted">Nenhuma movimentação no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Material</th>
                    <th>Tipo</th>
                    <th>Qtd</th>
                    <th>Custo total</th>
                    <th>Motivo</th>
                    <th>Obs</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($movements as $mv): ?>
                    <?php
                        $mid = (int)$mv['material_id'];
                        $mname = isset($matMap[$mid]) ? (string)$matMap[$mid]['name'] : ('#' . $mid);
                        $munit = isset($matMap[$mid]) ? (string)$matMap[$mid]['unit'] : '';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$mv['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($mname, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$mv['type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$mv['quantity'], 3, ',', '.') ?> <?= htmlspecialchars($munit, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$mv['total_cost_snapshot'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)($mv['loss_reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($mv['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
            <div class="lc-muted">Página <?= (int)$page ?></div>
            <div class="lc-flex lc-gap-sm">
                <?php if ($page > 1): ?>
                    <a class="lc-btn lc-btn--secondary" href="/stock/movements?from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
                <?php endif; ?>
                <?php if ($hasNext): ?>
                    <a class="lc-btn lc-btn--secondary" href="/stock/movements?from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
