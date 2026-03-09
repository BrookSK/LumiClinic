<?php
$title = 'Anotar imagem';
$csrf = $_SESSION['_csrf'] ?? '';
$image = $image ?? null;

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

$imageId = (int)($image['id'] ?? 0);
$patientId = (int)($image['patient_id'] ?? 0);

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Marcações</div>
        <div class="lc-muted" style="margin-top:6px;">Desenhe um retângulo e adicione um texto. As coordenadas são salvas de forma normalizada (0..1).</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= $patientId ?>">Voltar</a>
        <?php if ($can('files.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/medical-images/file?id=<?= $imageId ?>" target="_blank">Abrir imagem</a>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <?php if ($can('files.read')): ?>
            <div id="anno-wrap" style="position:relative; width:100%; max-width:980px; margin:0 auto;">
                <img id="anno-img" src="/medical-images/file?id=<?= $imageId ?>" alt="Imagem" style="display:block; width:100%; height:auto; border-radius:12px;" />
                <div id="anno-layer" style="position:absolute; inset:0; pointer-events:none;"></div>
            </div>
            <div class="lc-muted" style="margin-top:10px;">Dica: clique e arraste sobre a imagem para criar uma marcação.</div>
        <?php else: ?>
            <div class="lc-muted">Você não tem permissão para abrir o arquivo desta imagem.</div>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Nova marcação</div>
    <div class="lc-card__body">
        <?php if ($can('medical_images.upload')): ?>
            <form method="post" action="/medical-images/annotations/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr; align-items:end;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="image_id" value="<?= $imageId ?>" />
                <input type="hidden" id="payload_json" name="payload_json" value="" />

                <div class="lc-field" style="grid-column:1 / -1;">
                    <label class="lc-label">Texto</label>
                    <input class="lc-input" type="text" id="note" name="note" maxlength="255" placeholder="Ex.: Mancha, cicatriz, área de aplicação..." />
                </div>

                <div class="lc-form__actions" style="grid-column:1 / -1;">
                    <button class="lc-btn lc-btn--primary" id="btn-save" type="submit" disabled>Salvar marcação selecionada</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Marcações existentes</div>
    <div class="lc-card__body">
        <div class="lc-table-wrap">
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Texto</th>
                    <th style="width:1%; white-space:nowrap;">Ações</th>
                </tr>
                </thead>
                <tbody id="anno-table-body">
                <tr><td colspan="3" class="lc-muted">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function(){
  var imageId = <?= (int)$imageId ?>;
  var csrf = <?= json_encode((string)$csrf) ?>;
  var canUpload = <?= json_encode((bool)$can('medical_images.upload')) ?>;
  var img = document.getElementById('anno-img');
  var wrap = document.getElementById('anno-wrap');
  var layer = document.getElementById('anno-layer');
  var payloadInput = document.getElementById('payload_json');
  var btnSave = document.getElementById('btn-save');
  var noteInput = document.getElementById('note');
  var tbody = document.getElementById('anno-table-body');

  if (!img || !wrap || !layer || !tbody) return;

  function esc(s){
    return String(s || '').replace(/[&<>"']/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
    });
  }

  function clearLayer(){
    while (layer.firstChild) layer.removeChild(layer.firstChild);
  }

  function drawRectNorm(r, label, color){
    var div = document.createElement('div');
    div.style.position = 'absolute';
    div.style.left = (r.x * 100) + '%';
    div.style.top = (r.y * 100) + '%';
    div.style.width = (r.w * 100) + '%';
    div.style.height = (r.h * 100) + '%';
    div.style.border = '2px solid ' + (color || '#e11d48');
    div.style.borderRadius = '6px';
    div.style.boxSizing = 'border-box';
    div.style.background = 'rgba(225,29,72,0.08)';
    layer.appendChild(div);

    if (label) {
      var tag = document.createElement('div');
      tag.textContent = label;
      tag.style.position = 'absolute';
      tag.style.left = '0';
      tag.style.top = '-26px';
      tag.style.background = 'rgba(15,23,42,0.92)';
      tag.style.color = '#fff';
      tag.style.padding = '4px 8px';
      tag.style.borderRadius = '8px';
      tag.style.fontSize = '12px';
      tag.style.whiteSpace = 'nowrap';
      div.appendChild(tag);
    }
  }

  function fetchAnnotations(){
    fetch('/medical-images/annotations.json?image_id=' + encodeURIComponent(String(imageId)), {
      credentials: 'same-origin'
    }).then(function(r){ return r.json(); }).then(function(data){
      var items = (data && data.items) ? data.items : [];
      clearLayer();
      tbody.innerHTML = '';

      if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="lc-muted">Nenhuma marcação.</td></tr>';
        return;
      }

      items.forEach(function(it){
        var payload = {};
        try { payload = JSON.parse(it.payload_json || '{}'); } catch (e) { payload = {}; }
        if (payload && payload.type === 'rect' && payload.rect) {
          drawRectNorm(payload.rect, it.note || payload.label || '', payload.color);
        }

        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + esc(it.id) + '</td>' +
          '<td>' + esc(it.note || '') + '</td>' +
          '<td>' +
            (canUpload ? (
              '<form method="post" action="/medical-images/annotations/delete" style="display:inline;">' +
                '<input type="hidden" name="_csrf" value="' + esc(csrf) + '" />' +
                '<input type="hidden" name="image_id" value="' + esc(imageId) + '" />' +
                '<input type="hidden" name="id" value="' + esc(it.id) + '" />' +
                '<button class="lc-btn lc-btn--danger" type="submit">Excluir</button>' +
              '</form>'
            ) : '<span style="opacity:.7;">-</span>') +
          '</td>';
        tbody.appendChild(tr);
      });
    }).catch(function(){
      tbody.innerHTML = '<tr><td colspan="3" class="lc-muted">Falha ao carregar.</td></tr>';
    });
  }

  var drawing = false;
  var start = null;
  var lastRect = null;

  function rectFromPoints(a, b){
    var x1 = Math.min(a.x, b.x);
    var y1 = Math.min(a.y, b.y);
    var x2 = Math.max(a.x, b.x);
    var y2 = Math.max(a.y, b.y);
    return { x: x1, y: y1, w: Math.max(0.001, x2 - x1), h: Math.max(0.001, y2 - y1) };
  }

  function normPointFromEvent(e){
    var r = img.getBoundingClientRect();
    var x = (e.clientX - r.left) / r.width;
    var y = (e.clientY - r.top) / r.height;
    x = Math.max(0, Math.min(1, x));
    y = Math.max(0, Math.min(1, y));
    return { x: x, y: y };
  }

  function setSelectedRect(rect){
    lastRect = rect;
    if (payloadInput) {
      payloadInput.value = JSON.stringify({
        type: 'rect',
        rect: rect,
        label: (noteInput && noteInput.value) ? String(noteInput.value) : ''
      });
    }
    if (btnSave) {
      btnSave.disabled = !rect;
    }

    clearLayer();
    drawRectNorm(rect, 'Selecionada', '#2563eb');
  }

  if (canUpload) {
    img.addEventListener('pointerdown', function(e){
      drawing = true;
      start = normPointFromEvent(e);
      setSelectedRect({x:start.x,y:start.y,w:0.001,h:0.001});
      e.preventDefault();
    });

    img.addEventListener('pointermove', function(e){
      if (!drawing || !start) return;
      var cur = normPointFromEvent(e);
      setSelectedRect(rectFromPoints(start, cur));
      e.preventDefault();
    });

    img.addEventListener('pointerup', function(e){
      drawing = false;
      start = null;
    });

    if (noteInput && payloadInput) {
      noteInput.addEventListener('input', function(){
        if (!lastRect) return;
        payloadInput.value = JSON.stringify({ type: 'rect', rect: lastRect, label: String(noteInput.value || '') });
      });
    }
  }

  fetchAnnotations();
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
