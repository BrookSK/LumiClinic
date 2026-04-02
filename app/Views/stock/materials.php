<?php
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $categories */
/** @var list<array<string,mixed>> $units */
/** @var string $error */
/** @var string|null $tab */
/** @var string|null $from */
/** @var string|null $to */
/** @var list<array<string,mixed>>|null $movements */
/** @var list<array<string,mixed>>|null $categories_all */
/** @var list<array<string,mixed>>|null $units_all */
/** @var int|null $page */
/** @var int|null $per_page */
/** @var bool|null $has_next */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Estoque - Materiais';

$tab = isset($tab) ? (string)$tab : 'materials';
$allowedTabs = ['materials', 'movements', 'categories', 'units'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'materials';
}

$from = isset($from) && $from !== null ? (string)$from : date('Y-m-01');
$to = isset($to) && $to !== null ? (string)$to : date('Y-m-d');
$movements = isset($movements) && is_array($movements) ? $movements : [];
$categoriesAll = isset($categories_all) && is_array($categories_all) ? $categories_all : [];
$unitsAll = isset($units_all) && is_array($units_all) ? $units_all : [];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 100;
$hasNext = isset($has_next) ? (bool)$has_next : false;

$tabBase = '/stock/materials';
$tabHref = function (string $t) use ($tabBase): string {
    return $tabBase . '?tab=' . urlencode($t);
};

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

<div class="lc-flex lc-flex--wrap lc-gap-sm" style="margin-bottom: 12px;">
    <a class="lc-btn <?= $tab === 'materials' ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="<?= htmlspecialchars($tabHref('materials'), ENT_QUOTES, 'UTF-8') ?>">Materiais</a>
    <a class="lc-btn <?= $tab === 'movements' ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="<?= htmlspecialchars($tabHref('movements'), ENT_QUOTES, 'UTF-8') ?>">Movimentações</a>
    <a class="lc-btn <?= $tab === 'categories' ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="<?= htmlspecialchars($tabHref('categories'), ENT_QUOTES, 'UTF-8') ?>">Categorias</a>
    <a class="lc-btn <?= $tab === 'units' ? 'lc-btn--primary' : 'lc-btn--secondary' ?>" href="<?= htmlspecialchars($tabHref('units'), ENT_QUOTES, 'UTF-8') ?>">Unidades</a>
</div>

<?php if ($tab === 'materials' && $can('stock.materials.manage')): ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Novo material</div>
        <div class="lc-card__body">
            <form method="post" action="/stock/materials/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="lc-field">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" required />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Categoria</label>
                    <select class="lc-select" name="category" required>
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
                    <label class="lc-label" style="display:flex; align-items:center; gap:4px;">
                        Estoque mín.
                        <span title="Quando o estoque chegar nessa quantidade, o sistema gera um alerta avisando que está acabando." style="cursor:help; display:inline-flex; align-items:center; justify-content:center; width:16px; height:16px; border-radius:50%; background:rgba(0,0,0,.08); font-size:11px; color:#6b7280;">?</span>
                    </label>
                    <input class="lc-input" type="text" name="stock_minimum" value="0" />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Estoque inicial</label>
                    <input class="lc-input" type="text" name="initial_stock" value="0" />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Custo unit (R$)</label>
                    <input class="lc-input" type="text" name="unit_cost" value="0" />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Validade</label>
                    <input class="lc-input" type="date" name="validity_date" min="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" />
                </div>

                <div class="lc-form__actions" style="grid-column: 1 / -1; padding-top: 4px;">
                    <button class="lc-btn" type="submit">Salvar</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'materials'): ?>
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
                        <?php
                            $status = (string)($it['status'] ?? '');
                            $statusLabel = $status === 'active' ? 'Ativo' : 'Inativo';
                            $current = (float)$it['stock_current'];
                            $minimum = (float)$it['stock_minimum'];
                            $isLow = $minimum > 0 && $current <= $minimum;
                            $isOut = $current <= 0;
                        ?>
                        <tr style="<?= $isOut ? 'background:rgba(185,28,28,.06);' : ($isLow ? 'background:rgba(217,119,6,.06);' : '') ?>">
                            <td style="font-weight:600;"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="lc-muted" style="font-size:12px;"><?= htmlspecialchars((string)($it['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="lc-muted" style="font-size:12px;"><?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight:700; color:<?= $isOut ? '#b91c1c' : ($isLow ? '#d97706' : '#16a34a') ?>;">
                                <?= number_format($current, 2, ',', '.') ?>
                                <?php if ($isOut): ?><span style="font-size:11px;"> (zerado)</span><?php elseif ($isLow): ?><span style="font-size:11px;"> (baixo)</span><?php endif; ?>
                            </td>
                            <td class="lc-muted" style="font-size:12px;"><?= number_format($minimum, 2, ',', '.') ?></td>
                            <td class="lc-muted" style="font-size:12px;">R$ <?= number_format((float)$it['unit_cost'], 2, ',', '.') ?></td>
                            <td class="lc-muted" style="font-size:12px;"><?= $it['validity_date'] === null ? '—' : htmlspecialchars((string)$it['validity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="lc-badge <?= $status === 'active' ? 'lc-badge--success' : 'lc-badge--secondary' ?>" style="font-size:11px;">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($tab === 'movements'): ?>
    <?php
        $matMap = [];
        foreach ($items as $m) {
            $matMap[(int)$m['id']] = $m;
        }
        $returnToMov = '/stock/materials?tab=movements&from=' . urlencode((string)$from) . '&to=' . urlencode((string)$to) . '&per_page=' . (int)$perPage . '&page=' . (int)$page;
    ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Filtros</div>
        <div class="lc-card__body">
            <form method="get" action="/stock/materials" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
                <input type="hidden" name="tab" value="movements" />
                <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
                <input type="hidden" name="page" value="1" />
                <div class="lc-field">
                    <label class="lc-label">De</label>
                    <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Até</label>
                    <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>" />
                </div>
                <button class="lc-btn" type="submit">Filtrar</button>
            </form>
        </div>
    </div>

    <?php if ($can('stock.movements.create')): ?>
        <div class="lc-card" style="margin-bottom: 16px;">
            <div class="lc-card__header">Nova movimentação</div>
            <div class="lc-card__body">
                <form method="post" action="/stock/movements/create" class="lc-form lc-grid lc-gap-grid lc-grid--end" style="grid-template-columns: 2fr 1fr 1fr 1fr 2fr;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToMov, ENT_QUOTES, 'UTF-8') ?>" />

                    <div class="lc-field">
                        <label class="lc-label">Material</label>
                        <select class="lc-select" name="material_id">
                            <?php foreach ($items as $m): ?>
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

                            $type = (string)($mv['type'] ?? '');
                            $typeLabelMap = [
                                'entry' => 'Entrada',
                                'exit' => 'Saída',
                                'adjustment' => 'Ajuste',
                                'loss' => 'Perda',
                                'expiration' => 'Vencimento',
                            ];
                            $typeLabel = (string)($typeLabelMap[$type] ?? $type);

                            $reason = (string)($mv['loss_reason'] ?? '');
                            $reasonLabelMap = [
                                'expiration' => 'Vencimento',
                                'breakage' => 'Quebra',
                                'contamination' => 'Contaminação',
                                'operational_error' => 'Erro operacional',
                            ];
                            $reasonLabel = $reason !== '' ? (string)($reasonLabelMap[$reason] ?? $reason) : '';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$mv['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($mname, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$mv['quantity'], 3, ',', '.') ?> <?= htmlspecialchars($munit, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= number_format((float)$mv['total_cost_snapshot'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($reasonLabel, ENT_QUOTES, 'UTF-8') ?></td>
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
                        <a class="lc-btn lc-btn--secondary" href="/stock/materials?tab=movements&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
                    <?php endif; ?>
                    <?php if ($hasNext): ?>
                        <a class="lc-btn lc-btn--secondary" href="/stock/materials?tab=movements&from=<?= urlencode((string)$from) ?>&to=<?= urlencode((string)$to) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($tab === 'categories'): ?>
    <?php $returnToCat = '/stock/materials?tab=categories'; ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Nova categoria</div>
        <div class="lc-card__body">
            <?php if ($can('stock.materials.manage')): ?>
                <form method="post" action="/stock/categories/create" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToCat, ENT_QUOTES, 'UTF-8') ?>" />
                    <div class="lc-field" style="min-width:260px;">
                        <label class="lc-label">Nome</label>
                        <input class="lc-input" type="text" name="name" required />
                    </div>
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Categorias</div>
        <div class="lc-card__body">
            <?php if ($categoriesAll === []): ?>
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
                    <?php foreach ($categoriesAll as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="lc-td-actions">
                                <?php if ($can('stock.materials.manage')): ?>
                                    <form method="post" action="/stock/categories/delete" style="display:inline;">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToCat, ENT_QUOTES, 'UTF-8') ?>" />
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
<?php elseif ($tab === 'units'): ?>
    <?php $returnToUnit = '/stock/materials?tab=units'; ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Nova unidade</div>
        <div class="lc-card__body">
            <?php if ($can('stock.materials.manage')): ?>
                <form method="post" action="/stock/units/create" class="lc-form lc-grid lc-gap-grid lc-grid--end" style="grid-template-columns: 1fr 2fr;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToUnit, ENT_QUOTES, 'UTF-8') ?>" />
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
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="lc-card">
        <div class="lc-card__header">Unidades</div>
        <div class="lc-card__body">
            <?php if ($unitsAll === []): ?>
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
                    <?php foreach ($unitsAll as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$it['code'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="lc-td-actions">
                                <?php if ($can('stock.materials.manage')): ?>
                                    <form method="post" action="/stock/units/delete" style="display:inline;">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                        <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnToUnit, ENT_QUOTES, 'UTF-8') ?>" />
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
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
