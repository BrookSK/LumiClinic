<?php
$title = 'Assinar termo';
$csrf = $_SESSION['_csrf'] ?? '';
$doc = $doc ?? null;
$error = $error ?? ($_GET['error'] ?? null);
ob_start();
?>
<div class="lc-card" style="margin-top:16px; padding:16px;">
    <div class="lc-card__title">Assinatura digital</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin-top:10px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-alert lc-alert--info" style="margin-top:10px;">
        Assine no quadro abaixo para aceitar este termo.
    </div>

    <div style="margin-top:12px;">
        <strong><?= htmlspecialchars((string)($doc['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        <div style="opacity:.8; margin-top:6px; white-space:pre-wrap; line-height:1.6;">
            <?= nl2br(htmlspecialchars((string)($doc['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
        </div>
    </div>

    <form method="post" action="/portal/legal/sign" style="margin-top:12px;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($doc['id'] ?? 0) ?>" />
        <input type="hidden" name="signature_data_url" id="signature_data_url" value="" />

        <div style="border:1px solid #ddd; border-radius:10px; padding:10px; background:#fff;">
            <canvas id="sig" width="700" height="220" style="width:100%; height:220px; display:block;"></canvas>
        </div>

        <div class="lc-flex lc-gap-sm" style="margin-top:12px; align-items:center;">
            <button class="lc-btn lc-btn--primary" type="submit" onclick="return lcSigSubmit();">Assinar</button>
            <button class="lc-btn lc-btn--secondary" type="button" onclick="lcSigClear();">Limpar</button>
            <a class="lc-btn lc-btn--secondary" href="/portal/required-consents">Voltar</a>
        </div>
    </form>
</div>

<script>
(function(){
  const c = document.getElementById('sig');
  if (!c) return;
  const ctx = c.getContext('2d');
  let drawing = false;
  let last = null;

  function pos(e){
    const r = c.getBoundingClientRect();
    const x = (e.clientX - r.left) * (c.width / r.width);
    const y = (e.clientY - r.top) * (c.height / r.height);
    return {x, y};
  }

  function start(e){
    drawing = true;
    last = pos(e);
  }
  function move(e){
    if (!drawing) return;
    const p = pos(e);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#111';
    ctx.beginPath();
    ctx.moveTo(last.x, last.y);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    last = p;
  }
  function end(){ drawing = false; last = null; }

  c.addEventListener('pointerdown', (e)=>{ c.setPointerCapture(e.pointerId); start(e); });
  c.addEventListener('pointermove', move);
  c.addEventListener('pointerup', end);
  c.addEventListener('pointercancel', end);

  window.lcSigClear = function(){
    ctx.clearRect(0,0,c.width,c.height);
  };

  window.lcSigSubmit = function(){
    const data = c.toDataURL('image/png');
    document.getElementById('signature_data_url').value = data;
    if (!data || data.length < 2000) {
      alert('Assinatura vazia.');
      return false;
    }
    return true;
  };
})();
</script>
<?php
$portal_content = (string)ob_get_clean();
$portal_active = null;
require __DIR__ . '/_shell.php';
