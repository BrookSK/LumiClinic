<?php
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

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Novo inventário</div>
    <div class="lc-card__body">
        <form method="post" action="/stock/inventory/create" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="lc-field" style="min-width:360px;">
                <label class="lc-label">Observações</label>
                <input class="lc-input" type="text" name="notes" placeholder="Ex: Contagem mensal" />
            </div>
            <button class="lc-btn" type="submit">Criar</button>
            <a class="lc-btn lc-btn--secondary" href="/stock/materials">Materiais</a>
            <a class="lc-btn lc-btn--secondary" href="/stock/movements">Movimentações</a>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Inventários</div>
    <div class="lc-card__body">
        <?php if (($items ?? []) === []): ?>
            <div class="lc-muted">Nenhum inventário criado.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th>Confirmado em</th>
                    <th>Obs</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($items ?? []) as $it): ?>
                    <?php
                        $status = (string)($it['status'] ?? '');
                        $statusLabelMap = [
                            'draft' => 'Rascunho',
                            'confirmed' => 'Confirmado',
                            'cancelled' => 'Cancelado',
                        ];
                        $statusLabel = (string)($statusLabelMap[$status] ?? $status);
                    ?>
                    <tr>
                        <td><?= (int)($it['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= ($it['confirmed_at'] ?? null) === null ? '-' : htmlspecialchars((string)$it['confirmed_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a class="lc-btn lc-btn--secondary" href="/stock/inventory/edit?id=<?= (int)($it['id'] ?? 0) ?>">Abrir</a></td>
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
