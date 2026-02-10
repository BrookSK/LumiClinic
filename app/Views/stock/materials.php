<?php
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $categories */
/** @var list<array<string,mixed>> $units */
/** @var string $error */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Estoque - Materiais';

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom: 16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if ($can('stock.materials.manage')): ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Novo material</div>
        <div class="lc-card__body">
            <form method="post" action="/stock/materials/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="lc-field">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" required />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Categoria</label>
                    <select class="lc-select" name="category">
                        <option value="">-</option>
                        <?php foreach (($categories ?? []) as $c): ?>
                            <option value="<?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Unidade</label>
                    <select class="lc-select" name="unit" required>
                        <option value="">-</option>
                        <?php foreach (($units ?? []) as $u): ?>
                            <?php $code = (string)$u['code']; ?>
                            <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Estoque mín.</label>
                    <input class="lc-input" type="text" name="stock_minimum" value="0" />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Custo unit (R$)</label>
                    <input class="lc-input" type="text" name="unit_cost" value="0" />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Validade</label>
                    <input class="lc-input" type="date" name="validity_date" />
                </div>

                <div class="lc-form__actions" style="grid-column: 1 / -1; padding-top: 4px;">
                    <button class="lc-btn" type="submit">Salvar</button>
                    <a class="lc-btn lc-btn--secondary" href="/stock/movements">Movimentações</a>
                    <a class="lc-btn lc-btn--secondary" href="/stock/categories">Categorias</a>
                    <a class="lc-btn lc-btn--secondary" href="/stock/units">Unidades</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Materiais</div>
    <div class="lc-card__body">
        <?php if ($items === []): ?>
            <div class="lc-muted">Nenhum material cadastrado.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Un</th>
                    <th>Estoque</th>
                    <th>Mínimo</th>
                    <th>Custo</th>
                    <th>Validade</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['stock_current'], 3, ',', '.') ?></td>
                        <td><?= number_format((float)$it['stock_minimum'], 3, ',', '.') ?></td>
                        <td><?= number_format((float)$it['unit_cost'], 2, ',', '.') ?></td>
                        <td><?= $it['validity_date'] === null ? '-' : htmlspecialchars((string)$it['validity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$it['status'], ENT_QUOTES, 'UTF-8') ?></td>
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
