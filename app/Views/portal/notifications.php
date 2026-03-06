<?php
$title = 'Notificações';
$csrf = $_SESSION['_csrf'] ?? '';
$notifications = $notifications ?? [];
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);
ob_start();
?>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin-top:16px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success" style="margin-top:16px;">
            <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Push (app)</div>
        <div class="lc-card__body">
            <div class="lc-flex lc-flex--wrap" style="gap:10px; align-items:center;">
                <button class="lc-btn lc-btn--secondary" type="button" id="lcEnablePush">Ativar notificações push</button>

                <form method="post" action="/portal/push/test" onsubmit="return confirm('Enviar push de teste?');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <button class="lc-btn lc-btn--secondary" type="submit">Testar push</button>
                </form>

                <div class="lc-muted" id="lcPushStatus">Status: não verificado</div>
            </div>
            <div class="lc-muted" style="margin-top:8px;">
                No iPhone (Safari), use "Compartilhar" -> "Adicionar à Tela de Início" para instalar.
            </div>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Últimas</div>
        <div class="lc-card__body">
            <?php if (!is_array($notifications) || $notifications === []): ?>
                <div>Nenhuma notificação.</div>
            <?php else: ?>
                <div class="lc-grid" style="gap:10px;">
                    <?php foreach ($notifications as $n): ?>
                        <div class="lc-card" style="padding:12px;">
                            <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-md">
                                <div>
                                    <div><strong><?= htmlspecialchars((string)($n['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                                    <div><?= nl2br(htmlspecialchars((string)($n['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
                                    <div style="margin-top:6px; opacity:0.8;">
                                        <?= htmlspecialchars((string)($n['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                                <div class="lc-flex" style="gap:8px; align-items:flex-start;">
                                    <?php if (($n['read_at'] ?? null) === null): ?>
                                        <form method="post" action="/portal/notificacoes/read">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)($n['id'] ?? 0) ?>" />
                                            <button class="lc-btn lc-btn--secondary" type="submit">Marcar como lida</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="lc-badge lc-badge--gray">Lida</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    (function(){
      try {
        var csrf = <?= json_encode((string)$csrf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        var btn = document.getElementById('lcEnablePush');
        var statusEl = document.getElementById('lcPushStatus');

        function setStatus(t){
          if (statusEl) statusEl.textContent = 'Status: ' + t;
        }

        function urlBase64ToUint8Array(base64String) {
          var padding = '='.repeat((4 - base64String.length % 4) % 4);
          var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
          var rawData = atob(base64);
          var outputArray = new Uint8Array(rawData.length);
          for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
          }
          return outputArray;
        }

        async function fetchJson(url){
          var r = await fetch(url, { credentials: 'same-origin' });
          return await r.json();
        }

        async function postJson(url, data){
          data = data || {};
          data._csrf = csrf;
          var r = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
          });
          return await r.json();
        }

        async function ensureSubscription(){
          if (!('serviceWorker' in navigator)) {
            setStatus('sem suporte a Service Worker');
            return;
          }
          if (!('PushManager' in window)) {
            setStatus('sem suporte a Push');
            return;
          }

          var perm = Notification.permission;
          if (perm === 'denied') {
            setStatus('bloqueado (permite nas configurações do navegador)');
            return;
          }

          var reg = await navigator.serviceWorker.ready;
          var existing = await reg.pushManager.getSubscription();
          if (existing) {
            setStatus('ativo');
            return existing;
          }

          var cfg = await fetchJson('/portal/push/config');
          if (!cfg || !cfg.public_key) {
            setStatus('não configurado (sem VAPID public key)');
            return;
          }

          var permission = await Notification.requestPermission();
          if (permission !== 'granted') {
            setStatus('permissão não concedida');
            return;
          }

          var sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(String(cfg.public_key))
          });

          var res = await postJson('/portal/push/subscribe', sub.toJSON());
          if (res && res.ok) {
            setStatus('ativo');
          } else {
            setStatus('erro ao salvar inscrição');
          }
          return sub;
        }

        if (btn) {
          btn.addEventListener('click', function(){
            setStatus('ativando...');
            ensureSubscription().catch(function(){ setStatus('erro'); });
          });
        }

        // Passive status
        if (typeof Notification !== 'undefined') {
          if (Notification.permission === 'granted') setStatus('permitido');
          if (Notification.permission === 'denied') setStatus('bloqueado');
        }
      } catch (e) {}
    })();
    </script>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'notificacoes';
require __DIR__ . '/_shell.php';
