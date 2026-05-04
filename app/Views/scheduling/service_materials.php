<?php
/** @var array<string,mixed> $service */
/** @var list<array<string,mixed>> $services */
/** @var list<array<string,mixed>> $materials */
/** @var list<array<string,mixed>> $defaults */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Materiais do Servico';

$can = function (string $pc): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $p = $_SESSION['permissions'] ?? [];
    if (!is_array($p)) return false;
    if (isset($p['allow'],$p['deny'])&&is_array($p['allow'])&&is_array($p['deny'])) {
        if (in_array($pc,$p['deny'],true)) return false;
        return in_array($pc,$p['allow'],true);
    }
    return in_array($pc,$p,true);
};

$hasService = isset($service) && is_array($service) && $service !== [];
$fmtQty = function(float $q): string { return ($q == (int)$q) ? (string)(int)$q : number_format($q, 2, ',', '.'); };

ob_start();
?>

<style>
.sm-header { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:20px; }
.sm-title { font-weight:850; font-size:20px; color:rgba(31,41,55,.96); }
.sm-sub { font-size:13px; color:rgba(31,41,55,.50); margin-top:3px; max-width:600px; line-height:1.5; }
.sm-panel { border-radius:14px; border:1px solid rgba(17,24,39,.08); background:var(--lc-surface,#fff); box-shadow:0 2px 12px rgba(17,24,39,.06); overflow:hidden; margin-bottom:16px; }
.sm-panel-head { padding:14px 18px; border-bottom:1px solid rgba(17,24,39,.06); display:flex; align-items:center; justify-content:space-between; gap:10px; }
.sm-panel-head-title { font-weight:750; font-size:14px; color:rgba(31,41,55,.9); }
.sm-panel-body { padding:16px 18px; }
.sm-service-bar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:14px 18px; border-radius:14px; border:1px solid rgba(238,184,16,.22); background:rgba(253,229,159,.08); margin-bottom:18px; }
.sm-service-name { font-weight:750; font-size:16px; color:rgba(31,41,55,.96); }
.sm-service-sub { font-size:12px; color:rgba(31,41,55,.50); margin-top:2px; }
.sm-item { display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:12px; border:1px solid rgba(17,24,39,.06); background:rgba(0,0,0,.01); margin-bottom:8px; transition:all .15s; }
.sm-item:hover { border-color:rgba(99,102,241,.2); background:rgba(99,102,241,.02); }
.sm-item-icon { width:36px; height:36px; border-radius:10px; background:rgba(99,102,241,.08); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.sm-item-info { flex:1; min-width:0; }
.sm-item-name { font-weight:700; font-size:13px; color:rgba(31,41,55,.9); }
.sm-item-detail { font-size:12px; color:rgba(31,41,55,.45); margin-top:2px; }
.sm-item-actions { display:flex; gap:6px; align-items:center; flex-shrink:0; }
.sm-edit-form { display:flex; gap:8px; align-items:center; }
.sm-qty-input { width:80px; text-align:center; padding:6px 8px; border:1.5px solid rgba(17,24,39,.12); border-radius:8px; font-size:13px; font-weight:600; }
.sm-qty-input:focus { border-color:#6366f1; outline:none; }
.sm-badge { display:inline-flex; padding:4px 10px; border-radius:8px; font-size:12px; font-weight:700; background:rgba(99,102,241,.08); color:#6366f1; }
.sm-empty { text-align:center; padding:30px 20px; color:rgba(31,41,55,.4); font-size:13px; }
.sm-add-panel { padding:16px 18px; border-radius:14px; border:1px dashed rgba(99,102,241,.25); background:rgba(99,102,241,.03); margin-bottom:16px; }
.sm-add-form { display:flex; gap:12px; align-items:end; flex-wrap:wrap; }
</style>

<!-- Header -->
<div class="sm-header">
    <div>
        <a href="/services" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:8px;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Voltar para servicos
        </a>
        <div class="sm-title">Vinculo com estoque</div>
        <div class="sm-sub">Defina quais materiais sao consumidos automaticamente quando um servico e realizado. Ao finalizar o atendimento, o sistema da baixa no estoque.</div>
    </div>
</div>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!$hasService): ?>
<!-- Service selector -->
<div class="sm-panel">
    <div class="sm-panel-head"><span class="sm-panel-head-title">Selecione um servico</span></div>
    <div class="sm-panel-body">
        <form method="get" action="/services/materials" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <div class="lc-field" style="min-width:280px;flex:1;">
                <label class="lc-label">Servico</label>
                <select class="lc-select" name="service_id" required>
                    <option value="">Escolha...</option>
                    <?php foreach (($services ?? []) as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Abrir</button>
        </form>
    </div>
</div>

<?php else: ?>

<!-- Service bar -->
<div class="sm-service-bar">
    <div>
        <div class="sm-service-name"><?= htmlspecialchars((string)$service['name'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="sm-service-sub"><?= count($defaults) ?> material(is) vinculado(s)</div>
    </div>
    <form method="get" action="/services/materials" style="margin:0;">
        <select class="lc-select" name="service_id" style="min-width:200px;font-size:12px;" onchange="this.form.submit()">
            <?php foreach (($services ?? []) as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === (int)$service['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Add material -->
<?php if ($can('services.manage')): ?>
<div class="sm-add-panel" id="addMatForm" style="display:none;">
    <form method="post" action="/services/materials/create" class="sm-add-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>" />
        <div class="lc-field" style="min-width:220px;flex:1;">
            <label class="lc-label">Material</label>
            <select class="lc-select" name="material_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($materials as $m): ?>
                    <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$m['unit'], ENT_QUOTES, 'UTF-8') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="lc-field" style="width:120px;">
            <label class="lc-label">Qtd por sessao</label>
            <input class="lc-input" type="text" name="quantity_per_session" required placeholder="Ex: 5" />
        </div>
        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="document.getElementById('addMatForm').style.display='none'">Cancelar</button>
    </form>
</div>
<div style="margin-bottom:16px;">
    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="document.getElementById('addMatForm').style.display='block'">+ Adicionar material</button>
</div>
<?php endif; ?>

<!-- Materials list -->
<div class="sm-panel">
    <div class="sm-panel-head">
        <span class="sm-panel-head-title">Materiais vinculados</span>
        <span class="sm-badge"><?= count($defaults) ?></span>
    </div>
    <div class="sm-panel-body">
        <?php if ($defaults === []): ?>
            <div class="sm-empty">
                <div style="font-size:28px;margin-bottom:8px;">📦</div>
                Nenhum material vinculado a este servico.
            </div>
        <?php else: ?>
            <?php foreach ($defaults as $d):
                $dId = (int)$d['id'];
                $dQty = (float)$d['quantity_per_session'];
                $dQtyDisplay = $fmtQty($dQty);
                $dQtyInput = ($dQty == (int)$dQty) ? (string)(int)$dQty : (string)$dQty;
            ?>
                <div class="sm-item" id="mat-<?= $dId ?>">
                    <div class="sm-item-icon">📦</div>
                    <div class="sm-item-info">
                        <div class="sm-item-name"><?= htmlspecialchars((string)$d['material_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="sm-item-detail">
                            <span id="qty-display-<?= $dId ?>"><?= $dQtyDisplay ?></span>
                            <?= htmlspecialchars((string)$d['material_unit'], ENT_QUOTES, 'UTF-8') ?> por sessao
                        </div>
                    </div>
                    <?php if ($can('services.manage')): ?>
                    <div class="sm-item-actions">
                        <!-- Edit form (inline) -->
                        <form method="post" action="/services/materials/update" class="sm-edit-form" id="edit-form-<?= $dId ?>" style="display:none;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>" />
                            <input type="hidden" name="material_id" value="<?= (int)$d['material_id'] ?>" />
                            <input class="sm-qty-input" type="text" name="quantity_per_session" value="<?= htmlspecialchars($dQtyInput, ENT_QUOTES, 'UTF-8') ?>" />
                            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit" style="padding:5px 10px;">Salvar</button>
                            <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" style="padding:5px 10px;" onclick="toggleEdit(<?= $dId ?>,false)">Cancelar</button>
                        </form>
                        <!-- Action buttons -->
                        <div id="actions-<?= $dId ?>">
                            <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleEdit(<?= $dId ?>,true)" title="Editar quantidade">Editar</button>
                            <form method="post" action="/services/materials/delete" style="margin:0;display:inline;" onsubmit="return confirm('Remover este material?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>" />
                                <input type="hidden" name="id" value="<?= $dId ?>" />
                                <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="color:#dc2626;" title="Remover">Remover</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleEdit(id, show) {
    document.getElementById('edit-form-' + id).style.display = show ? 'flex' : 'none';
    document.getElementById('actions-' + id).style.display = show ? 'none' : '';
    if (show) {
        document.querySelector('#edit-form-' + id + ' input[name="quantity_per_session"]').focus();
    }
}
</script>

<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
