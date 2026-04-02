<?php
$title = 'Marcações';
$csrf  = $_SESSION['_csrf'] ?? '';
$image = $image ?? null;

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

$imageId  = (int)($image['id'] ?? 0);
$patientId = (int)($image['patient_id'] ?? 0);
$canUpload = $can('medical_images.upload');

ob_start();
?>

<!-- Cabeçalho -->
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;">Marcações na imagem</div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;">
            <?php if ($canUpload): ?>Clique e arraste sobre a imagem para criar uma marcação.<?php else: ?>Visualização das marcações.<?php endif; ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= $patientId ?>">Voltar</a>
        <a class="lc-btn lc-btn--secondary" href="/medical-images/file?id=<?= $imageId ?>" target="_blank">Ver original</a>
    </div>
</div>

<div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 280px; align-items:start;">

    <!-- Imagem com canvas de anotação -->
    <div class="lc-card" style="margin:0; overflow:hidden;">
        <div id="anno-wrap" style="position:relative; width:100%; background:#111; border-radius:10px; overflow:hidden; cursor:<?= $canUpload ? 'crosshair' : 'default' ?>;">
            <img id="anno-img"
                src="/medical-images/file?id=<?= $imageId ?>"
                alt="Imagem"
                style="display:block; width:100%; height:auto; max-height:75vh; object-fit:contain;"
                draggable="false"
            />
            <!-- Layer de anotações (SVG) -->
            <svg id="anno-svg" style="position:absolute; inset:0; width:100%; height:100%; overflow:visible; pointer-events:none;"></svg>
        </div>
    </div>

    <!-- Painel lateral -->
    <div style="display:flex; flex-direction:column; gap:12px;">

        <!-- Ferramentas de desenho -->
        <?php if ($canUpload): ?>
        <div class="lc-card" style="margin:0;">
            <div class="lc-card__header" style="font-weight:700; font-size:13px;">Nova marcação</div>
            <div class="lc-card__body">
                <div style="margin-bottom:10px;">
                    <div class="lc-label" style="margin-bottom:6px;">Ferramenta</div>
                    <div class="lc-flex lc-gap-sm">
                        <button type="button" id="tool-rect" class="lc-btn lc-btn--primary lc-btn--sm" onclick="setTool('rect')" title="Retângulo">▭ Retângulo</button>
                        <button type="button" id="tool-arrow" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="setTool('arrow')" title="Seta">↗ Seta</button>
                    </div>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Texto da marcação</label>
                    <input class="lc-input" type="text" id="note" placeholder="Ex: Área de aplicação..." maxlength="120" />
                </div>

                <div class="lc-field" style="margin-top:8px;">
                    <label class="lc-label">Cor</label>
                    <div class="lc-flex lc-gap-sm" style="flex-wrap:wrap;">
                        <?php foreach (['#e11d48'=>'Vermelho','#2563eb'=>'Azul','#16a34a'=>'Verde','#d97706'=>'Laranja','#7c3aed'=>'Roxo'] as $hex => $name): ?>
                            <button type="button"
                                class="color-btn"
                                data-color="<?= $hex ?>"
                                onclick="setColor('<?= $hex ?>')"
                                title="<?= $name ?>"
                                style="width:24px; height:24px; border-radius:50%; background:<?= $hex ?>; border:2px solid transparent; cursor:pointer; padding:0;"
                            ></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form id="anno-form" method="post" action="/medical-images/annotations/create" style="margin-top:10px;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="image_id" value="<?= $imageId ?>" />
                    <input type="hidden" id="payload_json" name="payload_json" value="" />
                    <input type="hidden" id="note_hidden" name="note" value="" />
                    <button class="lc-btn lc-btn--primary" id="btn-save" type="submit" disabled style="width:100%;">Salvar marcação</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de marcações -->
        <div class="lc-card" style="margin:0;">
            <div class="lc-card__header" style="font-weight:700; font-size:13px;">
                Marcações
                <span id="anno-count" class="lc-badge lc-badge--secondary" style="margin-left:6px; font-size:11px;">0</span>
            </div>
            <div class="lc-card__body" style="padding:0;">
                <div id="anno-list" style="max-height:300px; overflow-y:auto;">
                    <div class="lc-muted" style="padding:12px; font-size:13px;">Carregando...</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
(function(){
    var imageId  = <?= (int)$imageId ?>;
    var csrf     = <?= json_encode((string)$csrf) ?>;
    var canUpload = <?= json_encode((bool)$canUpload) ?>;

    var img      = document.getElementById('anno-img');
    var wrap     = document.getElementById('anno-wrap');
    var svg      = document.getElementById('anno-svg');
    var noteEl   = document.getElementById('note');
    var noteHid  = document.getElementById('note_hidden');
    var payload  = document.getElementById('payload_json');
    var btnSave  = document.getElementById('btn-save');
    var listEl   = document.getElementById('anno-list');
    var countEl  = document.getElementById('anno-count');

    if (!img || !wrap || !svg) return;

    var currentTool  = 'rect';
    var currentColor = '#e11d48';
    var drawing = false;
    var startPt = null;
    var previewEl = null;

    // ── Ferramentas ──────────────────────────────────────────────
    window.setTool = function(t) {
        currentTool = t;
        ['rect','arrow'].forEach(function(id){
            var btn = document.getElementById('tool-' + id);
            if (!btn) return;
            btn.className = id === t ? 'lc-btn lc-btn--primary lc-btn--sm' : 'lc-btn lc-btn--secondary lc-btn--sm';
        });
    };

    window.setColor = function(c) {
        currentColor = c;
        document.querySelectorAll('.color-btn').forEach(function(b){
            b.style.border = b.getAttribute('data-color') === c
                ? '2px solid #111'
                : '2px solid transparent';
        });
    };
    setColor('#e11d48');

    // ── Coordenadas normalizadas ──────────────────────────────────
    function normPt(e) {
        var r = img.getBoundingClientRect();
        return {
            x: Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)),
            y: Math.max(0, Math.min(1, (e.clientY - r.top)  / r.height)),
        };
    }

    // ── SVG helpers ───────────────────────────────────────────────
    function svgNS(tag) { return document.createElementNS('http://www.w3.org/2000/svg', tag); }

    function pct(v) { return (v * 100).toFixed(3) + '%'; }

    function drawRect(r, color, label, id) {
        var g = svgNS('g');
        g.setAttribute('data-id', id || '');

        var rect = svgNS('rect');
        rect.setAttribute('x', pct(r.x));
        rect.setAttribute('y', pct(r.y));
        rect.setAttribute('width', pct(r.w));
        rect.setAttribute('height', pct(r.h));
        rect.setAttribute('fill', color + '22');
        rect.setAttribute('stroke', color);
        rect.setAttribute('stroke-width', '2');
        rect.setAttribute('rx', '4');
        g.appendChild(rect);

        if (label) {
            var text = svgNS('text');
            text.setAttribute('x', pct(r.x));
            text.setAttribute('y', pct(r.y));
            text.setAttribute('dy', '-6');
            text.setAttribute('fill', '#fff');
            text.setAttribute('font-size', '12');
            text.setAttribute('font-family', 'system-ui,sans-serif');
            text.setAttribute('font-weight', '600');

            var bg = svgNS('rect');
            bg.setAttribute('x', pct(r.x));
            bg.setAttribute('y', pct(r.y - 0.04));
            bg.setAttribute('width', pct(Math.min(label.length * 0.012 + 0.02, 0.5)));
            bg.setAttribute('height', pct(0.04));
            bg.setAttribute('fill', color);
            bg.setAttribute('rx', '3');
            g.appendChild(bg);

            text.textContent = label;
            g.appendChild(text);
        }
        svg.appendChild(g);
        return g;
    }

    function drawArrow(x1, y1, x2, y2, color, label, id) {
        var g = svgNS('g');
        g.setAttribute('data-id', id || '');

        var line = svgNS('line');
        line.setAttribute('x1', pct(x1)); line.setAttribute('y1', pct(y1));
        line.setAttribute('x2', pct(x2)); line.setAttribute('y2', pct(y2));
        line.setAttribute('stroke', color);
        line.setAttribute('stroke-width', '2.5');
        line.setAttribute('marker-end', 'url(#arrowhead-' + color.replace('#','') + ')');
        g.appendChild(line);

        if (label) {
            var text = svgNS('text');
            text.setAttribute('x', pct(x2));
            text.setAttribute('y', pct(y2));
            text.setAttribute('dy', '-8');
            text.setAttribute('fill', color);
            text.setAttribute('font-size', '12');
            text.setAttribute('font-family', 'system-ui,sans-serif');
            text.setAttribute('font-weight', '600');
            text.textContent = label;
            g.appendChild(text);
        }
        svg.appendChild(g);
        return g;
    }

    function ensureArrowMarker(color) {
        var id = 'arrowhead-' + color.replace('#','');
        if (svg.querySelector('#' + id)) return;
        var defs = svg.querySelector('defs') || svg.insertBefore(svgNS('defs'), svg.firstChild);
        var marker = svgNS('marker');
        marker.setAttribute('id', id);
        marker.setAttribute('markerWidth', '8');
        marker.setAttribute('markerHeight', '8');
        marker.setAttribute('refX', '6');
        marker.setAttribute('refY', '3');
        marker.setAttribute('orient', 'auto');
        var path = svgNS('path');
        path.setAttribute('d', 'M0,0 L0,6 L8,3 z');
        path.setAttribute('fill', color);
        marker.appendChild(path);
        defs.appendChild(marker);
    }

    // ── Desenho interativo ────────────────────────────────────────
    if (canUpload) {
        wrap.style.cursor = 'crosshair';

        wrap.addEventListener('pointerdown', function(e){
            if (e.target !== img && e.target !== svg && !svg.contains(e.target)) return;
            drawing = true;
            startPt = normPt(e);
            try { wrap.setPointerCapture(e.pointerId); } catch(err){}
            e.preventDefault();
        });

        wrap.addEventListener('pointermove', function(e){
            if (!drawing || !startPt) return;
            var cur = normPt(e);
            if (previewEl) { try { svg.removeChild(previewEl); } catch(err){} previewEl = null; }

            if (currentTool === 'rect') {
                var r = {
                    x: Math.min(startPt.x, cur.x),
                    y: Math.min(startPt.y, cur.y),
                    w: Math.abs(cur.x - startPt.x),
                    h: Math.abs(cur.y - startPt.y),
                };
                previewEl = drawRect(r, currentColor, '', '');
            } else {
                ensureArrowMarker(currentColor);
                previewEl = drawArrow(startPt.x, startPt.y, cur.x, cur.y, currentColor, '', '');
            }
            e.preventDefault();
        });

        wrap.addEventListener('pointerup', function(e){
            if (!drawing || !startPt) return;
            drawing = false;
            var cur = normPt(e);

            var payloadObj;
            if (currentTool === 'rect') {
                payloadObj = {
                    type: 'rect',
                    color: currentColor,
                    rect: {
                        x: Math.min(startPt.x, cur.x),
                        y: Math.min(startPt.y, cur.y),
                        w: Math.abs(cur.x - startPt.x),
                        h: Math.abs(cur.y - startPt.y),
                    }
                };
            } else {
                payloadObj = {
                    type: 'arrow',
                    color: currentColor,
                    x1: startPt.x, y1: startPt.y,
                    x2: cur.x, y2: cur.y,
                };
            }

            if (payload) payload.value = JSON.stringify(payloadObj);
            if (noteHid && noteEl) noteHid.value = noteEl.value;
            if (btnSave) btnSave.disabled = false;
            startPt = null;
        });
    }

    // ── Carregar anotações ────────────────────────────────────────
    function renderAnnotations(items) {
        // Limpar SVG (manter defs)
        Array.from(svg.childNodes).forEach(function(n){
            if (n.tagName !== 'defs') svg.removeChild(n);
        });
        listEl.innerHTML = '';

        if (!items.length) {
            listEl.innerHTML = '<div class="lc-muted" style="padding:12px; font-size:13px;">Nenhuma marcação.</div>';
            if (countEl) countEl.textContent = '0';
            return;
        }

        if (countEl) countEl.textContent = String(items.length);

        items.forEach(function(it){
            var p = {};
            try { p = JSON.parse(it.payload_json || '{}'); } catch(e){}
            var color = p.color || '#e11d48';
            var label = it.note || '';

            if (p.type === 'rect' && p.rect) {
                drawRect(p.rect, color, label, it.id);
            } else if (p.type === 'arrow') {
                ensureArrowMarker(color);
                drawArrow(p.x1||0, p.y1||0, p.x2||0, p.y2||0, color, label, it.id);
            }

            // Item na lista
            var div = document.createElement('div');
            div.style.cssText = 'display:flex; justify-content:space-between; align-items:center; padding:8px 12px; border-bottom:1px solid rgba(0,0,0,.06); gap:8px;';
            div.innerHTML = '<div style="display:flex; align-items:center; gap:8px; min-width:0;">'
                + '<span style="width:10px; height:10px; border-radius:50%; background:' + color + '; flex-shrink:0;"></span>'
                + '<span style="font-size:13px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + esc(label || (p.type === 'arrow' ? 'Seta' : 'Retângulo')) + '</span>'
                + '</div>'
                + (canUpload ? (
                    '<form method="post" action="/medical-images/annotations/delete" style="flex-shrink:0;">'
                    + '<input type="hidden" name="_csrf" value="' + esc(csrf) + '" />'
                    + '<input type="hidden" name="image_id" value="' + esc(String(imageId)) + '" />'
                    + '<input type="hidden" name="id" value="' + esc(String(it.id)) + '" />'
                    + '<button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px; padding:2px 8px;">✕</button>'
                    + '</form>'
                ) : '')
                + '</div>';
            listEl.appendChild(div);
        });
    }

    function esc(s) {
        return String(s||'').replace(/[&<>"']/g, function(c){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    fetch('/medical-images/annotations.json?image_id=' + encodeURIComponent(String(imageId)), { credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(data){ renderAnnotations((data && data.items) ? data.items : []); })
        .catch(function(){ listEl.innerHTML = '<div class="lc-muted" style="padding:12px;">Erro ao carregar.</div>'; });
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
