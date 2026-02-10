<?php
/** @var list<array<string,mixed>> $items */
/** @var string $error */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Estoque - Unidades';

$statusLabel = [
    'ativo' => 'Ativo',
    'inativo' => 'Inativo',
];

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom: 16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Nova unidade</div>
    <div class="lc-card__body">
        <form method="post" action="/stock/units/create" class="lc-form lc-grid lc-gap-grid lc-grid--end" style="grid-template-columns: 1fr 2fr;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Código</label>
                <input class="lc-input" type="text" name="code" placeholder="un/ml/g" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Nome (opcional)</label>
                <input class="lc-input" type="text" name="name" placeholder="Unidade" />
            </div>

            <div class="lc-form__actions" style="grid-column: 1 / -1; padding-top: 4px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/stock/materials">Voltar</a>
            </div>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Unidades</div>
    <div class="lc-card__body">
        <?php if ($items === []): ?>
            <div class="lc-muted">Nenhuma unidade cadastrada.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['code'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <?php $st = (string)($it['status'] ?? ''); ?>
                        <td><?= htmlspecialchars((string)($statusLabel[$st] ?? ($st !== '' ? $st : '-')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="lc-td-actions">
                            <form method="post" action="/stock/units/delete" style="display:inline;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Excluir</button>
                            </form>
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
?>
