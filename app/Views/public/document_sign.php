<?php
$row = $row ?? [];
$alreadySigned = $already_signed ?? false;
$justSigned = $just_signed ?? false;
$title = (string)($row['title'] ?? 'Documento para Assinatura');
$body = (string)($row['body'] ?? '');
$clinicName = (string)($row['clinic_name'] ?? '');
$patientName = (string)($row['patient_name'] ?? '');
$token = (string)($row['token'] ?? '');
$signedAt = (string)($row['signed_at'] ?? '');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:#f4ecd4;color:#1f2937;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .card{background:#fffdf8;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.10);max-width:640px;width:100%;overflow:hidden}
        .header{background:linear-gradient(135deg,#fde59f,#815901);padding:24px 28px;text-align:center}
        .header h1{font-size:18px;color:#fff;font-weight:800}
        .header p{font-size:13px;color:rgba(255,255,255,.85);margin-top:4px}
        .body{padding:28px}
        .meta{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;font-size:13px;color:#6b7280}
        .meta strong{color:#1f2937}
        .doc-body{padding:20px;border:1px solid rgba(17,24,39,.08);border-radius:12px;background:rgba(0,0,0,.01);margin-bottom:20px;font-size:13px;line-height:1.7;white-space:pre-wrap;max-height:400px;overflow-y:auto}
        .sig-area{margin-bottom:16px}
        .sig-label{font-weight:700;font-size:13px;margin-bottom:8px;color:#374151}
        .sig-canvas{border:2px dashed rgba(17,24,39,.15);border-radius:12px;background:#fff;cursor:crosshair;display:block;width:100%;height:180px;touch-action:none}
        .sig-actions{display:flex;gap:8px;margin-top:8px}
        .btn{display:inline-block;padding:12px 24px;border-radius:8px;font-weight:700;font-size:14px;border:none;cursor:pointer;text-align:center}
        .btn-primary{background:linear-gradient(135deg,#fde59f,#815901);color:#fff}
        .btn-secondary{background:#e5e7eb;color:#374151}
        .btn-primary:hover{opacity:.9}
        .success{text-align:center;padding:40px 20px}
        .success-icon{font-size:48px;margin-bottom:12px}
        .success h2{font-size:18px;color:#16a34a;margin-bottom:6px}
        .success p{font-size:13px;color:#6b7280}
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h1><?= htmlspecialchars($clinicName !== '' ? $clinicName : 'Documento', ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Documento para assinatura</p>
    </div>
    <div class="body">
        <?php if ($alreadySigned): ?>
            <div class="success">
                <div class="success-icon">✅</div>
                <h2><?= $justSigned ? 'Documento assinado com sucesso!' : 'Documento já assinado' ?></h2>
                <p><strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong></p>
                <?php if ($signedAt !== ''): ?>
                    <p style="margin-top:8px;">Assinado em <?= date('d/m/Y \à\s H:i', strtotime($signedAt)) ?></p>
                <?php endif; ?>
                <p style="margin-top:12px;font-size:12px;color:#9ca3af;">Você pode fechar esta página.</p>
            </div>
        <?php else: ?>
            <div class="meta">
                <div><strong>Documento:</strong> <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
                <div><strong>Paciente:</strong> <?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <?php if ($body !== ''): ?>
                <div class="doc-body"><?= nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) ?></div>
            <?php endif; ?>

            <?php if (trim((string)($row['file_name'] ?? '')) !== ''): ?>
                <div style="padding:12px 16px;border-radius:10px;background:rgba(99,102,241,.06);border:1px solid rgba(99,102,241,.15);margin-bottom:20px;font-size:13px;">
                    📎 Arquivo anexo: <strong><?= htmlspecialchars((string)$row['file_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <div style="font-size:11px;color:#6b7280;margin-top:2px;">O arquivo foi disponibilizado pela clínica junto com este documento.</div>
                </div>
            <?php endif; ?>

            <form method="post" action="/doc/sign/submit" id="signForm">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="signature_data" id="signatureData" value="" />

                <div class="sig-area">
                    <div class="sig-label">Sua assinatura</div>
                    <canvas id="sigCanvas" class="sig-canvas" width="600" height="180"></canvas>
                    <div class="sig-actions">
                        <button type="button" class="btn btn-secondary" onclick="clearSig()" style="padding:8px 16px;font-size:12px;">Limpar</button>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                    <input type="checkbox" id="agreeCheck" required />
                    <label for="agreeCheck" style="font-size:13px;color:#374151;">Li e concordo com o documento acima.</label>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;" id="btnSubmit">Assinar documento</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!$alreadySigned): ?>
<script>
(function(){
    var canvas = document.getElementById('sigCanvas');
    var ctx = canvas.getContext('2d');
    var drawing = false;
    var hasDrawn = false;

    function getPos(e) {
        var r = canvas.getBoundingClientRect();
        var t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - r.left, y: t.clientY - r.top };
    }

    canvas.addEventListener('pointerdown', function(e) {
        drawing = true;
        var p = getPos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        canvas.setPointerCapture(e.pointerId);
        e.preventDefault();
    });
    canvas.addEventListener('pointermove', function(e) {
        if (!drawing) return;
        var p = getPos(e);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1f2937';
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        hasDrawn = true;
        e.preventDefault();
    });
    canvas.addEventListener('pointerup', function() { drawing = false; });

    window.clearSig = function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasDrawn = false;
    };

    document.getElementById('signForm').addEventListener('submit', function(e) {
        if (!hasDrawn) {
            e.preventDefault();
            alert('Por favor, desenhe sua assinatura antes de enviar.');
            return;
        }
        document.getElementById('signatureData').value = canvas.toDataURL('image/png');
    });
})();
</script>
<?php endif; ?>
</body>
</html>
