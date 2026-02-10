<?php
$title = 'Editar template de anamnese';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$template = $template ?? null;
$fields = $fields ?? [];
ob_start();

$fieldsForJson = [];
foreach ($fields as $f) {
    $opts = null;
    if (isset($f['options_json']) && $f['options_json']) {
        $decoded = json_decode((string)$f['options_json'], true);
        if (is_array($decoded)) {
            $opts = $decoded;
        }
    }

    $fieldsForJson[] = [
        'field_key' => (string)$f['field_key'],
        'label' => (string)$f['label'],
        'field_type' => (string)$f['field_type'],
        'options' => $opts,
        'sort_order' => (int)$f['sort_order'],
    ];
}

$fieldsJson = json_encode($fieldsForJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>
<div class="lc-card">
    <div class="lc-card__title">Template</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/anamnesis/templates/edit" id="anamnesis-template-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($template['id'] ?? 0) ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($template['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Status</label>
        <?php $status = (string)($template['status'] ?? 'active'); ?>
        <select class="lc-select" name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
            <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Desativado</option>
        </select>

        <label class="lc-label">Campos</label>
        <input type="hidden" name="fields_json" id="fields_json" value="<?= htmlspecialchars((string)($fieldsJson ?: '[]'), ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-card" style="margin-top:10px;">
            <div class="lc-card__body">
                <div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="gap:10px; margin-bottom:10px;">
                    <div class="lc-muted">Edite os campos do formulário.</div>
                    <button class="lc-btn lc-btn--secondary" type="button" id="add-field">Adicionar campo</button>
                </div>

                <div class="lc-table-wrap">
                    <table class="lc-table" id="fields-table">
                        <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Chave</th>
                            <th>Rótulo</th>
                            <th>Tipo</th>
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
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/anamnesis/templates">Voltar</a>
        </div>
    </form>
</div>

<script>
(function(){
  var form = document.getElementById('anamnesis-template-form');
  var addBtn = document.getElementById('add-field');
  var tableBody = document.querySelector('#fields-table tbody');
  var hidden = document.getElementById('fields_json');

  function esc(s){
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function rowTemplate(field){
    var type = field.field_type || 'text';
    var options = Array.isArray(field.options) ? field.options.join('\n') : '';

    return ''
      + '<tr>'
      + '  <td style="width:90px;"><input class="lc-input" type="number" name="sort_order" value="' + esc(field.sort_order ?? 0) + '" min="0" /></td>'
      + '  <td><input class="lc-input" type="text" name="field_key" value="' + esc(field.field_key ?? '') + '" required /></td>'
      + '  <td><input class="lc-input" type="text" name="label" value="' + esc(field.label ?? '') + '" required /></td>'
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
      + '  <td><textarea class="lc-input" name="options" rows="3" placeholder="1 opção por linha">' + esc(options) + '</textarea></td>'
      + '  <td style="width:120px;"><button class="lc-btn lc-btn--danger" type="button" data-action="remove">Remover</button></td>'
      + '</tr>';
  }

  function addField(field){
    var tr = document.createElement('tr');
    tr.innerHTML = rowTemplate(field).replace(/^<tr>|<\/tr>$/g,'');
    tableBody.appendChild(tr);
  }

  function collect(){
    var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr'));
    var out = rows.map(function(r){
      var fieldKey = (r.querySelector('input[name="field_key"]') || {}).value || '';
      var label = (r.querySelector('input[name="label"]') || {}).value || '';
      var fieldType = (r.querySelector('select[name="field_type"]') || {}).value || 'text';
      var sortOrderRaw = (r.querySelector('input[name="sort_order"]') || {}).value || '0';
      var sortOrder = parseInt(sortOrderRaw, 10);
      if (!Number.isFinite(sortOrder)) sortOrder = 0;
      var optionsRaw = (r.querySelector('textarea[name="options"]') || {}).value || '';
      var options = optionsRaw.split(/\r?\n/).map(function(x){ return x.trim(); }).filter(Boolean);

      var obj = {
        field_key: fieldKey.trim(),
        label: label.trim(),
        field_type: fieldType,
        sort_order: sortOrder
      };
      if (fieldType === 'select') {
        obj.options = options;
      }
      return obj;
    });

    out.sort(function(a,b){ return (a.sort_order||0) - (b.sort_order||0); });
    return out;
  }

  addBtn.addEventListener('click', function(){
    var next = tableBody.querySelectorAll('tr').length;
    addField({ field_key: '', label: '', field_type: 'text', options: [], sort_order: next });
  });

  tableBody.addEventListener('click', function(e){
    var el = e.target;
    if (!el) return;
    if (el.getAttribute('data-action') === 'remove') {
      var tr = el.closest('tr');
      if (tr) tr.remove();
    }
  });

  try {
    var initial = JSON.parse(hidden.value || '[]');
    if (Array.isArray(initial) && initial.length) {
      initial.forEach(function(f){ addField(f); });
    }
  } catch (e) {}

  form.addEventListener('submit', function(){
    hidden.value = JSON.stringify(collect());
  });
})();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
