<?php
/** @var array<string,mixed> $inventory */
/** @var list<array<string,mixed>> $items */
/** @var string $error */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Estoque - Inventário';

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom: 16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php
    $invId = (int)($inventory['id'] ?? 0);
    $status = (string)($inventory['status'] ?? '');
    $statusLabelMap = [
        'draft' => 'Rascunho',
        'confirmed' => 'Confirmado',
        'cancelled' => 'Cancelado',
    ];
    $statusLabel = (string)($statusLabelMap[$status] ?? $status);
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Inventário #<?= (int)$invId ?> (<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>)</div>
    <div class="lc-card__body">
        <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm">
            <div class="lc-muted">
                Criado em: <?= htmlspecialchars((string)($inventory['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                | Confirmado em: <?= ($inventory['confirmed_at'] ?? null) === null ? '-' : htmlspecialchars((string)$inventory['confirmed_at'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="lc-flex lc-gap-sm">
                <a class="lc-btn lc-btn--secondary" href="/stock/inventory">Voltar</a>
                <a class="lc-btn lc-btn--secondary" href="/stock/movements">Movimentações</a>
            </div>
        </div>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Contagem</div>
    <div class="lc-card__body">
        <?php if (($items ?? []) === []): ?>
            <div class="lc-muted">Sem itens.</div>
        <?php else: ?>
            <form method="post" action="/stock/inventory/update" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)$invId ?>" />

                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Material</th>
                        <th>Un</th>
                        <th>Qtde sistema (snapshot)</th>
                        <th>Qtde contada</th>
                        <th>Divergência</th>
                        <th>Custo (delta)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($items ?? []) as $it): ?>
                        <?php
                            $mid = (int)($it['material_id'] ?? 0);
                            $delta = (float)($it['qty_delta'] ?? 0);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($it['material_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['material_unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)($it['qty_system_snapshot'] ?? 0), 3, ',', '.') ?></td>
                            <td>
                                <?php if ($status === 'draft'): ?>
                                    <input class="lc-input" style="max-width:140px;" type="text" name="qty[<?= (int)$mid ?>]" value="<?= htmlspecialchars(number_format((float)($it['qty_counted'] ?? 0), 3, '.', ''), ENT_QUOTES, 'UTF-8') ?>" />
                                <?php else: ?>
                                    <?= number_format((float)($it['qty_counted'] ?? 0), 3, ',', '.') ?>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($delta, 3, ',', '.') ?></td>
                            <td>R$ <?= number_format((float)($it['total_cost_delta_snapshot'] ?? 0), 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($status === 'draft'): ?>
                    <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                        <button class="lc-btn" type="submit">Salvar contagem</button>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($status === 'draft'): ?>
    <div class="lc-card">
        <div class="lc-card__header">Confirmar inventário</div>
        <div class="lc-card__body">
            <form method="post" action="/stock/inventory/confirm" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)$invId ?>" />
                <button class="lc-btn" type="submit">Confirmar e ajustar estoque</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
