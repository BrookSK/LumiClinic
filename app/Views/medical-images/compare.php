<?php
$title = 'Comparar imagens';
$patient = $patient ?? null;
$beforeId = (int)($before_id ?? 0);
$afterId = (int)($after_id ?? 0);

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
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Comparação</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
        <?php if ($can('files.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/medical-images/annotate?id=<?= (int)$beforeId ?>">Marcar (Antes)</a>
            <a class="lc-btn lc-btn--secondary" href="/medical-images/annotate?id=<?= (int)$afterId ?>">Marcar (Depois)</a>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">Arraste o controle para comparar Antes/Depois.</div>
</div>

<div class="lc-card">
    <div class="lc-card__body">
        <div id="ba-wrap" class="lc-ba-wrap" style="position:relative; width:100%; max-width:980px; margin:0 auto; aspect-ratio: 16 / 9; overflow:hidden; border-radius:12px; touch-action:none; background:#111;">
            <img id="img-after" src="/medical-images/file?id=<?= $afterId ?>" alt="Depois" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;" />
            <div id="before-clip" style="position:absolute; inset:0; width:50%; overflow:hidden; will-change:width;">
                <img id="img-before" src="/medical-images/file?id=<?= $beforeId ?>" alt="Antes" style="position:absolute; inset:0; height:100%; width:200%; object-fit:cover; max-width:none;" />
            </div>
            <div id="divider" class="lc-ba-divider" style="position:absolute; top:0; bottom:0; left:50%; width:2px; background:rgba(255,255,255,.85); box-shadow: 0 0 0 1px rgba(0,0,0,.25);"></div>
            <div id="handle" class="lc-flex lc-ba-handle" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:44px; height:44px; border-radius:999px; align-items:center; justify-content:center; font-weight:800; user-select:none; cursor:ew-resize; background:rgba(255,255,255,.92); color:#111; box-shadow:0 6px 18px rgba(0,0,0,.25);">↔</div>
        </div>
        <div style="max-width:980px; margin:12px auto 0;">
            <input id="ba-range" type="range" min="0" max="100" value="50" style="width:100%;" />
            <div class="lc-flex lc-flex--between" style="margin-top:6px;">
                <div class="lc-muted">ANTES</div>
                <div class="lc-muted">DEPOIS</div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
  var wrap = document.getElementById('ba-wrap');
  var range = document.getElementById('ba-range');
  var clip = document.getElementById('before-clip');
  var divider = document.getElementById('divider');
  var handle = document.getElementById('handle');
  var imgBefore = document.getElementById('img-before');
  if (!wrap || !range || !clip || !divider || !handle) return;

  function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }

  function setPct(pct){
    var v = clamp(pct, 0, 100);
    clip.style.width = v + '%';
    divider.style.left = v + '%';
    handle.style.left = v + '%';
    if (imgBefore) {
      var safe = clamp(v, 1, 100);
      imgBefore.style.width = (100 / safe * 100) + '%';
    }
    range.value = String(Math.round(v));
  }

  function pctFromClientX(clientX){
    var rect = wrap.getBoundingClientRect();
    var x = clamp(clientX - rect.left, 0, rect.width);
    return (x / rect.width) * 100;
  }

  range.addEventListener('input', function(){
    setPct(parseInt(range.value, 10) || 50);
  });

  function onPointerDown(e){
    try {
      wrap.setPointerCapture(e.pointerId);
    } catch (err) {}
    setPct(pctFromClientX(e.clientX));
    e.preventDefault();
  }

  function onPointerMove(e){
    if (e.buttons === 0 && e.pressure === 0) return;
    setPct(pctFromClientX(e.clientX));
    e.preventDefault();
  }

  wrap.addEventListener('pointerdown', onPointerDown);
  wrap.addEventListener('pointermove', onPointerMove);
  handle.addEventListener('pointerdown', onPointerDown);
  handle.addEventListener('pointermove', onPointerMove);

  setPct(50);
})();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
