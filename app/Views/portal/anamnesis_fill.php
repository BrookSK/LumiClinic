<?php
$title = 'Preencher Anamnese';
$csrf = $_SESSION['_csrf'] ?? '';
$response = $response ?? null;
$template = $template ?? null;
$fields = $fields ?? [];
$patient = $patient ?? null;

$responseId = (int)($response['id'] ?? 0);
$tplName = trim((string)($template['name'] ?? $response['template_name_snapshot'] ?? ''));

ob_start();
?>

<a href="/portal/anamnese" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;"><?= htmlspecialchars($tplName !== '' ? $tplName : 'Anamnese', ENT_QUOTES, 'UTF-8') ?></div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;">Preencha todos os campos e assine ao final.</div>

<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:700px;">
    <form method="post" action="/portal/anamnese/preencher" id="anamnesisForm">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= $responseId ?>" />

        <?php foreach ($fields as $f): ?>
            <?php
            $key = (string)($f['field_key'] ?? '');
            $label = (string)($f['label'] ?? $key);
            $type = (string)($f['field_type'] ?? 'text');
            $required = (int)($f['is_required'] ?? 0) === 1;
            $options = [];
            if (isset($f['options_json']) && trim((string)$f['options_json']) !== '') {
                $decoded = json_decode((string)$f['options_json'], true);
                if (is_array($decoded)) $options = $decoded;
            }
            if ($key === '') continue;
            ?>
            <div class="lc-field">
                <label class="lc-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?><?= $required ? ' *' : '' ?></label>
                <?php if ($type === 'textarea'): ?>
                    <textarea class="lc-input" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" rows="3" <?= $required ? 'required' : '' ?>></textarea>
                <?php elseif ($type === 'select' && $options !== []): ?>
                    <select class="lc-select" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $required ? 'required' : '' ?>>
                        <option value="">Selecione...</option>
                        <?php foreach ($options as $opt): ?>
                            <option value="<?= htmlspecialchars((string)$opt, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$opt, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'checkbox'): ?>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="hidden" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="0" />
                        <input type="checkbox" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="1" style="width:18px;height:18px;" />
                        <span style="font-size:13px;">Sim</span>
                    </label>
                <?php elseif ($type === 'date'): ?>
                    <input class="lc-input" type="date" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $required ? 'required' : '' ?> />
                <?php else: ?>
                    <input class="lc-input" type="text" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $required ? 'required' : '' ?> />
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Assinatura -->
        <div class="lc-field" style="margin-top:16px;">
            <label class="lc-label">Assinatura</label>
            <canvas id="sigCanvas" width="500" height="160" style="border:1px solid rgba(17,24,39,.12);border-radius:12px;background:#fff;width:100%;max-width:500px;touch-action:none;cursor:crosshair;"></canvas>
            <input type="hidden" name="signature_data_url" id="sigData" />
            <div style="margin-top:6px;">
                <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="clearSig()">Limpar assinatura</button>
            </div>
        </div>

        <div style="margin-top:16px;">
            <button class="lc-btn lc-btn--primary" type="submit">Enviar anamnese</button>
        </div>
    </form>
</div>

<script>
(function(){
    var canvas=document.getElementById('sigCanvas'),ctx=canvas.getContext('2d'),drawing=false,sigInput=document.getElementById('sigData');
    function pos(e){var r=canvas.getBoundingClientRect();var t=e.touches?e.touches[0]:e;return{x:t.clientX-r.left,y:t.clientY-r.top};}
    canvas.addEventListener('mousedown',function(e){drawing=true;ctx.beginPath();var p=pos(e);ctx.moveTo(p.x,p.y);});
    canvas.addEventListener('mousemove',function(e){if(!drawing)return;var p=pos(e);ctx.lineTo(p.x,p.y);ctx.strokeStyle='#111';ctx.lineWidth=2;ctx.stroke();});
    canvas.addEventListener('mouseup',function(){drawing=false;});
    canvas.addEventListener('touchstart',function(e){e.preventDefault();drawing=true;ctx.beginPath();var p=pos(e);ctx.moveTo(p.x,p.y);},{passive:false});
    canvas.addEventListener('touchmove',function(e){e.preventDefault();if(!drawing)return;var p=pos(e);ctx.lineTo(p.x,p.y);ctx.strokeStyle='#111';ctx.lineWidth=2;ctx.stroke();},{passive:false});
    canvas.addEventListener('touchend',function(){drawing=false;});
    window.clearSig=function(){ctx.clearRect(0,0,canvas.width,canvas.height);};
    document.getElementById('anamnesisForm').addEventListener('submit',function(){
        try{sigInput.value=canvas.toDataURL('image/png');}catch(e){}
    });
})();
</script>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'anamnese';
require __DIR__ . '/_shell.php';
