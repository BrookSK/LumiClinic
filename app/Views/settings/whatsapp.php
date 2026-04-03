<?php
$title = 'Configurações - WhatsApp';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$diagnose = $diagnose ?? null;
$connectData = $connect_data ?? null;
$evolutionInstance = $evolution_instance ?? null;
$evolutionApiKeySet = $evolution_apikey_set ?? false;
$globalConfigured = $global_configured ?? false;

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

$isConfigured = $globalConfigured || ($evolutionInstance !== null && $evolutionInstance !== '' && $evolutionApiKeySet);

ob_start();
?>

<style>
.wa-back{display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px}
.wa-back:hover{color:rgba(129,89,1,1)}
.wa-card{padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.wa-status{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;margin-bottom:16px}
.wa-status--ok{border:1px solid rgba(22,163,74,.22);background:rgba(22,163,74,.06)}
.wa-status--off{border:1px solid rgba(107,114,128,.18);background:rgba(107,114,128,.04)}
.wa-status__icon{font-size:16px}
.wa-status__text{font-weight:700;font-size:13px}
.wa-qr{display:flex;justify-content:center;padding:20px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);margin-top:14px}
.wa-qr img{max-width:240px;width:100%;height:auto;border-radius:8px}
.wa-links{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
</style>

<a href="/settings" class="wa-back">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para configurações
</a>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="wa-card">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <span style="font-size:24px;">💬</span>
        <div>
            <div style="font-weight:850;font-size:18px;">WhatsApp</div>
            <div style="font-size:13px;color:rgba(31,41,55,.50);">Conecte o WhatsApp da clínica para enviar lembretes, confirmações e campanhas automaticamente.</div>
        </div>
    </div>

    <!-- Status -->
    <div id="lc-wa-status" class="wa-status <?= $isConfigured ? 'wa-status--ok' : 'wa-status--off' ?>">
        <span class="wa-status__icon"><?= $isConfigured ? '✅' : '⚠️' ?></span>
        <span class="wa-status__text" style="color:<?= $isConfigured ? '#16a34a' : '#6b7280' ?>;"><?= $isConfigured ? 'Configurado' : 'Não configurado' ?></span>
    </div>

    <?php if ($can('settings.update')): ?>
        <?php if ($globalConfigured): ?>
            <input type="hidden" id="lc-wa-csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div style="font-size:13px;color:rgba(31,41,55,.60);margin-bottom:14px;line-height:1.5;">
                Clique em "Conectar" para gerar o QR Code. Abra o WhatsApp no celular → Configurações → Aparelhos conectados → Conectar aparelho → Escaneie o QR Code abaixo.
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button id="lc-wa-connect" class="lc-btn lc-btn--primary" type="button">Conectar WhatsApp</button>
                <button id="lc-wa-disconnect" class="lc-btn lc-btn--danger lc-btn--sm" type="button" style="display:none;">Desconectar</button>
            </div>

            <details style="margin-top:10px;">
                <summary style="font-size:12px;color:rgba(31,41,55,.40);cursor:pointer;list-style:none;">Detalhes técnicos</summary>
                <div style="font-size:12px;color:rgba(31,41,55,.40);margin-top:6px;">Instância: <code><?= htmlspecialchars((string)($evolutionInstance ?? ''), ENT_QUOTES, 'UTF-8') ?></code></div>
            </details>
        <?php else: ?>
            <div style="font-size:13px;color:rgba(31,41,55,.60);margin-bottom:14px;line-height:1.5;">
                Configure a conexão com a Evolution API. Você precisa do nome da instância e da API Key fornecidos pelo administrador do sistema.
            </div>

            <form method="post" class="lc-form" action="/settings/whatsapp">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:500px;">
                    <div class="lc-field">
                        <label class="lc-label">Instância</label>
                        <input class="lc-input" type="text" name="evolution_instance" value="<?= htmlspecialchars((string)($evolutionInstance ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="nome-da-instancia" autocomplete="off" />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">API Key</label>
                        <input class="lc-input" type="password" name="evolution_apikey" placeholder="apikey" autocomplete="off" />
                    </div>
                </div>
                <div style="display:flex;gap:10px;margin-top:14px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                    <form method="post" action="/settings/whatsapp/test" style="margin:0;display:inline;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Testar conexão</button>
                    </form>
                </div>
            </form>

            <details style="margin-top:14px;">
                <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;">Remover configuração</summary>
                <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
                    <form method="post" action="/settings/whatsapp/clear" style="margin:0;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit" onclick="return confirm('Remover a configuração de WhatsApp?');">Confirmar remoção</button>
                    </form>
                </div>
            </details>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- QR Code area -->
<?php
$qrHtml = '';
if (is_array($connectData)) {
    $pairingCode = isset($connectData['pairingCode']) ? trim((string)$connectData['pairingCode']) : '';
    $code = '';
    if (isset($connectData['qrcode']) && is_array($connectData['qrcode'])) {
        $code = isset($connectData['qrcode']['base64']) ? trim((string)$connectData['qrcode']['base64']) : '';
        if ($code === '') $code = isset($connectData['qrcode']['code']) ? trim((string)$connectData['qrcode']['code']) : '';
    }
    if ($code === '') $code = isset($connectData['base64']) ? trim((string)$connectData['base64']) : '';
    if ($code === '') $code = isset($connectData['code']) ? trim((string)$connectData['code']) : '';
    $imgSrc = null;
    if ($code !== '') {
        if (stripos($code, 'data:image') === 0) $imgSrc = $code;
        elseif (preg_match('/^[A-Za-z0-9+\/\r\n]+=*$/', $code) && strlen($code) > 120) $imgSrc = 'data:image/png;base64,' . $code;
    }
}
?>

<div id="lc-wa-qr" class="wa-card" style="display:<?= is_array($connectData) ? 'block' : 'none' ?>;">
    <div style="font-weight:750;font-size:14px;margin-bottom:10px;">Escaneie o QR Code</div>
    <div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:10px;">Abra o WhatsApp no celular → Menu (⋮) → Aparelhos conectados → Conectar aparelho</div>
    <div id="lc-wa-qr-img" class="wa-qr" style="display:<?= (isset($imgSrc) && $imgSrc !== null) ? 'flex' : 'none' ?>;">
        <img id="lc-wa-qr-img-tag" src="<?= isset($imgSrc) && $imgSrc !== null ? htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') : '' ?>" alt="QR Code" />
    </div>
    <div id="lc-wa-pairing" style="margin-top:10px;font-size:13px;color:rgba(31,41,55,.60);display:<?= (isset($pairingCode) && $pairingCode !== '') ? 'block' : 'none' ?>;">
        Código de pareamento: <code><?= htmlspecialchars($pairingCode ?? '', ENT_QUOTES, 'UTF-8') ?></code>
    </div>
    <div id="lc-wa-attempt" style="margin-top:6px;font-size:12px;color:rgba(31,41,55,.40);display:none;"></div>
    <div id="lc-wa-raw" style="display:none;margin-top:10px;">
        <div style="font-size:12px;color:rgba(31,41,55,.40);">Código retornado:</div>
        <pre style="white-space:pre-wrap;word-break:break-word;margin-top:4px;font-size:11px;"><code id="lc-wa-raw-code"></code></pre>
    </div>
</div>

<!-- Diagnóstico e ações -->
<?php if ($can('settings.update')): ?>
<div class="wa-card">
    <div style="font-weight:750;font-size:14px;margin-bottom:10px;">Ferramentas</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <form method="post" action="/settings/whatsapp/diagnose" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Diagnosticar problemas</button>
        </form>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/whatsapp-templates">Gerenciar templates</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/whatsapp-logs">Ver logs de envio</a>
    </div>
</div>
<?php endif; ?>

<?php if (is_array($diagnose) && isset($diagnose['checks']) && is_array($diagnose['checks'])): ?>
<div class="wa-card">
    <div style="font-weight:750;font-size:14px;margin-bottom:12px;">Resultado do diagnóstico</div>
    <?php foreach ($diagnose['checks'] as $c): ?>
        <?php
        $ok = isset($c['ok']) ? (bool)$c['ok'] : false;
        $dTitle = (string)($c['title'] ?? '');
        $dMsg = (string)($c['message'] ?? '');
        $aLabel = isset($c['action_label']) ? (string)$c['action_label'] : null;
        $aUrl = isset($c['action_url']) ? (string)$c['action_url'] : null;
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid rgba(17,24,39,.06);flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:14px;"><?= $ok ? '✅' : '❌' ?></span>
                <div>
                    <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($dTitle, ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size:12px;color:rgba(31,41,55,.50);"><?= htmlspecialchars($dMsg, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
            <?php if ($aLabel !== null && $aUrl !== null): ?>
                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="<?= htmlspecialchars($aUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($aLabel, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($globalConfigured): ?>
<script>
(function(){
    var csrfEl=document.getElementById('lc-wa-csrf'),statusEl=document.getElementById('lc-wa-status'),btnC=document.getElementById('lc-wa-connect'),btnD=document.getElementById('lc-wa-disconnect'),qrCard=document.getElementById('lc-wa-qr'),qrImgW=document.getElementById('lc-wa-qr-img'),qrImg=document.getElementById('lc-wa-qr-img-tag'),pairEl=document.getElementById('lc-wa-pairing'),attEl=document.getElementById('lc-wa-attempt'),rawW=document.getElementById('lc-wa-raw'),rawC=document.getElementById('lc-wa-raw-code');
    var csrf=csrfEl?csrfEl.value:'',lastQr=0,busy=false;
    function esc(s){return(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
    function setSt(t,ok){if(!statusEl)return;statusEl.innerHTML='<span class="wa-status__icon">'+(ok?'✅':'⚠️')+'</span><span class="wa-status__text" style="color:'+(ok?'#16a34a':'#6b7280')+';">'+esc(t)+'</span>';statusEl.className='wa-status '+(ok?'wa-status--ok':'wa-status--off');}
    function setBtns(c){if(btnC)btnC.style.display=c?'none':'';if(btnD)btnD.style.display=c?'':'none';}
    function renderQr(d){if(!d||!qrCard)return;qrCard.style.display='';var pc=(d.pairingCode||'').toString().trim(),cnt=typeof d.count==='number'?d.count:null,code='';if(d.qrcode&&typeof d.qrcode==='object'){code=(d.qrcode.base64||'').toString().trim();if(!code)code=(d.qrcode.code||'').toString().trim();}if(!code)code=(d.base64||'').toString().trim();if(!code)code=(d.code||'').toString().trim();var img=null;if(code){if(code.indexOf('data:image')===0)img=code;else if(/^[A-Za-z0-9+\/\r\n]+=*$/.test(code)&&code.length>120)img='data:image/png;base64,'+code;}if(img){if(qrImg)qrImg.src=img;if(qrImgW)qrImgW.style.display='flex';if(rawW)rawW.style.display='none';}else{if(qrImgW)qrImgW.style.display='none';if(rawW){rawW.style.display=code?'block':'none';}if(rawC)rawC.textContent=code||'';}if(pairEl){pairEl.style.display=pc?'block':'none';if(pc)pairEl.innerHTML='Código: <code>'+esc(pc)+'</code>';}if(attEl){attEl.style.display=cnt!==null?'block':'none';if(cnt!==null)attEl.textContent='Tentativa: '+cnt;}lastQr=Date.now();}
    function post(u,p){p=p||{};return fetch(u,{method:'POST',headers:{'Content-Type':'application/json;charset=UTF-8','X-CSRF-Token':csrf},body:JSON.stringify(p)}).then(function(r){return r.json().then(function(d){return{status:r.status,data:d};});});}
    function refresh(){if(busy)return;busy=true;if(btnC)btnC.disabled=true;post('/settings/whatsapp/connect-json',{}).then(function(r){busy=false;if(btnC)btnC.disabled=false;if(r.data&&r.data.ok)renderQr(r.data.connect_data);}).catch(function(){busy=false;if(btnC)btnC.disabled=false;});}
    function disc(){if(btnD)btnD.disabled=true;post('/settings/whatsapp/disconnect',{}).then(function(r){if(btnD)btnD.disabled=false;if(r.data&&r.data.ok){setSt('Desconectado',false);setBtns(false);lastQr=0;refresh();}}).catch(function(){if(btnD)btnD.disabled=false;});}
    function tick(){fetch('/settings/whatsapp/status-json',{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){if(!d||!d.ok){setSt('Erro',false);return;}if(d.connected){setSt('Conectado ✓',true);setBtns(true);return;}setBtns(false);var sl={'open':'Conectado','connecting':'Conectando...','close':'Desconectado','refused':'Recusado','timeout':'Timeout'};var s=d.state?d.state.toString():'';setSt(s?(sl[s]||s):'Aguardando',false);if(!lastQr||Date.now()-lastQr>25000)refresh();}).catch(function(){setSt('Sem resposta',false);});}
    if(btnC)btnC.addEventListener('click',refresh);
    if(btnD)btnD.addEventListener('click',disc);
    tick();setInterval(tick,2500);
})();
</script>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
