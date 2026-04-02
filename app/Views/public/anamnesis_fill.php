<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Anamnese</title>
<link rel="icon" href="/icone_1.png" />
<link rel="stylesheet" href="/assets/css/design-system.css" />
<style>
body { background: #f4ecd4; }
.pub-wrap { max-width: 560px; margin: 0 auto; padding: 24px 16px 48px; }
.pub-header { display:flex; align-items:center; gap:14px; margin-bottom:24px; }
.pub-logo { width:48px; height:48px; border-radius:10px; object-fit:contain; background:#000; }
.pub-title { font-weight:800; font-size:20px; color:#2a2a2a; }
.pub-subtitle { font-size:13px; color:#6b7280; margin-top:2px; }
.pub-card { background:#fffdf8; border-radius:14px; padding:24px; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:16px; }
.pub-field { margin-bottom:16px; }
.pub-label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.pub-input { width:100%; box-sizing:border-box; padding:10px 12px; border:1px solid rgba(0,0,0,.15); border-radius:8px; font-size:14px; font-family:inherit; background:#fff; }
.pub-input:focus { outline:none; border-color:#eeb810; box-shadow:0 0 0 3px rgba(238,184,16,.15); }
.pub-radio { display:flex; gap:16px; }
.pub-radio label { display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:14px; }
.pub-btn { width:100%; padding:14px; background:linear-gradient(135deg,#fde59f,#815901); color:#fff; font-weight:700; font-size:15px; border:none; border-radius:10px; cursor:pointer; font-family:inherit; }
.pub-btn:hover { opacity:.9; }
.sig-wrap { border:2px dashed rgba(0,0,0,.15); border-radius:10px; background:#fafafa; position:relative; }
.sig-canvas { display:block; width:100%; height:160px; cursor:crosshair; touch-action:none; border-radius:8px; }
.sig-clear { position:absolute; top:8px; right:8px; background:rgba(0,0,0,.06); border:none; border-radius:6px; padding:4px 10px; font-size:12px; cursor:pointer; }
</style>
</head>
<body>
<div class="pub-wrap">

    <div class="pub-header">
        <img src="/icone_1.png" alt="Logo" class="pub-logo" />
        <div>
            <div class="pub-title">Anamnese</div>
            <div class="pub-subtitle">Preencha com atenção e assine ao final.</div>
        </div>
    </div>

    <?php
    $csrf    = $_SESSION['_csrf'] ?? '';
    $error   = $error ?? null;
    $success = $success ?? null;
    $token   = $token ?? '';
    $template = $template ?? null;
    $fields  = $fields ?? [];
    ?>

    <?php if ($error): ?>
        <div class="pub-card" style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php elseif ($success): ?>
        <div class="pub-card" style="text-align:center; padding:40px 24px;">
            <div style="font-size:48px; margin-bottom:12px;">✅</div>
            <div style="font-weight:800; font-size:18px; margin-bottom:8px;">Anamnese enviada!</div>
            <div style="color:#6b7280;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php elseif (is_array($template)): ?>

        <div class="pub-card" style="margin-bottom:8px;">
            <div style="font-weight:700; font-size:16px;"><?= htmlspecialchars((string)($template['name'] ?? 'Anamnese'), ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <form method="post" action="/a/anamnese" id="pub-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="token" value="<?= htmlspecialchars((string)$token, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="signature_data_url" id="pub_sig_input" value="" />

            <div class="pub-card">
                <?php foreach ($fields as $f): ?>
                    <?php
                    $key   = (string)($f['field_key'] ?? '');
                    $label = (string)($f['label'] ?? $key);
                    $type  = (string)($f['field_type'] ?? 'text');
                    $opts  = [];
                    if (!empty($f['options_json'])) {
                        $decoded = json_decode((string)$f['options_json'], true);
                        if (is_array($decoded)) $opts = $decoded;
                    }
                    ?>
                    <div class="pub-field">
                        <label class="pub-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
                        <?php if ($type === 'textarea'): ?>
                            <textarea class="pub-input" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" rows="3" style="resize:vertical;"></textarea>
                        <?php elseif ($type === 'checkbox'): ?>
                            <div class="pub-radio">
                                <label><input type="radio" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="1" /> Sim</label>
                                <label><input type="radio" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="0" checked /> Não</label>
                            </div>
                        <?php elseif ($type === 'select'): ?>
                            <select class="pub-input" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                                <option value="">Selecione</option>
                                <?php foreach ($opts as $o): ?>
                                    <option value="<?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'number'): ?>
                            <input class="pub-input" type="number" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" />
                        <?php elseif ($type === 'date'): ?>
                            <input class="pub-input" type="date" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" />
                        <?php else: ?>
                            <input class="pub-input" type="text" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" />
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Assinatura -->
            <div class="pub-card">
                <div style="font-weight:700; margin-bottom:4px;">Assinatura</div>
                <div style="font-size:12px; color:#6b7280; margin-bottom:10px;">
                    Ao assinar, você confirma que as informações acima são verdadeiras.
                </div>
                <div class="sig-wrap">
                    <canvas id="pub-sig-canvas" class="sig-canvas"></canvas>
                    <button type="button" class="sig-clear" onclick="clearPubSig()">Limpar</button>
                </div>
                <div style="font-size:11px; color:#9ca3af; margin-top:4px;">Assine com o dedo ou mouse.</div>
            </div>

            <button class="pub-btn" type="submit" onclick="capturePubSig()">Enviar anamnese</button>
        </form>

    <?php endif; ?>

    <div style="text-align:center; margin-top:20px; font-size:12px; color:#9ca3af;">LumiClinic</div>
</div>

<script>
(function(){
    var canvas = document.getElementById('pub-sig-canvas');
    var sigInput = document.getElementById('pub_sig_input');
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    ctx.strokeStyle = '#111';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    function resize() {
        var rect = canvas.getBoundingClientRect();
        var dpr = window.devicePixelRatio || 1;
        canvas.width  = rect.width  * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        ctx.strokeStyle = '#111';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }
    resize();
    window.addEventListener('resize', resize);

    var drawing = false, hasDrawn = false;

    function pos(e) {
        var r = canvas.getBoundingClientRect();
        var s = e.touches ? e.touches[0] : e;
        return { x: s.clientX - r.left, y: s.clientY - r.top };
    }

    canvas.addEventListener('pointerdown', function(e){ drawing=true; var p=pos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); e.preventDefault(); });
    canvas.addEventListener('pointermove', function(e){ if(!drawing) return; var p=pos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); hasDrawn=true; e.preventDefault(); });
    canvas.addEventListener('pointerup',     function(){ drawing=false; });
    canvas.addEventListener('pointercancel', function(){ drawing=false; });

    window.clearPubSig = function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasDrawn = false;
        if (sigInput) sigInput.value = '';
    };

    window.capturePubSig = function() {
        if (hasDrawn && sigInput) {
            sigInput.value = canvas.toDataURL('image/png');
        }
    };
})();
</script>
</body>
</html>
