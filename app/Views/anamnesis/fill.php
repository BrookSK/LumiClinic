<?php
$title = 'Preencher anamnese';
$csrf  = $_SESSION['_csrf'] ?? '';
$patient   = $patient ?? null;
$template  = $template ?? null;
$fields    = $fields ?? [];
$professionals = $professionals ?? [];
$defaultProfessionalId = isset($default_professional_id) ? (int)$default_professional_id : 0;
$lockProfessional = isset($lock_professional) && (int)$lock_professional === 1;

$patientId = (int)($patient['id'] ?? 0);

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($template['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <a class="lc-btn lc-btn--secondary" href="/anamnesis?patient_id=<?= $patientId ?>">Voltar</a>
</div>

<div class="lc-card">
    <div class="lc-card__body">
        <form method="post" action="/anamnesis/fill" class="lc-form" id="anamnesis-fill-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="patient_id" value="<?= $patientId ?>" />
            <input type="hidden" name="template_id" value="<?= (int)($template['id'] ?? 0) ?>" />
            <input type="hidden" name="signature_data_url" id="signature_data_url" value="" />

            <?php if ($lockProfessional && $defaultProfessionalId > 0): ?>
                <input type="hidden" name="professional_id" value="<?= $defaultProfessionalId ?>" />
            <?php elseif (!empty($professionals)): ?>
                <div class="lc-field" style="margin-bottom:16px;">
                    <label class="lc-label">Profissional (opcional)</label>
                    <select class="lc-select" name="professional_id">
                        <option value="">(opcional)</option>
                        <?php foreach ($professionals as $pr): ?>
                            <option value="<?= (int)$pr['id'] ?>" <?= ((int)$pr['id'] === $defaultProfessionalId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Campos do template -->
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
                <div class="lc-field" style="margin-bottom:14px;">
                    <label class="lc-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
                    <?php if ($type === 'textarea'): ?>
                        <textarea class="lc-input" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" rows="3"></textarea>
                    <?php elseif ($type === 'checkbox'): ?>
                        <div class="lc-flex lc-gap-sm">
                            <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                                <input type="radio" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="1" /> Sim
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                                <input type="radio" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="0" checked /> Não
                            </label>
                        </div>
                    <?php elseif ($type === 'select'): ?>
                        <select class="lc-select" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                            <option value="">Selecione</option>
                            <?php foreach ($opts as $o): ?>
                                <option value="<?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($type === 'number'): ?>
                        <input class="lc-input" type="number" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" />
                    <?php elseif ($type === 'date'): ?>
                        <input class="lc-input" type="date" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" />
                    <?php else: ?>
                        <input class="lc-input" type="text" name="a_<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" />
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Assinatura digital -->
            <div style="margin-top:20px; padding-top:16px; border-top:1px solid rgba(0,0,0,.08);">
                <div style="font-weight:700; margin-bottom:6px;">Assinatura do paciente</div>
                <div class="lc-muted" style="font-size:12px; margin-bottom:10px;">Peça ao paciente para assinar abaixo confirmando as informações.</div>
                <div style="border:2px dashed rgba(0,0,0,.15); border-radius:10px; background:#fafafa; position:relative;">
                    <canvas id="sig-canvas" style="display:block; width:100%; height:160px; cursor:crosshair; touch-action:none; border-radius:8px;"></canvas>
                    <button type="button" onclick="clearSig()" style="position:absolute; top:8px; right:8px; background:rgba(0,0,0,.06); border:none; border-radius:6px; padding:4px 10px; font-size:12px; cursor:pointer;">Limpar</button>
                </div>
                <div class="lc-muted" style="font-size:11px; margin-top:4px;">Assine com o dedo ou mouse.</div>
            </div>

            <div class="lc-flex lc-gap-sm" style="margin-top:16px;">
                <button class="lc-btn lc-btn--primary" type="submit" onclick="captureSignature()">Salvar anamnese</button>
                <a class="lc-btn lc-btn--secondary" href="/anamnesis?patient_id=<?= $patientId ?>">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    var canvas = document.getElementById('sig-canvas');
    var sigInput = document.getElementById('signature_data_url');
    if (!canvas) return;

    // Ajustar resolução do canvas
    function resizeCanvas() {
        var rect = canvas.getBoundingClientRect();
        canvas.width  = rect.width  * window.devicePixelRatio;
        canvas.height = rect.height * window.devicePixelRatio;
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
    }

    var ctx = canvas.getContext('2d');
    ctx.strokeStyle = '#111';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    resizeCanvas();

    var drawing = false;
    var hasDrawn = false;

    function getPos(e) {
        var rect = canvas.getBoundingClientRect();
        var src = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }

    canvas.addEventListener('pointerdown', function(e){
        drawing = true;
        var p = getPos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        e.preventDefault();
    });
    canvas.addEventListener('pointermove', function(e){
        if (!drawing) return;
        var p = getPos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        hasDrawn = true;
        e.preventDefault();
    });
    canvas.addEventListener('pointerup',     function(){ drawing = false; });
    canvas.addEventListener('pointercancel', function(){ drawing = false; });

    window.clearSig = function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasDrawn = false;
        if (sigInput) sigInput.value = '';
    };

    window.captureSignature = function() {
        if (hasDrawn && sigInput) {
            sigInput.value = canvas.toDataURL('image/png');
        }
    };
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
