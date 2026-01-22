<?php
$title = 'Assinar termo';
$csrf = $_SESSION['_csrf'] ?? '';
$patient = $patient ?? null;
$term = $term ?? null;
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Assinatura</div>
    <div>
        <a class="lc-btn lc-btn--secondary" href="/consent?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($term['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">
        <div style="margin-bottom:10px; color:rgba(244,236,212,0.72);">
            Procedimento: <?= htmlspecialchars((string)($term['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div style="white-space:pre-wrap; line-height:1.6;">
            <?= nl2br(htmlspecialchars((string)($term['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
        </div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Assinatura</div>

    <div class="lc-card__body" style="margin-bottom:10px;">
        Use o mouse ou toque para assinar.
    </div>

    <canvas id="sig" width="720" height="220" style="width:100%; max-width:720px; border:1px solid rgba(203,169,106,0.22); border-radius:12px; background:rgba(7,8,12,0.55);"></canvas>

    <form method="post" action="/consent/accept" style="margin-top:14px;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />
        <input type="hidden" name="term_id" value="<?= (int)($term['id'] ?? 0) ?>" />
        <input type="hidden" id="signature" name="signature" value="" />

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--secondary" type="button" id="clear">Limpar</button>
            <button class="lc-btn lc-btn--primary" type="submit" id="submit">Confirmar e salvar</button>
        </div>
    </form>
</div>

<script>
(function(){
  const canvas = document.getElementById('sig');
  const ctx = canvas.getContext('2d');
  ctx.lineWidth = 2;
  ctx.lineCap = 'round';
  ctx.strokeStyle = 'rgba(244,236,212,0.9)';

  let drawing = false;
  let last = null;

  function pos(e){
    const rect = canvas.getBoundingClientRect();
    const clientX = (e.touches && e.touches[0]) ? e.touches[0].clientX : e.clientX;
    const clientY = (e.touches && e.touches[0]) ? e.touches[0].clientY : e.clientY;
    return {
      x: (clientX - rect.left) * (canvas.width / rect.width),
      y: (clientY - rect.top) * (canvas.height / rect.height)
    };
  }

  function start(e){
    drawing = true;
    last = pos(e);
    e.preventDefault();
  }

  function move(e){
    if(!drawing) return;
    const p = pos(e);
    ctx.beginPath();
    ctx.moveTo(last.x, last.y);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    last = p;
    e.preventDefault();
  }

  function end(e){
    drawing = false;
    last = null;
    e.preventDefault();
  }

  canvas.addEventListener('mousedown', start);
  canvas.addEventListener('mousemove', move);
  window.addEventListener('mouseup', end);

  canvas.addEventListener('touchstart', start, {passive:false});
  canvas.addEventListener('touchmove', move, {passive:false});
  window.addEventListener('touchend', end, {passive:false});

  document.getElementById('clear').addEventListener('click', function(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
  });

  document.getElementById('submit').addEventListener('click', function(){
    document.getElementById('signature').value = canvas.toDataURL('image/png');
  });
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
