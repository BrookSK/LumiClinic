<?php
$csrf  = $_SESSION['_csrf'] ?? '';
$title = 'Estoque';

$tab = isset($tab) ? (string)$tab : 'materials';
if (!in_array($tab, ['materials','movements','categories','units'], true)) $tab = 'materials';

$items         = $items ?? [];
$categories    = $categories ?? [];
$units         = $units ?? [];
$from          = isset($from) && $from !== null ? (string)$from : date('Y-m-01');
$to            = isset($to) && $to !== null ? (string)$to : date('Y-m-d');
$movements     = isset($movements) && is_array($movements) ? $movements : [];
$categoriesAll = isset($categories_all) && is_array($categories_all) ? $categories_all : [];
$unitsAll      = isset($units_all) && is_array($units_all) ? $units_all : [];
$page          = isset($page) ? (int)$page : 1;
$perPage       = isset($per_page) ? (int)$per_page : 100;
$hasNext       = isset($has_next) ? (bool)$has_next : false;

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) {
        if (in_array($p, $perms['deny'], true)) return false;
        return in_array($p, $perms['allow'], true);
    }
    return in_array($p, $perms, true);
};

$tabLabels = ['materials'=>'📦 Materiais','movements'=>'↕ Movimentações','categories'=>'🏷 Categorias','units'=>'📏 Unidades'];

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div style="font-weight:800; font-size:18px;">Estoque</div>
    <a class="lc-btn lc-btn--secondary" href="/stock/alerts">Ver alertas</a>
</div>

<!-- Abas -->
<div style="display:flex; gap:0; margin-bottom:16px; border-bottom:2px solid rgba(0,0,0,.08);">
    <?php foreach ($tabLabels as $k => $lbl): ?>
        <a href="/stock/materials?tab=<?= $k ?>"
           style="padding:10px 18px; font-size:14px; font-weight:<?= $tab === $k ? '700' : '400' ?>; color:<?= $tab === $k ? '#815901' : '#6b7280' ?>; text-decoration:none; border-bottom:2px solid <?= $tab === $k ? '#eeb810' : 'transparent' ?>; margin-bottom:-2px; transition:all .15s;">
            <?= $lbl ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'materials'): ?>
<!-- ═══ MATERIAIS ═══ -->
<?php if ($can('stock.materials.manage')): ?>
    <button type="button" class="lc-btn lc-btn--primary" style="margin-bottom:14px;" onclick="toggleForm('form-material')">+ Novo material</button>

    <div id="form-material" style="display:none; margin-bottom:14px;">
        <div class="lc-card">
            <div class="lc-card__header" style="font-weight:700;">Cadastrar material</div>
            <div class="lc-card__body">
                <form method="post" action="/stock/materials/create" class="lc-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <div class="lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr; align-items:end;">
                        <div class="lc-field">
                            <label class="lc-label">Nome</label>
                            <input class="lc-input" type="text" name="name" required placeholder="Ex: Botox 100U, Ácido Hialurônico..." />
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Categoria</label>
                            <select class="lc-select" name="category" required>
                                <option value="">Selecione</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Unidade</label>
                            <select class="lc-select" name="unit" required>
                                <option value="">Selecione</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= htmlspecialchars((string)$u['code'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$u['code'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr 1fr; align-items:end; margin-top:10px;">
                        <div class="lc-field">
                            <label class="lc-label" style="display:flex; align-items:center; gap:4px;">
                                Estoque mínimo
                                <span class="lc-tooltip-trigger" style="cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:50%; background:rgba(0,0,0,.1); font-size:11px; color:#6b7280; position:relative;"
                                    onclick="var t=this.querySelector('.lc-tooltip-box'); if(t) t.style.display=t.style.display==='block'?'none':'block';">
                                    ?
                                    <span class="lc-tooltip-box" style="display:none; position:absolute; bottom:calc(100% + 6px); left:50%; transform:translateX(-50%); background:#1f2937; color:#fff; padding:8px 12px; border-radius:8px; font-size:12px; font-weight:400; white-space:nowrap; z-index:10; box-shadow:0 4px 12px rgba(0,0,0,.2);">
                                        Quando o estoque chegar nessa quantidade, o sistema gera um alerta.
                                    </span>
                                </span>
                            </label>
                            <input class="lc-input" type="text" name="stock_minimum" value="0" />
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Estoque inicial</label>
                            <input class="lc-input" type="text" name="initial_stock" value="0" />
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Custo unitário (R$)</label>
                            <input class="lc-input" type="text" name="unit_cost" value="0" placeholder="0,00" />
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Validade (opcional)</label>
                            <input class="lc-input" type="date" name="validity_date" />
                        </div>
                    </div>
                    <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                        <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleForm('form-material')">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($items)): ?>
    <div class="lc-card"><div class="lc-card__body lc-muted" style="text-align:center; padding:30px;">Nenhum material cadastrado.</div></div>
<?php else: ?>
    <div class="lc-card">
        <div class="lc-card__body" style="padding:0;">
            <table class="lc-table">
                <thead><tr>
                    <th>Material</th><th>Categoria</th><th>Unidade</th><th>Estoque</th><th>Mínimo</th><th>Custo</th><th>Validade</th><th>Status</th>
                </tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php
                    $current = (float)$it['stock_current'];
                    $minimum = (float)$it['stock_minimum'];
                    $isOut = $current <= 0;
                    $isLow = !$isOut && $minimum > 0 && $current <= $minimum;
                    $st = (string)($it['status'] ?? 'active');
                    ?>
                    <tr style="<?= $isOut ? 'background:rgba(185,28,28,.05);' : ($isLow ? 'background:rgba(217,119,6,.05);' : '') ?>">
                        <td style="font-weight:600;"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="lc-muted" style="font-size:12px;"><?= htmlspecialchars((string)($it['category'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="lc-muted" style="font-size:12px;"><?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-weight:700; color:<?= $isOut ? '#b91c1c' : ($isLow ? '#d97706' : '#16a34a') ?>;">
                            <?= number_format($current, 2, ',', '.') ?>
                            <?php if ($isOut): ?><span style="font-size:11px;"> ⚠ zerado</span><?php elseif ($isLow): ?><span style="font-size:11px;"> ⚠ baixo</span><?php endif; ?>
                        </td>
                        <td class="lc-muted" style="font-size:12px;"><?= number_format($minimum, 2, ',', '.') ?></td>
                        <td class="lc-muted" style="font-size:12px;">R$ <?= number_format((float)$it['unit_cost'], 2, ',', '.') ?></td>
                        <td class="lc-muted" style="font-size:12px;"><?= $it['validity_date'] === null ? '—' : htmlspecialchars((string)$it['validity_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="lc-badge <?= $st === 'active' ? 'lc-badge--success' : 'lc-badge--secondary' ?>" style="font-size:11px;"><?= $st === 'active' ? 'Ativo' : 'Inativo' ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php elseif ($tab === 'movements'): ?>
<!-- ═══ MOVIMENTAÇÕES ═══ -->
<?php
$matMap = [];
foreach ($items as $m) { $matMap[(int)$m['id']] = $m; }
$typeLabel = ['entry'=>'Entrada','exit'=>'Saída','adjustment'=>'Ajuste','loss'=>'Perda','expiration'=>'Vencimento'];
$typeColor = ['entry'=>'#16a34a','exit'=>'#b91c1c','adjustment'=>'#2563eb','loss'=>'#d97706','expiration'=>'#6b7280'];
?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/stock/materials" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
            <input type="hidden" name="tab" value="movements" />
            <div class="lc-field"><label class="lc-label">De</label><input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">Até</label><input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" /></div>
            <button class="lc-btn lc-btn--primary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<?php if ($can('stock.movements.create')): ?>
    <button type="button" class="lc-btn lc-btn--primary" style="margin-bottom:14px;" onclick="toggleForm('form-mov')">+ Nova movimentação</button>
    <div id="form-mov" style="display:none; margin-bottom:14px;">
        <div class="lc-card">
            <div class="lc-card__header" style="font-weight:700;">Registrar movimentação</div>
            <div class="lc-card__body">
                <form method="post" action="/stock/movements/create" class="lc-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="return_to" value="/stock/materials?tab=movements&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" />
                    <div class="lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 1fr 2fr; align-items:end;">
                        <div class="lc-field"><label class="lc-label">Material</label>
                            <select class="lc-select" name="material_id">
                                <?php foreach ($items as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8') ?> (<?= number_format((float)$m['stock_current'], 2, ',', '.') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="lc-field"><label class="lc-label">Tipo</label>
                            <select class="lc-select" name="type">
                                <option value="entry">Entrada</option><option value="exit">Saída</option><option value="adjustment">Ajuste</option><option value="loss">Perda</option>
                            </select>
                        </div>
                        <div class="lc-field"><label class="lc-label">Quantidade</label><input class="lc-input" type="text" name="quantity" required /></div>
                        <div class="lc-field"><label class="lc-label">Observações</label><input class="lc-input" type="text" name="notes" /></div>
                    </div>
                    <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                        <button class="lc-btn lc-btn--primary" type="submit">Registrar</button>
                        <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleForm('form-mov')">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($movements)): ?>
    <div class="lc-card"><div class="lc-card__body lc-muted" style="text-align:center; padding:30px;">Nenhuma movimentação no período.</div></div>
<?php else: ?>
    <div class="lc-card">
        <div class="lc-card__body" style="padding:0;">
            <table class="lc-table">
                <thead><tr><th>Data</th><th>Material</th><th>Tipo</th><th>Quantidade</th><th>Observações</th></tr></thead>
                <tbody>
                <?php foreach ($movements as $mv): ?>
                    <?php
                    $mid = (int)$mv['material_id'];
                    $mname = isset($matMap[$mid]) ? (string)$matMap[$mid]['name'] : '#'.$mid;
                    $t = (string)($mv['type'] ?? '');
                    $tl = $typeLabel[$t] ?? $t;
                    $tc = $typeColor[$t] ?? '#6b7280';
                    $dateFmt = '';
                    try { $dateFmt = (new \DateTimeImmutable((string)$mv['created_at']))->format('d/m/Y H:i'); } catch (\Throwable $e) { $dateFmt = (string)$mv['created_at']; }
                    ?>
                    <tr>
                        <td style="white-space:nowrap; font-size:13px;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($mname, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span style="color:<?= $tc ?>; font-weight:600; font-size:13px;"><?= htmlspecialchars($tl, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td style="font-weight:700;"><?= number_format((float)$mv['quantity'], 2, ',', '.') ?></td>
                        <td class="lc-muted" style="font-size:12px;"><?= htmlspecialchars((string)($mv['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php elseif ($tab === 'categories'): ?>
<!-- ═══ CATEGORIAS ═══ -->
<?php if ($can('stock.materials.manage')): ?>
    <div class="lc-card" style="margin-bottom:14px;">
        <div class="lc-card__body">
            <form method="post" action="/stock/categories/create" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="return_to" value="/stock/materials?tab=categories" />
                <div class="lc-field" style="flex:1; min-width:200px;"><label class="lc-label">Nova categoria</label><input class="lc-input" type="text" name="name" required placeholder="Ex: Injetáveis, Descartáveis..." /></div>
                <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($categoriesAll)): ?>
    <div class="lc-card"><div class="lc-card__body lc-muted" style="text-align:center; padding:30px;">Nenhuma categoria.</div></div>
<?php else: ?>
    <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
        <?php foreach ($categoriesAll as $c): ?>
            <div class="lc-card" style="margin:0; padding:14px;">
                <div class="lc-flex lc-flex--between lc-flex--center">
                    <span style="font-weight:600;"><?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($can('stock.materials.manage')): ?>
                        <form method="post" action="/stock/categories/delete" onsubmit="return confirm('Excluir?');">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
                            <input type="hidden" name="return_to" value="/stock/materials?tab=categories" />
                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px; padding:2px 8px;">✕</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php elseif ($tab === 'units'): ?>
<!-- ═══ UNIDADES ═══ -->
<?php if ($can('stock.materials.manage')): ?>
    <div class="lc-card" style="margin-bottom:14px;">
        <div class="lc-card__body">
            <form method="post" action="/stock/units/create" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="return_to" value="/stock/materials?tab=units" />
                <div class="lc-field" style="min-width:100px;"><label class="lc-label">Código</label><input class="lc-input" type="text" name="code" placeholder="un, ml, g..." required /></div>
                <div class="lc-field" style="flex:1; min-width:160px;"><label class="lc-label">Nome (opcional)</label><input class="lc-input" type="text" name="name" placeholder="Unidade, Mililitro..." /></div>
                <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($unitsAll)): ?>
    <div class="lc-card"><div class="lc-card__body lc-muted" style="text-align:center; padding:30px;">Nenhuma unidade.</div></div>
<?php else: ?>
    <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
        <?php foreach ($unitsAll as $u): ?>
            <div class="lc-card" style="margin:0; padding:14px;">
                <div class="lc-flex lc-flex--between lc-flex--center">
                    <div>
                        <span style="font-weight:700;"><?= htmlspecialchars((string)$u['code'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (($u['name'] ?? '') !== ''): ?>
                            <span class="lc-muted" style="font-size:12px; margin-left:6px;"><?= htmlspecialchars((string)$u['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($can('stock.materials.manage')): ?>
                        <form method="post" action="/stock/units/delete" onsubmit="return confirm('Excluir?');">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>" />
                            <input type="hidden" name="return_to" value="/stock/materials?tab=units" />
                            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px; padding:2px 8px;">✕</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php endif; ?>

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
