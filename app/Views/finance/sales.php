<?php
/** @var list<array<string,mixed>> $sales */
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>> $services */
/** @var list<array<string,mixed>> $packages */
/** @var list<array<string,mixed>> $plans */
/** @var string $error */
/** @var int $created */
/** @var bool $is_professional */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Financeiro - Vendas';

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card" style="margin-bottom: 16px; border-left: 4px solid #b91c1c;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (!isset($is_professional) || !$is_professional): ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Nova venda</div>
        <div class="lc-card__body">
            <form method="post" action="/finance/sales/create" class="lc-form" style="display:grid; grid-template-columns: 1fr 1fr 1fr 2fr; gap: 12px; align-items:end;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="lc-field">
                    <label class="lc-label">Paciente ID (opcional)</label>
                    <input class="lc-input" type="number" name="patient_id" min="1" step="1" />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Origem</label>
                    <select class="lc-select" name="origin">
                        <option value="reception">Recepção</option>
                        <option value="online">Online</option>
                        <option value="system">Sistema</option>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Desconto (R$)</label>
                    <input class="lc-input" type="text" name="desconto" value="0" />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Observações</label>
                    <input class="lc-input" type="text" name="notes" />
                </div>

                <div style="grid-column: 1 / -1;">
                    <button class="lc-btn" type="submit">Criar venda</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Vendas</div>
    <div class="lc-card__body">
        <?php if ($sales === []): ?>
            <div class="lc-muted">Nenhuma venda.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Paciente</th>
                    <th>Total bruto</th>
                    <th>Desconto</th>
                    <th>Total líquido</th>
                    <th>Criada em</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sales as $s): ?>
                    <tr>
                        <td><?= (int)$s['id'] ?></td>
                        <td><?= htmlspecialchars((string)$s['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $s['patient_id'] === null ? '-' : (int)$s['patient_id'] ?></td>
                        <td><?= number_format((float)$s['total_bruto'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$s['desconto'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$s['total_liquido'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)$s['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a class="lc-btn lc-btn--secondary" href="/finance/sales/view?id=<?= (int)$s['id'] ?>">Abrir</a></td>
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
