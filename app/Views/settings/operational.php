<?php
$title = 'Configuração Operacional';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? '';
$saved = $saved ?? '';
$stages = isset($stages) && is_array($stages) ? $stages : [];
$lostReasons = isset($lost_reasons) && is_array($lost_reasons) ? $lost_reasons : [];
$origins = isset($origins) && is_array($origins) ? $origins : [];
$can = function (string $pc): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $p = $_SESSION['permissions'] ?? [];
    if (!is_array($p)) return false;
    if (isset($p['allow'],$p['deny'])) { if (in_array($pc,$p['deny'],true)) return false; return in_array($pc,$p['allow'],true); }
    return in_array($pc,$p,true);
};
ob_start();
?>
<style>
.op-back{display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px}
.op-back:hover{color:rgba(129,89,1,1)}
.op-tabs{display:flex;gap:0;border-bottom:2px solid rgba(17,24,39,.08);margin-bottom:18px}
.op-tab{padding:10px 20px;font-size:14px;font-weight:600;color:rgba(31,41,55,.50);cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .15s;background:none;border-top:0;border-left:0;border-right:0}
.op-tab:hover{color:rgba(31,41,55,.80)}
.op-tab--active{color:rgba(129,89,1,1);border-bottom-color:rgba(129,89,1,1)}
.op-panel{display:none}.op-panel--active{display:block}
.op-desc{font-size:12px;color:rgba(31,41,55,.45);line-height:1.5;margin-bottom:14px}
.op-items{display:flex;flex-direction:column;gap:6px}
.op-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);cursor:grab;user-select:none}
.op-item.dragging{opacity:.4;border-style:dashed}
.op-item.drag-over{border-color:rgba(129,89,1,.5);background:rgba(253,229,159,.12)}
.op-item__grip{color:rgba(31,41,55,.25);font-size:16px;flex-shrink:0;cursor:grab}
.op-item__name{font-weight:700;font-size:13px;color:rgba(31,41,55,.90);flex:1}
.op-empty{text-align:center;padding:16px;color:rgba(31,41,55,.40);font-size:13px}
.op-add{margin-top:12px;padding:14px;border-radius:12px;border:1px solid rgba(238,184,16,.18);background:rgba(253,229,159,.08)}
</style>

<a href="/settings" class="op-back"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>Voltar para configurações</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Configuração operacional</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:600px;line-height:1.5;">Gerencie as listas usadas no dia a dia. Arraste os itens para reordenar.</div>

<?php if (is_string($error) && trim($error) !== ''): ?><div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (is_string($saved) && trim($saved) !== ''): ?><div class="lc-alert lc-alert--success" style="margin-bottom:14px;">Salvo com sucesso.</div><?php endif; ?>

<div class="op-tabs" role="tablist">
    <button class="op-tab op-tab--active" data-tab="funil" role="tab">Etapas do funil</button>
    <button class="op-tab" data-tab="motivos" role="tab">Motivos de perda</button>
    <button class="op-tab" data-tab="origens" role="tab">Origem do paciente</button>
</div>

<!-- TAB: Funil -->
<div class="op-panel op-panel--active" id="tab-funil">
    <div class="op-desc">O funil organiza o fluxo do paciente desde o primeiro contato até a conversão.</div>
    <?php if ($stages === []): ?><div class="op-empty">Nenhuma etapa cadastrada.</div><?php else: ?>
    <div class="op-items" data-sortable="stages">
        <?php foreach ($stages as $s): ?>
        <div class="op-item" draggable="true" data-id="<?= (int)($s['id'] ?? 0) ?>">
            <span class="op-item__grip">⠿</span>
            <span class="op-item__name"><?= htmlspecialchars((string)($s['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($can('settings.update')): ?>
            <form method="post" action="/settings/operational/funnel-stages/delete" style="margin:0;" onsubmit="return confirm('Remover?');"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"/><input type="hidden" name="id" value="<?= (int)($s['id'] ?? 0) ?>"/><button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px;">Remover</button></form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($can('settings.update')): ?>
    <div class="op-add">
        <form method="post" action="/settings/operational/funnel-stages/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"/><input type="hidden" name="sort_order" value="<?= count($stages) + 1 ?>"/>
            <div class="lc-field" style="flex:1;min-width:200px;"><label class="lc-label">Nova etapa</label><input class="lc-input" type="text" name="name" required placeholder="Ex: Triagem"/></div>
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- TAB: Motivos -->
<div class="op-panel" id="tab-motivos">
    <div class="op-desc">Registre por que pacientes desistem. Exemplo: Sem orçamento, Preço alto, Desistiu.</div>
    <?php if ($lostReasons === []): ?><div class="op-empty">Nenhum motivo cadastrado.</div><?php else: ?>
    <div class="op-items" data-sortable="lost_reasons">
        <?php foreach ($lostReasons as $r): ?>
        <div class="op-item" draggable="true" data-id="<?= (int)($r['id'] ?? 0) ?>">
            <span class="op-item__grip">⠿</span>
            <span class="op-item__name"><?= htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($can('settings.update')): ?>
            <form method="post" action="/settings/operational/lost-reasons/delete" style="margin:0;" onsubmit="return confirm('Remover?');"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"/><input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>"/><button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px;">Remover</button></form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($can('settings.update')): ?>
    <div class="op-add">
        <form method="post" action="/settings/operational/lost-reasons/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"/><input type="hidden" name="sort_order" value="<?= count($lostReasons) + 1 ?>"/>
            <div class="lc-field" style="flex:1;min-width:200px;"><label class="lc-label">Novo motivo</label><input class="lc-input" type="text" name="name" required placeholder="Ex: Preço alto"/></div>
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- TAB: Origens -->
<div class="op-panel" id="tab-origens">
    <div class="op-desc">De onde seus pacientes vêm? Cadastre para rastrear nos relatórios. Ex: Instagram, Indicação, Google.</div>
    <?php if ($origins === []): ?><div class="op-empty">Nenhuma origem cadastrada.</div><?php else: ?>
    <div class="op-items" data-sortable="origins">
        <?php foreach ($origins as $o): ?>
        <div class="op-item" draggable="true" data-id="<?= (int)($o['id'] ?? 0) ?>">
            <span class="op-item__grip">⠿</span>
            <span class="op-item__name"><?= htmlspecialchars((string)($o['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($can('settings.update')): ?>
            <form method="post" action="/settings/operational/patient-origins/delete" style="margin:0;" onsubmit="return confirm('Remover?');"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"/><input type="hidden" name="id" value="<?= (int)($o['id'] ?? 0) ?>"/><button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px;">Remover</button></form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($can('settings.update')): ?>
    <div class="op-add">
        <form method="post" action="/settings/operational/patient-origins/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"/><input type="hidden" name="sort_order" value="<?= count($origins) + 1 ?>"/>
            <div class="lc-field" style="flex:1;min-width:200px;"><label class="lc-label">Nova origem</label><input class="lc-input" type="text" name="name" required placeholder="Ex: Instagram"/></div>
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
(function(){
    var csrf = <?= json_encode($csrf) ?>;
    var tabMap = {funil:'tab-funil',motivos:'tab-motivos',origens:'tab-origens'};

    // Tabs
    document.querySelectorAll('.op-tab').forEach(function(btn){
        btn.addEventListener('click',function(){
            document.querySelectorAll('.op-tab').forEach(function(b){b.classList.remove('op-tab--active');});
            document.querySelectorAll('.op-panel').forEach(function(p){p.classList.remove('op-panel--active');});
            btn.classList.add('op-tab--active');
            var panel = document.getElementById(tabMap[btn.dataset.tab]);
            if(panel) panel.classList.add('op-panel--active');
            history.replaceState(null,'','/settings/operational?tab='+btn.dataset.tab);
        });
    });

    // Open tab from URL ?tab= or hash
    var params = new URLSearchParams(window.location.search);
    var initialTab = params.get('tab') || '';
    if(initialTab && tabMap[initialTab]){
        document.querySelectorAll('.op-tab').forEach(function(b){b.classList.remove('op-tab--active');if(b.dataset.tab===initialTab)b.classList.add('op-tab--active');});
        document.querySelectorAll('.op-panel').forEach(function(p){p.classList.remove('op-panel--active');});
        var p=document.getElementById(tabMap[initialTab]);if(p)p.classList.add('op-panel--active');
    }

    // Drag and drop reorder
    document.querySelectorAll('[data-sortable]').forEach(function(list){
        var type = list.dataset.sortable;
        var dragItem = null;

        list.addEventListener('dragstart',function(e){
            dragItem = e.target.closest('.op-item');
            if(dragItem) dragItem.classList.add('dragging');
        });
        list.addEventListener('dragend',function(e){
            if(dragItem) dragItem.classList.remove('dragging');
            list.querySelectorAll('.op-item').forEach(function(it){it.classList.remove('drag-over');});
            dragItem = null;
        });
        list.addEventListener('dragover',function(e){
            e.preventDefault();
            var target = e.target.closest('.op-item');
            if(!target || target === dragItem) return;
            list.querySelectorAll('.op-item').forEach(function(it){it.classList.remove('drag-over');});
            target.classList.add('drag-over');
            var rect = target.getBoundingClientRect();
            var mid = rect.top + rect.height / 2;
            if(e.clientY < mid){
                list.insertBefore(dragItem, target);
            } else {
                list.insertBefore(dragItem, target.nextSibling);
            }
        });
        list.addEventListener('drop',function(e){
            e.preventDefault();
            list.querySelectorAll('.op-item').forEach(function(it){it.classList.remove('drag-over');});
            // Save new order
            var ids = [];
            list.querySelectorAll('.op-item').forEach(function(it){ids.push(parseInt(it.dataset.id));});
            var fd = new FormData();
            fd.append('_csrf', csrf);
            fd.append('type', type);
            fd.append('ids', JSON.stringify(ids));
            fetch('/settings/operational/reorder',{method:'POST',body:fd,credentials:'same-origin',headers:{'X-CSRF-Token':csrf}})
                .then(function(r){return r.json();})
                .then(function(j){/* silently saved */})
                .catch(function(){});
        });
    });
})();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
