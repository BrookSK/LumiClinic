<?php
$title = 'Novo template de prontuário';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;

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
<div class="lc-card">
    <div class="lc-card__title">Novo template</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/medical-record-templates/create" id="mr-template-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" required />

        <label class="lc-label">Campos</label>
        <input type="hidden" name="fields_json" id="fields_json" value="[]" />

        <div class="lc-card" style="margin-top:10px;">
            <div class="lc-card__body">
                <div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="gap:10px; margin-bottom:10px;">
                    <div class="lc-muted">Adicione os campos do formulário (você pode marcar obrigatórios).</div>
                    <?php if ($can('medical_record_templates.manage')): ?>
                        <button class="lc-btn lc-btn--secondary" type="button" id="add-field">Adicionar campo</button>
                    <?php endif; ?>
                </div>

                <div class="lc-table-wrap">
                    <table class="lc-table" id="fields-table">
                        <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Rótulo</th>
                            <th>Tipo</th>
                            <th>Obrigatório</th>
                            <th>Opções (select)</th>
                            <th>Ações</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <?php if ($can('medical_record_templates.manage')): ?>
                <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
            <?php endif; ?>
            <a class="lc-btn lc-btn--secondary" href="/medical-record-templates">Voltar</a>
        </div>
    </form>
</div>

<script>
(function(){
  var form = document.getElementById('mr-template-form');
  var addBtn = document.getElementById('add-field');
  var tableBody = document.querySelector('#fields-table tbody');
  var hidden = document.getElementById('fields_json');

  function esc(s){
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function slugify(s){
    return String(s ?? '')
      .toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
      .replace(/[^a-z0-9]+/g,'_')
      .replace(/^_+|_+$/g,'')
      .substring(0, 64);
  }

  function rowTemplate(field){
    var type = field.field_type || 'text';
    var options = Array.isArray(field.options) ? field.options.join('\n') : '';
    var required = field.required ? 1 : 0;

    return ''
      + '<tr>'
      + '  <td style="width:120px;">'
      + '    <div class="lc-flex" style="gap:6px; align-items:center;">'
      + '      <button class="lc-btn lc-btn--secondary" type="button" data-action="move-up" aria-label="Subir">↑</button>'
      + '      <button class="lc-btn lc-btn--secondary" type="button" data-action="move-down" aria-label="Descer">↓</button>'
      + '      <span class="lc-muted" data-role="order-label"></span>'
      + '    </div>'
      + '  </td>'
      + '  <td>'
      + '    <input type="hidden" name="field_key" value="' + esc(field.field_key ?? '') + '" />'
      + '    <input class="lc-input" type="text" name="label" value="' + esc(field.label ?? '') + '" required />'
      + '  </td>'
      + '  <td style="width:160px;">'
      + '    <select class="lc-select" name="field_type">'
      + '      <option value="text"' + (type==='text'?' selected':'') + '>Texto</option>'
      + '      <option value="textarea"' + (type==='textarea'?' selected':'') + '>Texto longo</option>'
      + '      <option value="checkbox"' + (type==='checkbox'?' selected':'') + '>Checkbox</option>'
      + '      <option value="select"' + (type==='select'?' selected':'') + '>Select</option>'
      + '      <option value="number"' + (type==='number'?' selected':'') + '>Número</option>'
      + '      <option value="date"' + (type==='date'?' selected':'') + '>Data</option>'
      + '    </select>'
      + '  </td>'
      + '  <td style="width:140px;">'
      + '    <select class="lc-select" name="required">'
      + '      <option value="0"' + (required===0?' selected':'') + '>Não</option>'
      + '      <option value="1"' + (required===1?' selected':'') + '>Sim</option>'
      + '    </select>'
      + '  </td>'
      + '  <td><textarea class="lc-input" name="options" rows="3" placeholder="1 opção por linha">' + esc(options) + '</textarea></td>'
      + '  <td style="width:120px;"><button class="lc-btn lc-btn--danger" type="button" data-action="remove">Remover</button></td>'
      + '</tr>';
  }

  function refreshOrderLabels(){
    var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr'));
    rows.forEach(function(r, idx){
      var el = r.querySelector('[data-role="order-label"]');
      if (el) el.textContent = String(idx + 1);
    });
  }

  function addField(field){
    var tr = document.createElement('tr');
    tr.innerHTML = rowTemplate(field).replace(/^<tr>|<\/tr>$/g,'');
    tableBody.appendChild(tr);
    refreshOrderLabels();
  }

  function collect(){
    var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr'));
    return rows.map(function(r, idx){
      var fieldKey = (r.querySelector('input[name="field_key"]') || {}).value || '';
      var label = (r.querySelector('input[name="label"]') || {}).value || '';
      var fieldType = (r.querySelector('select[name="field_type"]') || {}).value || 'text';
      var required = parseInt(((r.querySelector('select[name="required"]') || {}).value || '0'), 10) || 0;
      var optionsRaw = (r.querySelector('textarea[name="options"]') || {}).value || '';
      var options = optionsRaw.split(/\r?\n/).map(function(x){ return x.trim(); }).filter(Boolean);

      fieldKey = String(fieldKey || '').trim();
      if (fieldKey === '') {
        fieldKey = slugify(label);
      }

      var obj = {
        field_key: fieldKey.trim(),
        label: label.trim(),
        field_type: fieldType,
        required: (required ? 1 : 0),
        sort_order: idx
      };
      if (fieldType === 'select') {
        obj.options = options;
      }
      return obj;
    });
  }

  addBtn.addEventListener('click', function(){
    addField({ field_key: '', label: '', field_type: 'text', required: 0, options: [], sort_order: tableBody.querySelectorAll('tr').length });
  });

  tableBody.addEventListener('click', function(e){
    var el = e.target;
    if (!el) return;

    if (el.getAttribute('data-action') === 'remove') {
      var tr = el.closest('tr');
      if (tr) tr.remove();
      refreshOrderLabels();
      return;
    }

    if (el.getAttribute('data-action') === 'move-up') {
      var trUp = el.closest('tr');
      if (!trUp) return;
      var prev = trUp.previousElementSibling;
      if (prev) {
        tableBody.insertBefore(trUp, prev);
        refreshOrderLabels();
      }
      return;
    }

    if (el.getAttribute('data-action') === 'move-down') {
      var trDown = el.closest('tr');
      if (!trDown) return;
      var next = trDown.nextElementSibling;
      if (next) {
        tableBody.insertBefore(next, trDown);
        refreshOrderLabels();
      }
      return;
    }
  });

  tableBody.addEventListener('input', function(e){
    var el = e.target;
    if (!el) return;
    if (el.matches('input[name="label"]')) {
      var tr = el.closest('tr');
      if (!tr) return;
      var keyInput = tr.querySelector('input[name="field_key"]');
      if (!keyInput) return;
      if (String(keyInput.value || '').trim() === '') {
        keyInput.value = slugify(el.value);
      }
    }
  });

  form.addEventListener('submit', function(){
    hidden.value = JSON.stringify(collect());
  });

  addField({ field_key: 'complaint', label: 'Queixa principal', field_type: 'textarea', required: 1, options: [], sort_order: 0 });
  addField({ field_key: 'evolution', label: 'Evolução', field_type: 'textarea', required: 1, options: [], sort_order: 1 });
  refreshOrderLabels();
})();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
