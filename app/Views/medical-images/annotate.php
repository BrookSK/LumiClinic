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

    <div class="lc-card" style="margin:0; overflow:hidden;">
        <div id="anno-wrap" style="position:relative; width:100%; background:#111; border-radius:10px; overflow:hidden; cursor:<?= $canUpload ? 'crosshair' : 'default' ?>;">
            <img id="anno-img" src="/medical-images/file?id=<?= $imageId ?>" alt="Imagem" style="display:block; width:100%; height:auto; max-height:75vh; object-fit:contain;" draggable="false" />
            <svg id="anno-svg" style="position:absolute; inset:0; width:100%; height:100%; overflow:visible; pointer-events:none;"></svg>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:12px;">

        <?php if ($canUpload): ?>
        <div class="lc-card" style="margin:0;">
            <div class="lc-card__header" style="font-weight:700; font-size:13px;">Nova marcação</div>
            <div class="lc-card__body">
                <div style="margin-bottom:10px;">
                    <div class="lc-label" style="margin-bottom:6px;">Ferramenta</div>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                        <button type="button" id="tool-rect" class="lc-btn lc-btn--primary lc-btn--sm" onclick="setTool('rect')" title="Retângulo" style="font-size:11px;padding:4px 8px;">▭ Retângulo</button>
                        <button type="button" id="tool-arrow" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="setTool('arrow')" title="Seta" style="font-size:11px;padding:4px 8px;">↗ Seta</button>
                        <button type="button" id="tool-circle" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="setTool('circle')" title="Círculo" style="font-size:11px;padding:4px 8px;">◯ Círculo</button>
                        <button type="button" id="tool-line" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="setTool('line')" title="Traço livre" style="font-size:11px;padding:4px 8px;">✏ Traço</button>
                    </div>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Texto da marcação</label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <input class="lc-input" type="text" id="note" placeholder="Ex: Área de aplicação..." maxlength="120" style="flex:1;" />
                        <button type="button" id="btn-mic" onclick="toggleAudioRec()" title="Gravar áudio" style="background:none;border:1px solid rgba(0,0,0,.12);border-radius:8px;padding:6px 8px;cursor:pointer;font-size:14px;">🎤</button>
                    </div>
                    <div id="audio-status" style="display:none;font-size:11px;margin-top:4px;color:#b91c1c;"></div>
                </div>

                <div class="lc-field" style="margin-top:8px;">
                    <label class="lc-label">Cor</label>
                    <div class="lc-flex lc-gap-sm" style="flex-wrap:wrap;">
                        <?php foreach (['#e11d48'=>'Vermelho','#2563eb'=>'Azul','#16a34a'=>'Verde','#d97706'=>'Laranja','#7c3aed'=>'Roxo','#ffffff'=>'Branco'] as $hex => $name): ?>
                            <button type="button" class="color-btn" data-color="<?= $hex ?>" onclick="setColor('<?= $hex ?>')" title="<?= $name ?>" style="width:24px; height:24px; border-radius:50%; background:<?= $hex ?>; border:2px solid <?= $hex === '#ffffff' ? '#ccc' : 'transparent' ?>; cursor:pointer; padding:0;"></button>
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

    var img = document.getElementById('anno-img');
    var wrap = document.getElementById('anno-wrap');
    var svg = document.getElementById('anno-svg');
    var noteEl = document.getElementById('note');
    var noteHid = document.getElementById('note_hidden');
    var payload = document.getElementById('payload_json');
    var btnSave = document.getElementById('btn-save');
    var listEl = document.getElementById('anno-list');
    var countEl = document.getElementById('anno-count');

    if (!img || !wrap || !svg) return;

    var currentTool = 'rect', currentColor = '#e11d48';
    var drawing = false, startPt = null, previewEl = null;
    var freehandPoints = [];

    var TOOLS = ['rect','arrow','circle','line'];
    window.setTool = function(t) {
        currentTool = t;
        TOOLS.forEach(function(id){
            var btn = document.getElementById('tool-' + id);
            if (btn) btn.className = id === t ? 'lc-btn lc-btn--primary lc-btn--sm' : 'lc-btn lc-btn--secondary lc-btn--sm';
        });
    };

    window.setColor = function(c) {
        currentColor = c;
        document.querySelectorAll('.color-btn').forEach(function(b){
            var bc = b.getAttribute('data-color');
            b.style.border = bc === c ? '2px solid #111' : ('2px solid ' + (bc === '#ffffff' ? '#ccc' : 'transparent'));
        });
    };
    setColor('#e11d48');

    function normPt(e) {
        var r = img.getBoundingClientRect();
        return { x: Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)), y: Math.max(0, Math.min(1, (e.clientY - r.top) / r.height)) };
    }

    function svgNS(tag) { return document.createElementNS('http://www.w3.org/2000/svg', tag); }
    function pct(v) { return (v * 100).toFixed(3) + '%'; }

    function drawRect(r, color, label, id) {
        var g = svgNS('g'); g.setAttribute('data-id', id || '');
        var rect = svgNS('rect');
        rect.setAttribute('x', pct(r.x)); rect.setAttribute('y', pct(r.y));
        rect.setAttribute('width', pct(r.w)); rect.setAttribute('height', pct(r.h));
        rect.setAttribute('fill', color + '22'); rect.setAttribute('stroke', color);
        rect.setAttribute('stroke-width', '2'); rect.setAttribute('rx', '4');
        g.appendChild(rect);
        if (label) addLabel(g, r.x, r.y, label, color);
        svg.appendChild(g); return g;
    }

    function drawCircle(cx, cy, rx, ry, color, label, id) {
        var g = svgNS('g'); g.setAttribute('data-id', id || '');
        var el = svgNS('ellipse');
        el.setAttribute('cx', pct(cx)); el.setAttribute('cy', pct(cy));
        el.setAttribute('rx', pct(rx)); el.setAttribute('ry', pct(ry));
        el.setAttribute('fill', color + '18'); el.setAttribute('stroke', color);
        el.setAttribute('stroke-width', '2');
        g.appendChild(el);
        if (label) addLabel(g, cx - rx, cy - ry, label, color);
        svg.appendChild(g); return g;
    }

    function drawArrow(x1, y1, x2, y2, color, label, id) {
        var g = svgNS('g'); g.setAttribute('data-id', id || '');
        ensureArrowMarker(color);
        var line = svgNS('line');
        line.setAttribute('x1', pct(x1)); line.setAttribute('y1', pct(y1));
        line.setAttribute('x2', pct(x2)); line.setAttribute('y2', pct(y2));
        line.setAttribute('stroke', color); line.setAttribute('stroke-width', '2');
        line.setAttribute('marker-end', 'url(#ah-' + color.replace('#','') + ')');
        g.appendChild(line);
        if (label) { var t = svgNS('text'); t.setAttribute('x', pct(x2)); t.setAttribute('y', pct(y2)); t.setAttribute('dy', '-6'); t.setAttribute('fill', color); t.setAttribute('font-size', '11'); t.setAttribute('font-family', 'system-ui,sans-serif'); t.setAttribute('font-weight', '600'); t.textContent = label; g.appendChild(t); }
        svg.appendChild(g); return g;
    }

    function drawFreehand(points, color, label, id) {
        if (!points || points.length < 2) return null;
        var g = svgNS('g'); g.setAttribute('data-id', id || '');
        var d = 'M' + points.map(function(p){ return pct(p.x) + ' ' + pct(p.y); }).join(' L');
        var path = svgNS('path');
        path.setAttribute('d', d); path.setAttribute('fill', 'none');
        path.setAttribute('stroke', color); path.setAttribute('stroke-width', '2.5');
        path.setAttribute('stroke-linecap', 'round'); path.setAttribute('stroke-linejoin', 'round');
        g.appendChild(path);
        if (label && points.length > 0) { var t = svgNS('text'); t.setAttribute('x', pct(points[0].x)); t.setAttribute('y', pct(points[0].y)); t.setAttribute('dy', '-6'); t.setAttribute('fill', color); t.setAttribute('font-size', '11'); t.setAttribute('font-family', 'system-ui,sans-serif'); t.setAttribute('font-weight', '600'); t.textContent = label; g.appendChild(t); }
        svg.appendChild(g); return g;
    }

    function addLabel(g, x, y, label, color) {
        var bg = svgNS('rect');
        bg.setAttribute('x', pct(x)); bg.setAttribute('y', pct(y - 0.035));
        bg.setAttribute('width', pct(Math.min(label.length * 0.011 + 0.02, 0.45)));
        bg.setAttribute('height', pct(0.035)); bg.setAttribute('fill', color); bg.setAttribute('rx', '3');
        g.appendChild(bg);
        var t = svgNS('text');
        t.setAttribute('x', pct(x + 0.005)); t.setAttribute('y', pct(y - 0.01));
        t.setAttribute('fill', '#fff'); t.setAttribute('font-size', '11');
        t.setAttribute('font-family', 'system-ui,sans-serif'); t.setAttribute('font-weight', '600');
        t.textContent = label; g.appendChild(t);
    }

    function ensureArrowMarker(color) {
        var id = 'ah-' + color.replace('#','');
        if (svg.querySelector('#' + id)) return;
        var defs = svg.querySelector('defs') || svg.insertBefore(svgNS('defs'), svg.firstChild);
        var m = svgNS('marker');
        m.setAttribute('id', id); m.setAttribute('markerWidth', '6'); m.setAttribute('markerHeight', '6');
        m.setAttribute('refX', '5'); m.setAttribute('refY', '3'); m.setAttribute('orient', 'auto');
        var p = svgNS('path'); p.setAttribute('d', 'M0,0 L0,6 L6,3 z'); p.setAttribute('fill', color);
        m.appendChild(p); defs.appendChild(m);
    }

    // Drawing interaction
    if (canUpload) {
        wrap.addEventListener('pointerdown', function(e){
            if (e.target !== img && e.target !== svg && !svg.contains(e.target)) return;
            drawing = true; startPt = normPt(e); freehandPoints = [startPt];
            try { wrap.setPointerCapture(e.pointerId); } catch(err){} e.preventDefault();
        });

        wrap.addEventListener('pointermove', function(e){
            if (!drawing || !startPt) return;
            var cur = normPt(e);
            if (previewEl) { try { svg.removeChild(previewEl); } catch(err){} previewEl = null; }

            if (currentTool === 'rect') {
                previewEl = drawRect({ x: Math.min(startPt.x, cur.x), y: Math.min(startPt.y, cur.y), w: Math.abs(cur.x - startPt.x), h: Math.abs(cur.y - startPt.y) }, currentColor, '', '');
            } else if (currentTool === 'arrow') {
                previewEl = drawArrow(startPt.x, startPt.y, cur.x, cur.y, currentColor, '', '');
            } else if (currentTool === 'circle') {
                var cx = (startPt.x + cur.x) / 2, cy = (startPt.y + cur.y) / 2;
                previewEl = drawCircle(cx, cy, Math.abs(cur.x - startPt.x) / 2, Math.abs(cur.y - startPt.y) / 2, currentColor, '', '');
            } else if (currentTool === 'line') {
                freehandPoints.push(cur);
                previewEl = drawFreehand(freehandPoints, currentColor, '', '');
            }
            e.preventDefault();
        });

        wrap.addEventListener('pointerup', function(e){
            if (!drawing || !startPt) return;
            drawing = false; var cur = normPt(e);
            var payloadObj;

            if (currentTool === 'rect') {
                payloadObj = { type: 'rect', color: currentColor, rect: { x: Math.min(startPt.x, cur.x), y: Math.min(startPt.y, cur.y), w: Math.abs(cur.x - startPt.x), h: Math.abs(cur.y - startPt.y) } };
            } else if (currentTool === 'arrow') {
                payloadObj = { type: 'arrow', color: currentColor, x1: startPt.x, y1: startPt.y, x2: cur.x, y2: cur.y };
            } else if (currentTool === 'circle') {
                payloadObj = { type: 'circle', color: currentColor, cx: (startPt.x + cur.x) / 2, cy: (startPt.y + cur.y) / 2, rx: Math.abs(cur.x - startPt.x) / 2, ry: Math.abs(cur.y - startPt.y) / 2 };
            } else if (currentTool === 'line') {
                freehandPoints.push(cur);
                payloadObj = { type: 'line', color: currentColor, points: freehandPoints };
            }

            if (payload) payload.value = JSON.stringify(payloadObj);
            if (noteHid && noteEl) noteHid.value = noteEl.value;
            if (btnSave) btnSave.disabled = false;
            startPt = null; freehandPoints = [];
        });
    }

    // Audio recording
    var audioRec = null, audioChunks = [], audioRecording = false;
    window.toggleAudioRec = function() {
        if (audioRecording) { stopAudioRec(); return; }
        startAudioRec();
    };

    function startAudioRec() {
        var statusEl = document.getElementById('audio-status');
        var btnMic = document.getElementById('btn-mic');
        navigator.mediaDevices.getUserMedia({audio:true}).then(function(stream){
            audioChunks = [];
            audioRec = new MediaRecorder(stream);
            audioRec.ondataavailable = function(e){ if(e.data && e.data.size) audioChunks.push(e.data); };
            audioRec.onstop = function(){
                stream.getTracks().forEach(function(t){ t.stop(); });
                if (!audioChunks.length) { if(statusEl) statusEl.style.display='none'; return; }
                var blob = new Blob(audioChunks, {type: audioChunks[0].type || 'audio/webm'});
                if(statusEl){ statusEl.textContent='Transcrevendo...'; statusEl.style.display='block'; statusEl.style.color='#2563eb'; }
                var fd = new FormData();
                fd.append('_csrf', csrf);
                fd.append('patient_id', String(<?= $patientId ?>));
                fd.append('audio', new File([blob], 'rec.webm', {type:blob.type}));
                fetch('/medical-records/audio/transcribe-json', {method:'POST', body:fd, credentials:'same-origin'})
                    .then(function(r){ return r.json(); })
                    .then(function(j){
                        if(j && j.ok && j.transcript && noteEl) {
                            noteEl.value = (noteEl.value ? noteEl.value + ' ' : '') + j.transcript;
                            if(statusEl){ statusEl.textContent='✓ Transcrito'; statusEl.style.color='#16a34a'; }
                        } else {
                            if(statusEl){ statusEl.textContent=(j&&j.error)?j.error:'Falha na transcrição'; statusEl.style.color='#b91c1c'; }
                        }
                    })
                    .catch(function(){ if(statusEl){ statusEl.textContent='Erro de conexão'; statusEl.style.color='#b91c1c'; } });
            };
            audioRec.start();
            audioRecording = true;
            if(btnMic) btnMic.style.background='#fee2e2';
            if(statusEl){ statusEl.textContent='🔴 Gravando... clique no 🎤 para parar'; statusEl.style.display='block'; statusEl.style.color='#b91c1c'; }
        }).catch(function(e){
            if(statusEl){ statusEl.textContent='Sem acesso ao microfone'; statusEl.style.display='block'; statusEl.style.color='#b91c1c'; }
        });
    }

    function stopAudioRec() {
        audioRecording = false;
        var btnMic = document.getElementById('btn-mic');
        if(btnMic) btnMic.style.background='';
        if(audioRec && audioRec.state !== 'inactive') audioRec.stop();
    }

    // Load annotations
    function renderAnnotations(items) {
        Array.from(svg.childNodes).forEach(function(n){ if (n.tagName !== 'defs') svg.removeChild(n); });
        listEl.innerHTML = '';
        if (!items.length) { listEl.innerHTML = '<div class="lc-muted" style="padding:12px; font-size:13px;">Nenhuma marcação.</div>'; if(countEl) countEl.textContent='0'; return; }
        if(countEl) countEl.textContent = String(items.length);

        var toolLabels = {rect:'Retângulo',arrow:'Seta',circle:'Círculo',line:'Traço'};
        items.forEach(function(it){
            var p = {}; try { p = JSON.parse(it.payload_json || '{}'); } catch(e){}
            var color = p.color || '#e11d48', label = it.note || '';

            if (p.type === 'rect' && p.rect) drawRect(p.rect, color, label, it.id);
            else if (p.type === 'arrow') { ensureArrowMarker(color); drawArrow(p.x1||0, p.y1||0, p.x2||0, p.y2||0, color, label, it.id); }
            else if (p.type === 'circle') drawCircle(p.cx||0, p.cy||0, p.rx||0, p.ry||0, color, label, it.id);
            else if (p.type === 'line' && p.points) drawFreehand(p.points, color, label, it.id);

            var div = document.createElement('div');
            div.style.cssText = 'display:flex; justify-content:space-between; align-items:center; padding:8px 12px; border-bottom:1px solid rgba(0,0,0,.06); gap:8px;';
            div.innerHTML = '<div style="display:flex;align-items:center;gap:8px;min-width:0;">'
                + '<span style="width:10px;height:10px;border-radius:50%;background:'+color+';flex-shrink:0;"></span>'
                + '<span style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'+esc(label || (toolLabels[p.type] || 'Marcação'))+'</span>'
                + '</div>'
                + (canUpload ? '<form method="post" action="/medical-images/annotations/delete" style="flex-shrink:0;"><input type="hidden" name="_csrf" value="'+esc(csrf)+'"/><input type="hidden" name="image_id" value="'+esc(String(imageId))+'"/><input type="hidden" name="id" value="'+esc(String(it.id))+'"/><button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px;padding:2px 8px;">✕</button></form>' : '');
            listEl.appendChild(div);
        });
    }

    function esc(s) { return String(s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

    fetch('/medical-images/annotations.json?image_id=' + encodeURIComponent(String(imageId)), { credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(data){ renderAnnotations((data && data.items) ? data.items : []); })
        .catch(function(){ listEl.innerHTML = '<div class="lc-muted" style="padding:12px;">Erro ao carregar.</div>'; });
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
