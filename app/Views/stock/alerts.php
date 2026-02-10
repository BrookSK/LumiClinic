<?php

/** @var int $days */
/** @var list<array<string,mixed>> $low_stock */
/** @var list<array<string,mixed>> $out_of_stock */
/** @var list<array<string,mixed>> $expiring_soon */
/** @var list<array<string,mixed>> $expired */

$title = 'Alertas de Estoque';
ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/stock/alerts" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
            <div class="lc-field">
                <label class="lc-label">Dias para validade próxima</label>
                <input class="lc-input" type="number" name="days" min="1" max="365" value="<?= (int)$days ?>" />
            </div>
            <div>
                <button class="lc-btn" type="submit">Atualizar</button>
                <a class="lc-btn lc-btn--secondary" href="/stock/materials">Materiais</a>
                <a class="lc-btn lc-btn--secondary" href="/stock/movements">Movimentações</a>
            </div>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Ruptura (estoque zerado)</div>
    <div class="lc-card__body">
        <?php if ($out_of_stock === []): ?>
            <div class="lc-muted">Nenhum material em ruptura.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Material</th>
                    <th>Estoque</th>
                    <th>Mínimo</th>
                    <th>Sugestão compra</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($out_of_stock as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['stock_minimum'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['suggested_buy'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Estoque baixo (abaixo do mínimo)</div>
    <div class="lc-card__body">
        <?php if ($low_stock === []): ?>
            <div class="lc-muted">Nenhum material abaixo do mínimo.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Material</th>
                    <th>Estoque</th>
                    <th>Mínimo</th>
                    <th>Sugestão compra</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($low_stock as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['stock_minimum'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['suggested_buy'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Validade vencida</div>
    <div class="lc-card__body">
        <?php if ($expired === []): ?>
            <div class="lc-muted">Nenhum material vencido.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Material</th>
                    <th>Validade</th>
                    <th>Estoque</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expired as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$it['validity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Validade próxima (até <?= (int)$days ?> dias)</div>
    <div class="lc-card__body">
        <?php if ($expiring_soon === []): ?>
            <div class="lc-muted">Nenhum material com validade próxima.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Material</th>
                    <th>Validade</th>
                    <th>Estoque</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expiring_soon as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$it['validity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
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
?>
