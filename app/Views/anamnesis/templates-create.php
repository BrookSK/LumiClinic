<?php
$title = 'Novo template de anamnese';
$csrf  = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;

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

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div style="font-weight:800; font-size:18px;">Novo template de anamnese</div>
    <a class="lc-btn lc-btn--secondary" href="/anamnesis/templates">Voltar</a>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="/anamnesis/templates/create" id="tpl-form" class="lc-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="fields_json" id="fields_json" value="[]" />

    <div class="lc-card" style="margin-bottom:14px;">
        <div class="lc-card__body">
            <div class="lc-field">
                <label class="lc-label">Nome do template</label>
                <input class="lc-input" type="text" name="name" required placeholder="Ex: Anamnese Botox, Anamnese Geral..." />
            </div>
        </div>
    </div>

    <!-- Campos -->
    <div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:10px;">
        <div style="font-weight:700;">Campos do formulário</div>
        <?php if ($can('anamnesis.manage')): ?>
            <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="addField()">+ Adicionar campo</button>
        <?php endif; ?>
    </div>

    <div id="fields-container" style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;"></div>

    <div class="lc-flex lc-gap-sm">
        <?php if ($can('anamnesis.manage')): ?>
            <button class="lc-btn lc-btn--primary" type="submit" onclick="collectFields()">Criar template</button>
        <?php endif; ?>
        <a class="lc-btn lc-btn--secondary" href="/anamnesis/templates">Cancelar</a>
    </div>
</form>

<script>
(function(){
    var container = document.getElementById('fields-container');
    var hiddenJson = document.getElementById('fields_json');

    var typeLabels = {
        text: 'Texto curto', textarea: 'Texto longo', checkbox: 'Sim/Não',
        select: 'Múltipla escolha', number: 'Número', date: 'Data'
    };

    function slugify(s) {
        return String(s||'').toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
            .replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'').substring(0,64);
    }

    function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function makeCard(field) {
        var div = document.createElement('div');
        div.className = 'lc-card';
        div.style.margin = '0';
        div.setAttribute('data-field', '1');

        var typeOpts = Object.entries(typeLabels).map(function(e){
            return '<option value="'+e[0]+'"'+(field.field_type===e[0]?' selected':'')+'>'+e[1]+'</option>';
        }).join('');

        var optionsVal = Array.isArray(field.options) ? field.options.join('\n') : '';
        var showOpts = field.field_type === 'select';

        div.innerHTML = '<div style="padding:12px 14px;">'
            + '<div class="lc-flex lc-flex--between lc-flex--center" style="gap:10px; margin-bottom:10px;">'
            + '<div class="lc-flex lc-gap-sm" style="align-items:center; flex:1; min-width:0;">'
            + '<button type="button" onclick="moveUp(this)" style="background:none;border:1px solid rgba(0,0,0,.12);border-radius:6px;padding:2px 8px;cursor:pointer;font-size:14px;">↑</button>'
            + '<button type="button" onclick="moveDown(this)" style="background:none;border:1px solid rgba(0,0,0,.12);border-radius:6px;padding:2px 8px;cursor:pointer;font-size:14px;">↓</button>'
            + '<input class="lc-input" type="text" data-role="label" value="'+esc(field.label||'')+'" placeholder="Pergunta (ex: Tem alergias?)" style="flex:1;" />'
            + '</div>'
            + '<div class="lc-flex lc-gap-sm" style="align-items:center; flex-shrink:0;">'
            + '<select class="lc-select" data-role="type" onchange="onTypeChange(this)" style="min-width:160px;">'+typeOpts+'</select>'
            + '<button type="button" onclick="removeField(this)" style="background:none;border:1px solid #fecaca;color:#b91c1c;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:13px;">✕</button>'
            + '</div>'
            + '</div>'
            + '<div data-role="options-wrap" style="display:'+(showOpts?'block':'none')+';">'
            + '<div class="lc-muted" style="font-size:12px; margin-bottom:4px;">Opções (uma por linha)</div>'
            + '<textarea class="lc-input" data-role="options" rows="3" placeholder="Opção 1\nOpção 2\nOpção 3">'+esc(optionsVal)+'</textarea>'
            + '</div>'
            + '</div>';

        // Auto-slug ao digitar label
        var labelEl = div.querySelector('[data-role="label"]');
        if (labelEl) {
            labelEl.addEventListener('input', function(){
                // nada — slug gerado no collect
            });
        }

        container.appendChild(div);
    }

    window.addField = function() {
        makeCard({ field_key:'', label:'', field_type:'text', options:[] });
        var cards = container.querySelectorAll('[data-field]');
        var last = cards[cards.length-1];
        if (last) {
            var inp = last.querySelector('[data-role="label"]');
            if (inp) inp.focus();
        }
    };

    window.removeField = function(btn) {
        var card = btn.closest('[data-field]');
        if (card) card.remove();
    };

    window.moveUp = function(btn) {
        var card = btn.closest('[data-field]');
        if (!card) return;
        var prev = card.previousElementSibling;
        if (prev && prev.hasAttribute('data-field')) container.insertBefore(card, prev);
    };

    window.moveDown = function(btn) {
        var card = btn.closest('[data-field]');
        if (!card) return;
        var next = card.nextElementSibling;
        if (next && next.hasAttribute('data-field')) container.insertBefore(next, card);
    };

    window.onTypeChange = function(sel) {
        var card = sel.closest('[data-field]');
        if (!card) return;
        var wrap = card.querySelector('[data-role="options-wrap"]');
        if (wrap) wrap.style.display = sel.value === 'select' ? 'block' : 'none';
    };

    window.collectFields = function() {
        var cards = container.querySelectorAll('[data-field]');
        var out = [];
        cards.forEach(function(card, idx){
            var label = (card.querySelector('[data-role="label"]')||{}).value || '';
            var type  = (card.querySelector('[data-role="type"]')||{}).value || 'text';
            var optsRaw = (card.querySelector('[data-role="options"]')||{}).value || '';
            var opts = optsRaw.split(/\r?\n/).map(function(x){ return x.trim(); }).filter(Boolean);
            var key = slugify(label) || ('field_' + idx);
            var obj = { field_key: key, label: label.trim(), field_type: type, sort_order: idx };
            if (type === 'select') obj.options = opts;
            out.push(obj);
        });
        hiddenJson.value = JSON.stringify(out);
    };

    // Campos padrão
    [
        { field_key:'allergies', label:'Possui alergias? Quais?', field_type:'textarea' },
        { field_key:'medications', label:'Usa algum medicamento?', field_type:'textarea' },
        { field_key:'smoker', label:'Fumante?', field_type:'checkbox' },
        { field_key:'pregnant', label:'Gestante ou amamentando?', field_type:'checkbox' },
    ].forEach(makeCard);
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
