<?php
$title = 'Comparar imagens';
$patient = $patient ?? null;
$beforeId = (int)($before_id ?? 0);
$afterId = (int)($after_id ?? 0);
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Comparação</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">Arraste o controle para comparar Antes/Depois.</div>
</div>

<div class="lc-card">
    <div class="lc-card__body">
        <div id="ba-wrap" class="lc-ba-wrap" style="position:relative; width:100%; max-width:980px; margin:0 auto; aspect-ratio: 16 / 9; overflow:hidden; border-radius:12px;">
            <img id="img-after" src="/medical-images/file?id=<?= $afterId ?>" alt="Depois" style="position:absolute; inset:0; width:100%; height:100%; object-fit:contain;" />
            <div id="before-clip" style="position:absolute; inset:0; width:50%; overflow:hidden;">
                <img id="img-before" src="/medical-images/file?id=<?= $beforeId ?>" alt="Antes" style="position:absolute; inset:0; width:100%; height:100%; object-fit:contain;" />
            </div>
            <div id="divider" class="lc-ba-divider" style="position:absolute; top:0; bottom:0; left:50%; width:2px;"></div>
            <div id="handle" class="lc-flex lc-ba-handle" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:36px; height:36px; border-radius:999px; align-items:center; justify-content:center; font-weight:700; user-select:none;">↔</div>
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
  var range = document.getElementById('ba-range');
  var clip = document.getElementById('before-clip');
  var divider = document.getElementById('divider');
  var handle = document.getElementById('handle');

  function set(v){
    var pct = Math.max(0, Math.min(100, v));
    clip.style.width = pct + '%';
    divider.style.left = pct + '%';
    handle.style.left = pct + '%';
  }

  range.addEventListener('input', function(){ set(parseInt(range.value, 10) || 50); });
  set(50);
})();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
