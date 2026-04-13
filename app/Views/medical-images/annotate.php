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
        <div class="lc-muted" style="font-size:13px;margin-top:2px;"><?= $canUpload ? 'Clique e arraste sobre a imagem.' : 'Visualização.' ?></div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <?php if ($canUpload): ?>
        <button type="button" id="btn-crop-toggle" class="lc-btn lc-btn--secondary" onclick="toggleCropMode()">✂ Recortar</button>
        <?php endif; ?>
        <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= $patientId ?>">Voltar</a>
        <a class="lc-btn lc-btn--secondary" href="/medical-images/file?id=<?= $imageId ?>" target="_blank">Ver original</a>
    </div>
</div>
<div class="lc-grid lc-gap-grid" style="grid-template-columns:1fr 280px;align-items:start;">
    <div class="lc-card" style="margin:0;overflow:hidden;">
        <div id="anno-wrap" style="position:relative;width:100%;background:#111;border-radius:10px;overflow:hidden;cursor:<?= $canUpload ? 'crosshair' : 'default' ?>;">
            <img id="anno-img" src="/medical-images/file?id=<?= $imageId ?>" alt="" style="display:block;width:100%;height:auto;max-height:75vh;object-fit:contain;" draggable="false" />
            <svg id="anno-svg" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;"></svg>
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

<!-- Crop overlay -->
<?php if ($canUpload): ?>
<div id="crop-bar" style="display:none;margin-top:10px;padding:12px;border-radius:10px;background:rgba(99,102,241,.06);border:1px solid rgba(99,102,241,.2);">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span style="font-size:13px;font-weight:600;color:#4f46e5;">✂ Modo recorte</span>
        <span class="lc-muted" style="font-size:12px;">Arraste sobre a imagem para selecionar a área de recorte.</span>
        <form id="crop-form" method="post" action="/medical-images/crop" style="margin:0;display:flex;gap:6px;margin-left:auto;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="image_id" value="<?= $imageId ?>" />
            <input type="hidden" id="crop-x" name="x" value="0" />
            <input type="hidden" id="crop-y" name="y" value="0" />
            <input type="hidden" id="crop-w" name="w" value="0" />
            <input type="hidden" id="crop-h" name="h" value="0" />
            <button type="submit" id="btn-crop-apply" class="lc-btn lc-btn--primary lc-btn--sm" disabled>Aplicar recorte</button>
            <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleCropMode()">Cancelar</button>
        </form>
    </div>
</div>
<div id="crop-overlay" style="display:none;position:absolute;border:2px dashed #4f46e5;background:rgba(99,102,241,.15);pointer-events:none;z-index:10;"></div>
<?php endif; ?>

<script>
(function(){
var imageId=<?=(int)$imageId?>,csrf=<?=json_encode((string)$csrf)?>,canUpload=<?=json_encode((bool)$canUpload)?>;
var img=document.getElementById('anno-img'),wrap=document.getElementById('anno-wrap'),svg=document.getElementById('anno-svg');
var noteEl=document.getElementById('note'),noteHid=document.getElementById('note_hidden'),payloadEl=document.getElementById('payload_json');
var btnSave=document.getElementById('btn-save'),listEl=document.getElementById('anno-list'),countEl=document.getElementById('anno-count');
if(!img||!wrap||!svg)return;

/* ── viewBox = real image pixels so nothing distorts ── */
var VW=1000,VH=1000;
function boot(){
    VW=img.naturalWidth||1000; VH=img.naturalHeight||1000;
    svg.setAttribute('viewBox','0 0 '+VW+' '+VH);
    loadAnnotations();
}
if(img.complete&&img.naturalWidth)boot(); else img.addEventListener('load',boot);

var tool='rect',color='#e11d48',drawing=false,sp=null,preview=null,fp=[];
var TOOLS=['rect','circle','arrow','straightline','freehand','dot','text','measure'];

function norm(e){var r=img.getBoundingClientRect();return{x:Math.round(Math.max(0,Math.min(VW,((e.clientX-r.left)/r.width)*VW))),y:Math.round(Math.max(0,Math.min(VH,((e.clientY-r.top)/r.height)*VH)))};}
function S(t){return document.createElementNS('http://www.w3.org/2000/svg',t);}
/* scale-aware sizes */
function sw(){return Math.max(2,Math.round(Math.min(VW,VH)*0.004));}
function fz(){return Math.max(12,Math.round(Math.min(VW,VH)*0.022));}
function dr(){return Math.max(5,Math.round(Math.min(VW,VH)*0.008));}

window.setTool=function(t){tool=t;TOOLS.forEach(function(id){var b=document.getElementById('tool-'+id);if(b)b.className=id===t?'t-btn t-active':'t-btn';});};
window.setColor=function(c){color=c;document.querySelectorAll('.color-btn').forEach(function(b){var bc=b.getAttribute('data-color');b.style.border=bc===c?'2px solid #111':'2px solid '+(bc==='#ffffff'?'#ccc':'transparent');});};
setColor('#e11d48');

/* ── ensure arrow markers ── */
function ensureMarker(c){
    var id='ah-'+c.replace('#','');if(svg.querySelector('#'+id))return;
    var defs=svg.querySelector('defs');if(!defs){defs=S('defs');svg.insertBefore(defs,svg.firstChild);}
    var m=S('marker');m.setAttribute('id',id);m.setAttribute('markerWidth','10');m.setAttribute('markerHeight','10');m.setAttribute('refX','9');m.setAttribute('refY','5');m.setAttribute('orient','auto');m.setAttribute('markerUnits','strokeWidth');
    var p=S('path');p.setAttribute('d','M0,1 L0,9 L9,5 z');p.setAttribute('fill',c);m.appendChild(p);defs.appendChild(m);
}

/* ── draw helpers ── */
function mkRect(x,y,w,h,c,lbl,id){
    var g=S('g');g.setAttribute('data-id',id||'');
    var r=S('rect');r.setAttribute('x',x);r.setAttribute('y',y);r.setAttribute('width',w);r.setAttribute('height',h);
    r.setAttribute('fill',c+'22');r.setAttribute('stroke',c);r.setAttribute('stroke-width',sw());r.setAttribute('rx','4');g.appendChild(r);
    if(lbl)mkLabel(g,x,y,lbl,c);svg.appendChild(g);return g;
}
function mkCircle(cx,cy,rx,ry,c,lbl,id){
    var g=S('g');g.setAttribute('data-id',id||'');
    var el=S('ellipse');el.setAttribute('cx',cx);el.setAttribute('cy',cy);el.setAttribute('rx',rx);el.setAttribute('ry',ry);
    el.setAttribute('fill',c+'18');el.setAttribute('stroke',c);el.setAttribute('stroke-width',sw());g.appendChild(el);
    if(lbl)mkLabel(g,cx-rx,cy-ry,lbl,c);svg.appendChild(g);return g;
}
function mkArrow(x1,y1,x2,y2,c,lbl,id){
    ensureMarker(c);var g=S('g');g.setAttribute('data-id',id||'');
    var l=S('line');l.setAttribute('x1',x1);l.setAttribute('y1',y1);l.setAttribute('x2',x2);l.setAttribute('y2',y2);
    l.setAttribute('stroke',c);l.setAttribute('stroke-width',sw());l.setAttribute('marker-end','url(#ah-'+c.replace('#','')+')');g.appendChild(l);
    if(lbl)mkFloat(g,x2,y2-fz()*2.2,lbl,c);svg.appendChild(g);return g;
}
function mkLine(x1,y1,x2,y2,c,lbl,id){
    var g=S('g');g.setAttribute('data-id',id||'');
    var l=S('line');l.setAttribute('x1',x1);l.setAttribute('y1',y1);l.setAttribute('x2',x2);l.setAttribute('y2',y2);
    l.setAttribute('stroke',c);l.setAttribute('stroke-width',sw());l.setAttribute('stroke-linecap','round');g.appendChild(l);
    if(lbl)mkFloat(g,(+x1+ +x2)/2,(+y1+ +y2)/2-fz()*1.8,lbl,c);svg.appendChild(g);return g;
}
function mkFree(pts,c,lbl,id){
    if(!pts||pts.length<2)return null;var g=S('g');g.setAttribute('data-id',id||'');
    var d='M'+pts[0].x+','+pts[0].y;for(var i=1;i<pts.length;i++)d+='L'+pts[i].x+','+pts[i].y;
    var p=S('path');p.setAttribute('d',d);p.setAttribute('fill','none');p.setAttribute('stroke',c);p.setAttribute('stroke-width',sw());
    p.setAttribute('stroke-linecap','round');p.setAttribute('stroke-linejoin','round');g.appendChild(p);
    if(lbl)mkFloat(g,pts[0].x,pts[0].y-fz()*2,lbl,c);svg.appendChild(g);return g;
}
function mkDot(cx,cy,c,lbl,id){
    var g=S('g');g.setAttribute('data-id',id||'');
    var ci=S('circle');ci.setAttribute('cx',cx);ci.setAttribute('cy',cy);ci.setAttribute('r',dr());ci.setAttribute('fill',c);ci.setAttribute('stroke','#000');ci.setAttribute('stroke-width',Math.max(1,sw()*0.4));g.appendChild(ci);
    if(lbl)mkFloat(g,+cx+dr()*3,+cy-fz()*1.5,lbl,c);svg.appendChild(g);return g;
}
function mkText(x,y,c,lbl,id){
    var g=S('g');g.setAttribute('data-id',id||'');
    var f=fz()*1.6;
    /* background pill for readability */
    if(lbl){
        var pad=f*0.3,h=f*1.3,tw=lbl.length*f*0.55+pad*2;
        var bg=S('rect');bg.setAttribute('x',x-pad);bg.setAttribute('y',y-h+pad);bg.setAttribute('width',tw);bg.setAttribute('height',h);bg.setAttribute('fill',c);bg.setAttribute('rx','5');bg.setAttribute('opacity','0.9');g.appendChild(bg);
        var t=S('text');t.setAttribute('x',x);t.setAttribute('y',y);t.setAttribute('fill',c==='#ffffff'?'#000':'#fff');t.setAttribute('font-size',f);t.setAttribute('font-weight','700');t.setAttribute('font-family','system-ui,sans-serif');t.textContent=lbl;g.appendChild(t);
    }
    svg.appendChild(g);return g;
}
function mkMeasure(x1,y1,x2,y2,c,lbl,id){
    var g=S('g');g.setAttribute('data-id',id||'');
    var l=S('line');l.setAttribute('x1',x1);l.setAttribute('y1',y1);l.setAttribute('x2',x2);l.setAttribute('y2',y2);
    l.setAttribute('stroke',c);l.setAttribute('stroke-width',Math.max(1,sw()*0.6));l.setAttribute('stroke-dasharray',sw()*3+','+sw()*2);g.appendChild(l);
    var dx=x2-x1,dy=y2-y1,len=Math.sqrt(dx*dx+dy*dy)||1,n=sw()*4,nx=-dy/len*n,ny=dx/len*n;
    [[x1,y1],[x2,y2]].forEach(function(p){var t=S('line');t.setAttribute('x1',p[0]-nx);t.setAttribute('y1',p[1]-ny);t.setAttribute('x2',+p[0]+nx);t.setAttribute('y2',+p[1]+ny);t.setAttribute('stroke',c);t.setAttribute('stroke-width',Math.max(1,sw()*0.6));g.appendChild(t);});
    mkFloat(g,(+x1+ +x2)/2,(+y1+ +y2)/2-fz()*1.8,lbl||Math.round(len)+'px',c);svg.appendChild(g);return g;
}

/* label with background pill */
function mkLabel(g,x,y,lbl,c){
    var f=fz(),pad=f*0.35,h=f*1.2,tw=lbl.length*f*0.55+pad*2;
    var bg=S('rect');bg.setAttribute('x',x);bg.setAttribute('y',y-h-2);bg.setAttribute('width',tw);bg.setAttribute('height',h);bg.setAttribute('fill',c);bg.setAttribute('rx','4');g.appendChild(bg);
    var t=S('text');t.setAttribute('x',+x+pad);t.setAttribute('y',y-h*0.22-2);t.setAttribute('fill',c==='#ffffff'?'#000':'#fff');t.setAttribute('font-size',f);t.setAttribute('font-weight','600');t.setAttribute('font-family','system-ui,sans-serif');t.textContent=lbl;g.appendChild(t);
}
/* floating label — pill with background for readability on any background */
function mkFloat(g,x,y,lbl,c){
    var f=fz()*1.1,pad=f*0.4,h=f*1.4,tw=lbl.length*f*0.58+pad*2;
    var bg=S('rect');bg.setAttribute('x',x);bg.setAttribute('y',y-h);bg.setAttribute('width',tw);bg.setAttribute('height',h);
    bg.setAttribute('fill',c);bg.setAttribute('rx','4');bg.setAttribute('opacity','0.9');g.appendChild(bg);
    var t=S('text');t.setAttribute('x',+x+pad);t.setAttribute('y',y-h*0.28);t.setAttribute('fill',c==='#ffffff'?'#000':'#fff');
    t.setAttribute('font-size',f);t.setAttribute('font-weight','700');t.setAttribute('font-family','system-ui,sans-serif');t.textContent=lbl;g.appendChild(t);
}

function renderOne(p,lbl,id){
    var c=p.color||'#e11d48';
    if(p.type==='rect')return mkRect(p.x,p.y,p.w,p.h,c,lbl,id);
    if(p.type==='circle')return mkCircle(p.cx,p.cy,p.rx,p.ry,c,lbl,id);
    if(p.type==='arrow')return mkArrow(p.x1,p.y1,p.x2,p.y2,c,lbl,id);
    if(p.type==='straightline')return mkLine(p.x1,p.y1,p.x2,p.y2,c,lbl,id);
    if(p.type==='freehand')return mkFree(p.points,c,lbl,id);
    if(p.type==='dot')return mkDot(p.cx,p.cy,c,lbl,id);
    if(p.type==='text')return mkText(p.x,p.y,c,lbl||p.text||'',id);
    if(p.type==='measure')return mkMeasure(p.x1,p.y1,p.x2,p.y2,c,lbl,id);
    if(p.rect)return mkRect(p.rect.x*VW,p.rect.y*VH,p.rect.w*VW,p.rect.h*VH,c,lbl,id);
    return null;
}

/* ── FIX: read note at submit time, not draw time ── */
var form=document.getElementById('anno-form');
if(form)form.addEventListener('submit',function(){if(noteHid&&noteEl)noteHid.value=noteEl.value;});

/* ── interaction ── */
if(canUpload){
    function rmPreview(){if(preview){try{svg.removeChild(preview);}catch(x){}preview=null;}}

    wrap.addEventListener('pointerdown',function(e){
        if(cropMode)return;
        if(e.target!==img&&e.target!==svg&&!svg.contains(e.target))return;
        var pt=norm(e);
        if(tool==='dot'){
            rmPreview();preview=mkDot(pt.x,pt.y,color,noteEl?noteEl.value:'','');
            if(payloadEl)payloadEl.value=JSON.stringify({type:'dot',color:color,cx:pt.x,cy:pt.y});
            if(btnSave)btnSave.disabled=false;e.preventDefault();return;
        }
        if(tool==='text'){
            var txt=noteEl?noteEl.value:'';if(!txt){alert('Digite o texto primeiro.');return;}
            rmPreview();preview=mkText(pt.x,pt.y,color,txt,'');
            if(payloadEl)payloadEl.value=JSON.stringify({type:'text',color:color,x:pt.x,y:pt.y,text:txt});
            if(btnSave)btnSave.disabled=false;e.preventDefault();return;
        }
        drawing=true;sp=pt;fp=[pt];
        try{wrap.setPointerCapture(e.pointerId);}catch(x){}e.preventDefault();
    });
    wrap.addEventListener('pointermove',function(e){
        if(!drawing||!sp)return;var c2=norm(e);rmPreview();
        if(tool==='rect')preview=mkRect(Math.min(sp.x,c2.x),Math.min(sp.y,c2.y),Math.abs(c2.x-sp.x),Math.abs(c2.y-sp.y),color,'','');
        else if(tool==='circle')preview=mkCircle((sp.x+c2.x)/2,(sp.y+c2.y)/2,Math.abs(c2.x-sp.x)/2,Math.abs(c2.y-sp.y)/2,color,'','');
        else if(tool==='arrow')preview=mkArrow(sp.x,sp.y,c2.x,c2.y,color,'','');
        else if(tool==='straightline')preview=mkLine(sp.x,sp.y,c2.x,c2.y,color,'','');
        else if(tool==='freehand'){fp.push(c2);preview=mkFree(fp,color,'','');}
        else if(tool==='measure')preview=mkMeasure(sp.x,sp.y,c2.x,c2.y,color,'','');
        e.preventDefault();
    });
    wrap.addEventListener('pointerup',function(e){
        if(!drawing||!sp)return;drawing=false;var c2=norm(e);var obj;
        if(tool==='rect')obj={type:'rect',color:color,x:Math.min(sp.x,c2.x),y:Math.min(sp.y,c2.y),w:Math.abs(c2.x-sp.x),h:Math.abs(c2.y-sp.y)};
        else if(tool==='circle')obj={type:'circle',color:color,cx:(sp.x+c2.x)/2,cy:(sp.y+c2.y)/2,rx:Math.abs(c2.x-sp.x)/2,ry:Math.abs(c2.y-sp.y)/2};
        else if(tool==='arrow')obj={type:'arrow',color:color,x1:sp.x,y1:sp.y,x2:c2.x,y2:c2.y};
        else if(tool==='straightline')obj={type:'straightline',color:color,x1:sp.x,y1:sp.y,x2:c2.x,y2:c2.y};
        else if(tool==='freehand'){fp.push(c2);obj={type:'freehand',color:color,points:fp};}
        else if(tool==='measure')obj={type:'measure',color:color,x1:sp.x,y1:sp.y,x2:c2.x,y2:c2.y};
        if(payloadEl)payloadEl.value=JSON.stringify(obj);
        if(btnSave)btnSave.disabled=false;sp=null;fp=[];
    });
}

/* ── audio ── */
var aRec=null,aCh=[],aOn=false;
window.toggleAudioRec=function(){aOn?stopA():startA();};
function startA(){var st=document.getElementById('audio-status'),btn=document.getElementById('btn-mic');navigator.mediaDevices.getUserMedia({audio:true}).then(function(s){aCh=[];aRec=new MediaRecorder(s);aRec.ondataavailable=function(e){if(e.data&&e.data.size)aCh.push(e.data);};aRec.onstop=function(){s.getTracks().forEach(function(t){t.stop();});if(!aCh.length)return;var b=new Blob(aCh,{type:aCh[0].type||'audio/webm'});if(st){st.textContent='Transcrevendo...';st.style.display='block';st.style.color='#2563eb';}var fd=new FormData();fd.append('_csrf',csrf);fd.append('patient_id','<?=$patientId?>');fd.append('audio',new File([b],'r.webm',{type:b.type}));fetch('/medical-records/audio/transcribe-json',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){if(j&&j.ok&&j.transcript&&noteEl){noteEl.value=(noteEl.value?noteEl.value+' ':'')+j.transcript;if(st){st.textContent='✓';st.style.color='#16a34a';}}else if(st){st.textContent=(j&&j.error)||'Falha';st.style.color='#b91c1c';}}).catch(function(){if(st){st.textContent='Erro';st.style.color='#b91c1c';}});};aRec.start();aOn=true;if(btn)btn.style.background='#fee2e2';if(st){st.textContent='🔴 Gravando...';st.style.display='block';st.style.color='#b91c1c';}}).catch(function(){var st=document.getElementById('audio-status');if(st){st.textContent='Sem microfone';st.style.display='block';st.style.color='#b91c1c';}});}
function stopA(){aOn=false;var btn=document.getElementById('btn-mic');if(btn)btn.style.background='';if(aRec&&aRec.state!=='inactive')aRec.stop();}

/* ── load ── */
var TL={rect:'Retângulo',circle:'Círculo',arrow:'Seta',straightline:'Linha',freehand:'Traço',dot:'Ponto',text:'Texto',measure:'Medida'};
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
        div.innerHTML='<div style="display:flex;align-items:center;gap:8px;min-width:0;"><span style="width:10px;height:10px;border-radius:50%;background:'+c+';flex-shrink:0;"></span><span style="font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+esc(it.note||(TL[p.type]||'Marcação'))+'</span></div>'+(canUpload?'<form method="post" action="/medical-images/annotations/delete" style="flex-shrink:0;"><input type="hidden" name="_csrf" value="'+esc(csrf)+'"/><input type="hidden" name="image_id" value="'+imageId+'"/><input type="hidden" name="id" value="'+it.id+'"/><button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" style="font-size:11px;padding:2px 8px;">✕</button></form>':'');
        listEl.appendChild(div);
    });
}
function esc(s){return String(s||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
function loadAnnotations(){
    fetch('/medical-images/annotations.json?image_id='+imageId,{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){renderAll((d&&d.items)?d.items:[]);}).catch(function(){listEl.innerHTML='<div class="lc-muted" style="padding:12px;">Erro.</div>';});
}

/* ── Crop mode ── */
var cropMode=false,cropStart=null,cropOverlay=document.getElementById('crop-overlay');
window.toggleCropMode=function(){
    cropMode=!cropMode;
    var bar=document.getElementById('crop-bar'),btn=document.getElementById('btn-crop-toggle');
    if(bar)bar.style.display=cropMode?'block':'none';
    if(btn)btn.className=cropMode?'lc-btn lc-btn--primary':'lc-btn lc-btn--secondary';
    if(cropOverlay)cropOverlay.style.display='none';
    if(!cropMode){wrap.style.cursor=canUpload?'crosshair':'default';}
    else{wrap.style.cursor='crosshair';}
    document.getElementById('btn-crop-apply').disabled=true;
};
if(canUpload&&wrap){
    wrap.addEventListener('pointerdown',function(e){
        if(!cropMode)return;
        var r=img.getBoundingClientRect();
        cropStart={px:e.clientX-r.left,py:e.clientY-r.top,bx:r.left,by:r.top,bw:r.width,bh:r.height};
        if(cropOverlay){cropOverlay.style.display='block';cropOverlay.style.left=cropStart.px+'px';cropOverlay.style.top=cropStart.py+'px';cropOverlay.style.width='0';cropOverlay.style.height='0';}
        try{wrap.setPointerCapture(e.pointerId);}catch(x){}
        e.preventDefault();e.stopPropagation();
    },true);
    wrap.addEventListener('pointermove',function(e){
        if(!cropMode||!cropStart)return;
        var r=img.getBoundingClientRect();
        var cx=e.clientX-r.left,cy=e.clientY-r.top;
        var x=Math.min(cropStart.px,cx),y=Math.min(cropStart.py,cy),w=Math.abs(cx-cropStart.px),h=Math.abs(cy-cropStart.py);
        if(cropOverlay){cropOverlay.style.left=x+'px';cropOverlay.style.top=y+'px';cropOverlay.style.width=w+'px';cropOverlay.style.height=h+'px';}
        e.preventDefault();e.stopPropagation();
    },true);
    wrap.addEventListener('pointerup',function(e){
        if(!cropMode||!cropStart)return;
        var r=img.getBoundingClientRect();
        var cx=e.clientX-r.left,cy=e.clientY-r.top;
        var px1=Math.min(cropStart.px,cx),py1=Math.min(cropStart.py,cy);
        var pw=Math.abs(cx-cropStart.px),ph=Math.abs(cy-cropStart.py);
        // Convert screen px to image px
        var scaleX=VW/r.width,scaleY=VH/r.height;
        var ix=Math.round(px1*scaleX),iy=Math.round(py1*scaleY),iw=Math.round(pw*scaleX),ih=Math.round(ph*scaleY);
        document.getElementById('crop-x').value=ix;
        document.getElementById('crop-y').value=iy;
        document.getElementById('crop-w').value=iw;
        document.getElementById('crop-h').value=ih;
        document.getElementById('btn-crop-apply').disabled=(iw<10||ih<10);
        cropStart=null;
        e.preventDefault();e.stopPropagation();
    },true);
}
})();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
