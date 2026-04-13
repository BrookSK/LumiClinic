<?php
$title = 'Marcações';
$csrf  = $_SESSION['_csrf'] ?? '';
$image = $image ?? null;
$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) { if (in_array($p, $perms['deny'], true)) return false; return in_array($p, $perms['allow'], true); }
    return in_array($p, $perms, true);
};
$imageId   = (int)($image['id'] ?? 0);
$patientId = (int)($image['patient_id'] ?? 0);
$canUpload = $can('medical_images.upload');
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px;gap:10px;">
    <div>
        <div style="font-weight:800;font-size:18px;">Marcações na imagem</div>
        <div class="lc-muted" style="font-size:13px;margin-top:2px;"><?= $canUpload ? 'Clique e arraste sobre a imagem para criar uma marcação.' : 'Visualização das marcações.' ?></div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= $patientId ?>">Voltar</a>
        <a class="lc-btn lc-btn--secondary" href="/medical-images/file?id=<?= $imageId ?>" target="_blank">Ver original</a>
    </div>
</div>
<div class="lc-grid lc-gap-grid" style="grid-template-columns:1fr 280px;align-items:start;">
    <div class="lc-card" style="margin:0;overflow:hidden;">
        <div id="anno-wrap" style="position:relative;width:100%;background:#111;border-radius:10px;overflow:hidden;cursor:<?= $canUpload ? 'crosshair' : 'default' ?>;">
            <img id="anno-img" src="/medical-images/file?id=<?= $imageId ?>" alt="" style="display:block;width:100%;height:auto;max-height:75vh;object-fit:contain;" draggable="false" />
            <svg id="anno-svg" viewBox="0 0 1000 1000" preserveAspectRatio="none" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;">
                <defs>
                    <?php foreach (['e11d48','2563eb','16a34a','d97706','7c3aed','ffffff'] as $c): ?>
                    <marker id="ah-<?=$c?>" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto"><path d="M0,0 L0,6 L6,3 z" fill="#<?=$c?>"/></marker>
                    <?php endforeach; ?>
                </defs>
            </svg>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:12px;">
        <?php if ($canUpload): ?>
        <div class="lc-card" style="margin:0;">
            <div class="lc-card__header" style="font-weight:700;font-size:13px;">Nova marcação</div>
            <div class="lc-card__body">
                <div style="margin-bottom:10px;">
                    <div class="lc-label" style="margin-bottom:6px;">Ferramenta</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;">
                        <button type="button" id="tool-rect" class="t-btn t-active" onclick="setTool('rect')">▭ Retângulo</button>
                        <button type="button" id="tool-circle" class="t-btn" onclick="setTool('circle')">◯ Círculo</button>
                        <button type="button" id="tool-arrow" class="t-btn" onclick="setTool('arrow')">↗ Seta</button>
                        <button type="button" id="tool-straightline" class="t-btn" onclick="setTool('straightline')">— Linha</button>
                        <button type="button" id="tool-freehand" class="t-btn" onclick="setTool('freehand')">✏ Traço livre</button>
                        <button type="button" id="tool-dot" class="t-btn" onclick="setTool('dot')">● Ponto</button>
                        <button type="button" id="tool-text" class="t-btn" onclick="setTool('text')">T Texto</button>
                        <button type="button" id="tool-measure" class="t-btn" onclick="setTool('measure')">📏 Medida</button>
                    </div>
                </div>
                <style>.t-btn{font-size:11px;padding:5px 6px;border:1px solid rgba(0,0,0,.12);border-radius:6px;background:#fff;cursor:pointer;text-align:center;font-weight:600;color:#374151;transition:all .1s}.t-btn:hover{background:#f3f4f6}.t-active{background:rgba(99,102,241,.12)!important;border-color:rgba(99,102,241,.4);color:#4f46e5}</style>
                <div class="lc-field">
                    <label class="lc-label">Texto</label>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <input class="lc-input" type="text" id="note" placeholder="Ex: Área de aplicação..." maxlength="120" style="flex:1;" />
                        <button type="button" id="btn-mic" onclick="toggleAudioRec()" title="Gravar áudio" style="background:none;border:1px solid rgba(0,0,0,.12);border-radius:8px;padding:6px 8px;cursor:pointer;font-size:14px;">🎤</button>
                    </div>
                    <div id="audio-status" style="display:none;font-size:11px;margin-top:4px;"></div>
                </div>
                <div class="lc-field" style="margin-top:8px;">
                    <label class="lc-label">Cor</label>
                    <div class="lc-flex lc-gap-sm" style="flex-wrap:wrap;">
                        <?php foreach (['#e11d48'=>'Vermelho','#2563eb'=>'Azul','#16a34a'=>'Verde','#d97706'=>'Laranja','#7c3aed'=>'Roxo','#ffffff'=>'Branco'] as $hex => $nm): ?>
                        <button type="button" class="color-btn" data-color="<?=$hex?>" onclick="setColor('<?=$hex?>')" title="<?=$nm?>" style="width:24px;height:24px;border-radius:50%;background:<?=$hex?>;border:2px solid <?=$hex==='#ffffff'?'#ccc':'transparent'?>;cursor:pointer;padding:0;"></button>
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
            <div class="lc-card__header" style="font-weight:700;font-size:13px;">Marcações <span id="anno-count" class="lc-badge lc-badge--secondary" style="margin-left:6px;font-size:11px;">0</span></div>
            <div class="lc-card__body" style="padding:0;"><div id="anno-list" style="max-height:300px;overflow-y:auto;"><div class="lc-muted" style="padding:12px;font-size:13px;">Carregando...</div></div></div>
        </div>
    </div>
</div>

<script>
(function(){
var imageId=<?=(int)$imageId?>,csrf=<?=json_encode((string)$csrf)?>,canUpload=<?=json_encode((bool)$canUpload)?>;
var img=document.getElementById('anno-img'),wrap=document.getElementById('anno-wrap'),svg=document.getElementById('anno-svg');
var noteEl=document.getElementById('note'),noteHid=document.getElementById('note_hidden'),payload=document.getElementById('payload_json');
var btnSave=document.getElementById('btn-save'),listEl=document.getElementById('anno-list'),countEl=document.getElementById('anno-count');
if(!img||!wrap||!svg)return;

var V=1000; // viewBox size
var tool='rect',color='#e11d48',drawing=false,startPt=null,previewEl=null,freePoints=[];
var TOOLS=['rect','circle','arrow','straightline','freehand','dot','text','measure'];

// Normalize pointer to 0-1000 viewBox coords
function norm(e){var r=img.getBoundingClientRect();return{x:Math.max(0,Math.min(V,((e.clientX-r.left)/r.width)*V)),y:Math.max(0,Math.min(V,((e.clientY-r.top)/r.height)*V))};}
function ns(t){return document.createElementNS('http://www.w3.org/2000/svg',t);}
function mid(a,b){return(a+b)/2;}

window.setTool=function(t){tool=t;TOOLS.forEach(function(id){var b=document.getElementById('tool-'+id);if(b)b.className=id===t?'t-btn t-active':'t-btn';});};
window.setColor=function(c){color=c;document.querySelectorAll('.color-btn').forEach(function(b){var bc=b.getAttribute('data-color');b.style.border=bc===c?'2px solid #111':'2px solid '+(bc==='#ffffff'?'#ccc':'transparent');});};
setColor('#e11d48');

// ── Drawing functions (all use 0-1000 coords) ──
function drawRect(x,y,w,h,c,label,id){
    var g=ns('g');g.setAttribute('data-id',id||'');
    var r=ns('rect');r.setAttribute('x',x);r.setAttribute('y',y);r.setAttribute('width',w);r.setAttribute('height',h);
    r.setAttribute('fill',c+'22');r.setAttribute('stroke',c);r.setAttribute('stroke-width','3');r.setAttribute('rx','4');g.appendChild(r);
    if(label)addLabel(g,x,y-22,label,c);
    svg.appendChild(g);return g;
}
function drawCircle(cx,cy,rx,ry,c,label,id){
    var g=ns('g');g.setAttribute('data-id',id||'');
    var el=ns('ellipse');el.setAttribute('cx',cx);el.setAttribute('cy',cy);el.setAttribute('rx',rx);el.setAttribute('ry',ry);
    el.setAttribute('fill',c+'18');el.setAttribute('stroke',c);el.setAttribute('stroke-width','3');g.appendChild(el);
    if(label)addLabel(g,cx-rx,cy-ry-22,label,c);
    svg.appendChild(g);return g;
}
function drawArrow(x1,y1,x2,y2,c,label,id){
    var g=ns('g');g.setAttribute('data-id',id||'');
    var cid='ah-'+c.replace('#','');
    var l=ns('line');l.setAttribute('x1',x1);l.setAttribute('y1',y1);l.setAttribute('x2',x2);l.setAttribute('y2',y2);
    l.setAttribute('stroke',c);l.setAttribute('stroke-width','3');l.setAttribute('marker-end','url(#'+cid+')');g.appendChild(l);
    if(label){var t=ns('text');t.setAttribute('x',x2);t.setAttribute('y',y2-8);t.setAttribute('fill',c);t.setAttribute('font-size','14');t.setAttribute('font-weight','600');t.setAttribute('font-family','system-ui,sans-serif');t.textContent=label;g.appendChild(t);}
    svg.appendChild(g);return g;
}
function drawLine(x1,y1,x2,y2,c,label,id){
    var g=ns('g');g.setAttribute('data-id',id||'');
    var l=ns('line');l.setAttribute('x1',x1);l.setAttribute('y1',y1);l.setAttribute('x2',x2);l.setAttribute('y2',y2);
    l.setAttribute('stroke',c);l.setAttribute('stroke-width','3');l.setAttribute('stroke-linecap','round');g.appendChild(l);
    if(label){var t=ns('text');t.setAttribute('x',mid(x1,x2));t.setAttribute('y',mid(y1,y2)-8);t.setAttribute('fill',c);t.setAttribute('font-size','13');t.setAttribute('font-weight','600');t.setAttribute('font-family','system-ui,sans-serif');t.setAttribute('text-anchor','middle');t.textContent=label;g.appendChild(t);}
    svg.appendChild(g);return g;
}
function drawFreehand(pts,c,label,id){
    if(!pts||pts.length<2)return null;
    var g=ns('g');g.setAttribute('data-id',id||'');
    var d='M'+pts[0].x+' '+pts[0].y;
    for(var i=1;i<pts.length;i++)d+=' L'+pts[i].x+' '+pts[i].y;
    var p=ns('path');p.setAttribute('d',d);p.setAttribute('fill','none');p.setAttribute('stroke',c);p.setAttribute('stroke-width','3');
    p.setAttribute('stroke-linecap','round');p.setAttribute('stroke-linejoin','round');g.appendChild(p);
    if(label){var t=ns('text');t.setAttribute('x',pts[0].x);t.setAttribute('y',pts[0].y-8);t.setAttribute('fill',c);t.setAttribute('font-size','13');t.setAttribute('font-weight','600');t.setAttribute('font-family','system-ui,sans-serif');t.textContent=label;g.appendChild(t);}
    svg.appendChild(g);return g;
}
function drawDot(cx,cy,c,label,id){
    var g=ns('g');g.setAttribute('data-id',id||'');
    var ci=ns('circle');ci.setAttribute('cx',cx);ci.setAttribute('cy',cy);ci.setAttribute('r','8');ci.setAttribute('fill',c);ci.setAttribute('stroke','#fff');ci.setAttribute('stroke-width','2');g.appendChild(ci);
    if(label){var t=ns('text');t.setAttribute('x',cx+14);t.setAttribute('y',cy+5);t.setAttribute('fill',c);t.setAttribute('font-size','14');t.setAttribute('font-weight','700');t.setAttribute('font-family','system-ui,sans-serif');t.textContent=label;g.appendChild(t);}
    svg.appendChild(g);return g;
}
function drawText(x,y,c,label,id){
    var g=ns('g');g.setAttribute('data-id',id||'');
    if(label){var t=ns('text');t.setAttribute('x',x);t.setAttribute('y',y);t.setAttribute('fill',c);t.setAttribute('font-size','20');t.setAttribute('font-weight','700');t.setAttribute('font-family','system-ui,sans-serif');t.textContent=label;g.appendChild(t);}
    svg.appendChild(g);return g;
}
function drawMeasure(x1,y1,x2,y2,c,label,id){
    var g=ns('g');g.setAttribute('data-id',id||'');
    // Line with end ticks
    var l=ns('line');l.setAttribute('x1',x1);l.setAttribute('y1',y1);l.setAttribute('x2',x2);l.setAttribute('y2',y2);
    l.setAttribute('stroke',c);l.setAttribute('stroke-width','2');l.setAttribute('stroke-dasharray','6,4');g.appendChild(l);
    // End ticks
    var dx=x2-x1,dy=y2-y1,len=Math.sqrt(dx*dx+dy*dy);if(len<1)len=1;
    var nx=-dy/len*10,ny=dx/len*10;
    [[x1,y1],[x2,y2]].forEach(function(p){var tk=ns('line');tk.setAttribute('x1',p[0]-nx);tk.setAttribute('y1',p[1]-ny);tk.setAttribute('x2',p[0]+nx);tk.setAttribute('y2',p[1]+ny);tk.setAttribute('stroke',c);tk.setAttribute('stroke-width','2');g.appendChild(tk);});
    // Label
    var pxLen=Math.round(len);
    var txt=label||(pxLen+'px');
    var t=ns('text');t.setAttribute('x',mid(x1,x2));t.setAttribute('y',mid(y1,y2)-10);t.setAttribute('fill',c);t.setAttribute('font-size','13');t.setAttribute('font-weight','700');t.setAttribute('font-family','system-ui,sans-serif');t.setAttribute('text-anchor','middle');
    t.setAttribute('paint-order','stroke');t.setAttribute('stroke','#000');t.setAttribute('stroke-width','3');t.textContent=txt;g.appendChild(t);
    svg.appendChild(g);return g;
}
function addLabel(g,x,y,label,c){
    var bg=ns('rect');var tw=Math.min(label.length*9+12,400);
    bg.setAttribute('x',x);bg.setAttribute('y',y);bg.setAttribute('width',tw);bg.setAttribute('height','20');bg.setAttribute('fill',c);bg.setAttribute('rx','3');g.appendChild(bg);
    var t=ns('text');t.setAttribute('x',x+6);t.setAttribute('y',y+15);t.setAttribute('fill','#fff');t.setAttribute('font-size','13');t.setAttribute('font-weight','600');t.setAttribute('font-family','system-ui,sans-serif');t.textContent=label;g.appendChild(t);
}

// ── Render from payload ──
function renderOne(p,label,id){
    var c=p.color||'#e11d48';
    if(p.type==='rect')return drawRect(p.x,p.y,p.w,p.h,c,label,id);
    if(p.type==='circle')return drawCircle(p.cx,p.cy,p.rx,p.ry,c,label,id);
    if(p.type==='arrow')return drawArrow(p.x1,p.y1,p.x2,p.y2,c,label,id);
    if(p.type==='straightline')return drawLine(p.x1,p.y1,p.x2,p.y2,c,label,id);
    if(p.type==='freehand')return drawFreehand(p.points,c,label,id);
    if(p.type==='dot')return drawDot(p.cx,p.cy,c,label,id);
    if(p.type==='text')return drawText(p.x,p.y,c,label||p.text||'',id);
    if(p.type==='measure')return drawMeasure(p.x1,p.y1,p.x2,p.y2,c,label,id);
    // Legacy rect format
    if(p.rect)return drawRect(p.rect.x*V,p.rect.y*V,p.rect.w*V,p.rect.h*V,c,label,id);
    return null;
}

// ── Interaction ──
if(canUpload){
    wrap.addEventListener('pointerdown',function(e){
        if(e.target!==img&&e.target!==svg&&!svg.contains(e.target))return;
        var pt=norm(e);
        if(tool==='dot'){
            var obj={type:'dot',color:color,cx:pt.x,cy:pt.y};
            if(payload)payload.value=JSON.stringify(obj);
            if(noteHid&&noteEl)noteHid.value=noteEl.value;
            if(previewEl){try{svg.removeChild(previewEl);}catch(x){}}
            previewEl=drawDot(pt.x,pt.y,color,noteEl?noteEl.value:'','');
            if(btnSave)btnSave.disabled=false;
            e.preventDefault();return;
        }
        if(tool==='text'){
            var txt=noteEl?noteEl.value:'';
            if(!txt){alert('Digite o texto antes de posicionar.');return;}
            var obj={type:'text',color:color,x:pt.x,y:pt.y,text:txt};
            if(payload)payload.value=JSON.stringify(obj);
            if(noteHid)noteHid.value=txt;
            if(previewEl){try{svg.removeChild(previewEl);}catch(x){}}
            previewEl=drawText(pt.x,pt.y,color,txt,'');
            if(btnSave)btnSave.disabled=false;
            e.preventDefault();return;
        }
        drawing=true;startPt=pt;freePoints=[pt];
        try{wrap.setPointerCapture(e.pointerId);}catch(x){}
        e.preventDefault();
    });
    wrap.addEventListener('pointermove',function(e){
        if(!drawing||!startPt)return;
        var cur=norm(e);
        if(previewEl){try{svg.removeChild(previewEl);}catch(x){}previewEl=null;}
        if(tool==='rect'){previewEl=drawRect(Math.min(startPt.x,cur.x),Math.min(startPt.y,cur.y),Math.abs(cur.x-startPt.x),Math.abs(cur.y-startPt.y),color,'','');}
        else if(tool==='circle'){previewEl=drawCircle(mid(startPt.x,cur.x),mid(startPt.y,cur.y),Math.abs(cur.x-startPt.x)/2,Math.abs(cur.y-startPt.y)/2,color,'','');}
        else if(tool==='arrow'){previewEl=drawArrow(startPt.x,startPt.y,cur.x,cur.y,color,'','');}
        else if(tool==='straightline'){previewEl=drawLine(startPt.x,startPt.y,cur.x,cur.y,color,'','');}
        else if(tool==='freehand'){freePoints.push(cur);previewEl=drawFreehand(freePoints,color,'','');}
        else if(tool==='measure'){previewEl=drawMeasure(startPt.x,startPt.y,cur.x,cur.y,color,'','');}
        e.preventDefault();
    });
    wrap.addEventListener('pointerup',function(e){
        if(!drawing||!startPt)return;
        drawing=false;var cur=norm(e);var obj;
        if(tool==='rect'){obj={type:'rect',color:color,x:Math.min(startPt.x,cur.x),y:Math.min(startPt.y,cur.y),w:Math.abs(cur.x-startPt.x),h:Math.abs(cur.y-startPt.y)};}
        else if(tool==='circle'){obj={type:'circle',color:color,cx:mid(startPt.x,cur.x),cy:mid(startPt.y,cur.y),rx:Math.abs(cur.x-startPt.x)/2,ry:Math.abs(cur.y-startPt.y)/2};}
        else if(tool==='arrow'){obj={type:'arrow',color:color,x1:startPt.x,y1:startPt.y,x2:cur.x,y2:cur.y};}
        else if(tool==='straightline'){obj={type:'straightline',color:color,x1:startPt.x,y1:startPt.y,x2:cur.x,y2:cur.y};}
        else if(tool==='freehand'){freePoints.push(cur);obj={type:'freehand',color:color,points:freePoints};}
        else if(tool==='measure'){obj={type:'measure',color:color,x1:startPt.x,y1:startPt.y,x2:cur.x,y2:cur.y};}
        if(payload)payload.value=JSON.stringify(obj);
        if(noteHid&&noteEl)noteHid.value=noteEl.value;
        if(btnSave)btnSave.disabled=false;
        startPt=null;freePoints=[];
    });
}

// ── Audio ──
var aRec=null,aChunks=[],aRecording=false;
window.toggleAudioRec=function(){aRecording?stopAR():startAR();};
function startAR(){
    var st=document.getElementById('audio-status'),btn=document.getElementById('btn-mic');
    navigator.mediaDevices.getUserMedia({audio:true}).then(function(stream){
        aChunks=[];aRec=new MediaRecorder(stream);
        aRec.ondataavailable=function(e){if(e.data&&e.data.size)aChunks.push(e.data);};
        aRec.onstop=function(){stream.getTracks().forEach(function(t){t.stop();});
            if(!aChunks.length){if(st)st.style.display='none';return;}
            var blob=new Blob(aChunks,{type:aChunks[0].type||'audio/webm'});
            if(st){st.textContent='Transcrevendo...';st.style.display='block';st.style.color='#2563eb';}
            var fd=new FormData();fd.append('_csrf',csrf);fd.append('patient_id',String(<?=$patientId?>));fd.append('audio',new File([blob],'r.webm',{type:blob.type}));
            fetch('/medical-records/audio/transcribe-json',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
                if(j&&j.ok&&j.transcript&&noteEl){noteEl.value=(noteEl.value?noteEl.value+' ':'')+j.transcript;if(st){st.textContent='✓ Transcrito';st.style.color='#16a34a';}}
                else{if(st){st.textContent=(j&&j.error)?j.error:'Falha';st.style.color='#b91c1c';}}
            }).catch(function(){if(st){st.textContent='Erro';st.style.color='#b91c1c';}});
        };
        aRec.start();aRecording=true;if(btn)btn.style.background='#fee2e2';
        if(st){st.textContent='🔴 Gravando...';st.style.display='block';st.style.color='#b91c1c';}
    }).catch(function(){if(st){st.textContent='Sem microfone';st.style.display='block';st.style.color='#b91c1c';}});
}
function stopAR(){aRecording=false;var btn=document.getElementById('btn-mic');if(btn)btn.style.background='';if(aRec&&aRec.state!=='inactive')aRec.stop();}

// ── Load annotations ──
var toolLabels={rect:'Retângulo',circle:'Círculo',arrow:'Seta',straightline:'Linha',freehand:'Traço',dot:'Ponto',text:'Texto',measure:'Medida'};
function renderAll(items){
    Array.from(svg.querySelectorAll('g')).forEach(function(n){svg.removeChild(n);});
    listEl.innerHTML='';
    if(!items.length){listEl.innerHTML='<div class="lc-muted" style="padding:12px;font-size:13px;">Nenhuma marcação.</div>';if(countEl)countEl.textContent='0';return;}
    if(countEl)countEl.textContent=String(items.length);
    items.forEach(function(it){
        var p={};try{p=JSON.parse(it.payload_json||'{}');}catch(e){}
        renderOne(p,it.note||'',it.id);
        var c=p.color||'#e11d48';
        var div=document.createElement('div');div.style.cssText='display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border-bottom:1px solid rgba(0,0,0,.06);gap:8px;';
        div.innerHTML='<div style="display:flex;align-items:center;gap:8px;min-width:0;"><span style="width:10px;height:10px;border-radius:50%;background:'+c+';flex-shrink:0;"></span><span style="font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+esc(it.note||(toolLabels[p.type]||'Marcação'))+'</span></div>'
            +(canUpload?'<form method="post" action="/medical-images/annotations/delete" style="flex-shrink:0;"><input type="hidden" name="_csrf" value="'+esc(csrf)+'"/><input type="hidden" name="image_id" value="'+imageId+'"/><input type="hidden" name="id" value="'+it.id+'"/><button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px;padding:2px 8px;">✕</button></form>':'');
        listEl.appendChild(div);
    });
}
function esc(s){return String(s||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
fetch('/medical-images/annotations.json?image_id='+imageId,{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){renderAll((d&&d.items)?d.items:[]);}).catch(function(){listEl.innerHTML='<div class="lc-muted" style="padding:12px;">Erro.</div>';});
})();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
