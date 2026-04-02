<?php
$title = 'Notificações';
$csrf = $_SESSION['_csrf'] ?? '';
$notifications = $notifications ?? [];
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

ob_start();
?>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:18px;">Notificações</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Push (só mobile) -->
<div id="pushSection" style="display:none;padding:14px 16px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
        <div>
            <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.80);">Notificações push</div>
            <div style="font-size:12px;color:rgba(31,41,55,.45);" id="lcPushStatus">Ative para receber avisos no celular.</div>
        </div>
        <button class="lc-btn lc-btn--primary lc-btn--sm" type="button" id="lcEnablePush">Ativar</button>
    </div>
</div>

<!-- Lista -->
<?php if (!is_array($notifications) || $notifications === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">🔔</div>
        <div style="font-size:14px;">Nenhuma notificação.</div>
    </div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($notifications as $n): ?>
        <?php $isNew = ($n['read_at'] ?? null) === null; ?>
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:14px 16px;border-radius:12px;border:1px solid <?= $isNew ? 'rgba(238,184,16,.22)' : 'rgba(17,24,39,.06)' ?>;background:<?= $isNew ? 'rgba(253,229,159,.06)' : 'var(--lc-surface)' ?>;flex-wrap:wrap;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);"><?= htmlspecialchars((string)($n['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div style="font-size:13px;color:rgba(31,41,55,.60);margin-top:4px;line-height:1.5;"><?= nl2br(htmlspecialchars((string)($n['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:6px;"><?= htmlspecialchars((string)($n['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div style="flex-shrink:0;">
                <?php if ($isNew): ?>
                    <form method="post" action="/portal/notificacoes/read" style="margin:0;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="id" value="<?= (int)($n['id'] ?? 0) ?>" />
                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Marcar como lida</button>
                    </form>
                <?php else: ?>
                    <span style="font-size:11px;color:rgba(31,41,55,.35);">Lida</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
(function(){
  try{
    // Mostrar seção push só em mobile/PWA
    var isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    var pushSec = document.getElementById('pushSection');
    if (pushSec && isMobile) pushSec.style.display = 'block';

    var csrf=<?= json_encode($csrf, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    var btn=document.getElementById('lcEnablePush');
    var statusEl=document.getElementById('lcPushStatus');
    function setStatus(t){if(statusEl)statusEl.textContent=t;}
    function urlB64(b){var p='='.repeat((4-b.length%4)%4);var s=(b+p).replace(/-/g,'+').replace(/_/g,'/');var r=atob(s);var a=new Uint8Array(r.length);for(var i=0;i<r.length;i++)a[i]=r.charCodeAt(i);return a;}
    async function enable(){
      if(!('serviceWorker' in navigator)||!('PushManager' in window)){setStatus('Seu navegador não suporta push.');return;}
      if(Notification.permission==='denied'){setStatus('Bloqueado. Permita nas configurações do navegador.');return;}
      var reg=await navigator.serviceWorker.ready;
      var ex=await reg.pushManager.getSubscription();
      if(ex){setStatus('Ativo ✓');return;}
      var cfg=await(await fetch('/portal/push/config',{credentials:'same-origin'})).json();
      if(!cfg||!cfg.public_key){setStatus('Não configurado pelo administrador.');return;}
      var perm=await Notification.requestPermission();
      if(perm!=='granted'){setStatus('Permissão não concedida.');return;}
      var sub=await reg.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:urlB64(String(cfg.public_key))});
      var res=await(await fetch('/portal/push/subscribe',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(Object.assign(sub.toJSON(),{_csrf:csrf}))})).json();
      setStatus(res&&res.ok?'Ativo ✓':'Erro ao salvar.');
    }
    if(btn)btn.addEventListener('click',function(){setStatus('Ativando...');enable().catch(function(){setStatus('Erro.');});});
    if(typeof Notification!=='undefined'&&Notification.permission==='granted')setStatus('Ativo ✓');
  }catch(e){}
})();
</script>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'notificacoes';
require __DIR__ . '/_shell.php';
