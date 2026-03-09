<?php
/** @var list<array<string,mixed>> $items */
/** @var string $error */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Estoque - Categorias';

$statusLabel = [
    'ativo' => 'Ativo',
    'inativo' => 'Inativo',
];

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

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Nova categoria</div>
    <div class="lc-card__body">
        <?php if ($can('stock.materials.manage')): ?>
            <form method="post" action="/stock/categories/create" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="lc-field" style="min-width:260px;">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" required />
                </div>

                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/stock/materials">Voltar</a>
            </form>
        <?php else: ?>
            <a class="lc-btn lc-btn--secondary" href="/stock/materials">Voltar</a>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Categorias</div>
    <div class="lc-card__body">
        <?php if ($items === []): ?>
            <div class="lc-muted">Nenhuma categoria cadastrada.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <?php $st = (string)($it['status'] ?? ''); ?>
                        <td><?= htmlspecialchars((string)($statusLabel[$st] ?? ($st !== '' ? $st : '-')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="lc-td-actions">
                            <?php if ($can('stock.materials.manage')): ?>
                                <form method="post" action="/stock/categories/delete" style="display:inline;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                    <button class="lc-btn lc-btn--secondary" type="submit">Excluir</button>
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

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
