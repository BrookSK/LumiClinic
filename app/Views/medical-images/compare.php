<?php
$title = 'Comparar imagens';
$patient  = $patient ?? null;
$beforeId = (int)($before_id ?? 0);
$afterId  = (int)($after_id ?? 0);

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) {
        if (in_array($p, $perms['deny'], true)) return false;
        return in_array($p, $perms['allow'], true);
    }
    return in_array($p, $perms, true);
};

$patientId = (int)($patient['id'] ?? 0);
ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">Comparação Antes / Depois</div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= $patientId ?>">Voltar</a>
        <?php if ($can('files.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/medical-images/annotate?id=<?= $beforeId ?>">Marcar Antes</a>
            <a class="lc-btn lc-btn--secondary" href="/medical-images/annotate?id=<?= $afterId ?>">Marcar Depois</a>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__body" style="padding:16px;">

        <!-- Comparador -->
        <div id="ba-wrap" style="
            position: relative;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 12px;
            background: #111;
            cursor: ew-resize;
            user-select: none;
            touch-action: none;
        ">
            <!-- DEPOIS (base, sempre visível inteira) -->
            <img id="img-after"
                src="/medical-images/file?id=<?= $afterId ?>"
                alt="Depois"
                style="display:block; width:100%; height:auto; max-height:70vh; object-fit:contain;"
                draggable="false"
            />

            <!-- ANTES (por cima, clipada pela esquerda) -->
            <img id="img-before"
                src="/medical-images/file?id=<?= $beforeId ?>"
                alt="Antes"
                style="
                    position: absolute;
                    inset: 0;
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                    clip-path: inset(0 50% 0 0);
                    will-change: clip-path;
                "
                draggable="false"
            />

            <!-- Linha divisória -->
            <div id="ba-line" style="
                position: absolute;
                top: 0; bottom: 0;
                left: 50%;
                width: 3px;
                background: #fff;
                box-shadow: 0 0 0 1px rgba(0,0,0,.3);
                transform: translateX(-50%);
                will-change: left;
                pointer-events: none;
            "></div>

            <!-- Handle (círculo arrastável) -->
            <div id="ba-handle" style="
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: #fff;
                box-shadow: 0 4px 16px rgba(0,0,0,.35);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
                font-weight: 800;
                color: #374151;
                cursor: ew-resize;
                will-change: left;
                pointer-events: none;
            ">⇔</div>

            <!-- Labels -->
            <div style="position:absolute; bottom:12px; left:14px; background:rgba(0,0,0,.55); color:#fff; font-size:12px; font-weight:700; padding:3px 10px; border-radius:6px; pointer-events:none;">ANTES</div>
            <div style="position:absolute; bottom:12px; right:14px; background:rgba(0,0,0,.55); color:#fff; font-size:12px; font-weight:700; padding:3px 10px; border-radius:6px; pointer-events:none;">DEPOIS</div>
        </div>

        <div class="lc-muted" style="text-align:center; font-size:12px; margin-top:10px;">
            Arraste para comparar Antes e Depois
        </div>
    </div>
</div>

<script>
(function(){
    var wrap   = document.getElementById('ba-wrap');
    var before = document.getElementById('img-before');
    var line   = document.getElementById('ba-line');
    var handle = document.getElementById('ba-handle');
    if (!wrap || !before || !line || !handle) return;

    var pct = 50;

    function clamp(n, lo, hi){ return Math.max(lo, Math.min(hi, n)); }

    function apply(p) {
        pct = clamp(p, 0, 100);
        var right = (100 - pct).toFixed(3);
        before.style.clipPath = 'inset(0 ' + right + '% 0 0)';
        line.style.left   = pct + '%';
        handle.style.left = pct + '%';
    }

    function pctFromX(clientX) {
        var rect = wrap.getBoundingClientRect();
        return ((clientX - rect.left) / rect.width) * 100;
    }

    var dragging = false;

    wrap.addEventListener('pointerdown', function(e){
        dragging = true;
        try { wrap.setPointerCapture(e.pointerId); } catch(err){}
        apply(pctFromX(e.clientX));
        e.preventDefault();
    });

    wrap.addEventListener('pointermove', function(e){
        if (!dragging) return;
        apply(pctFromX(e.clientX));
        e.preventDefault();
    });

    wrap.addEventListener('pointerup',     function(){ dragging = false; });
    wrap.addEventListener('pointercancel', function(){ dragging = false; });

    // Touch fallback
    wrap.addEventListener('touchstart', function(e){
        apply(pctFromX(e.touches[0].clientX));
        e.preventDefault();
    }, { passive: false });

    wrap.addEventListener('touchmove', function(e){
        apply(pctFromX(e.touches[0].clientX));
        e.preventDefault();
    }, { passive: false });

    apply(50);
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
