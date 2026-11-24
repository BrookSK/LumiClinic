<?php
/**
 * Widget flutuante do assistente de suporte. Incluído no fim de layout/app.php somente para usuário de clínica
 * (não super admin, não "logar como") quando o assistente está ativo. Variável esperada: $csrf.
 * Todo texto dinâmico (respostas do modelo, mensagens do usuário) é inserido com textContent, nunca como HTML.
 */
$_scCsrf = htmlspecialchars((string)($csrf ?? ''), ENT_QUOTES, 'UTF-8');
?>
<style>
#lc-sc-root{--lc-sc-main:#815901;--lc-sc-accent:#eeb810;--lc-sc-bg:#fffdf8;--lc-sc-text:#1f2937;--lc-sc-muted:rgba(31,41,55,.65);--lc-sc-border:rgba(17,24,39,.14);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}
#lc-sc-root *{box-sizing:border-box}
.lc-sc-fab{position:fixed;right:20px;bottom:20px;z-index:9999;width:56px;height:56px;border-radius:50%;border:0;background:var(--lc-sc-main);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 20px rgba(0,0,0,.25);transition:transform .15s,box-shadow .15s}
.lc-sc-fab:hover{transform:scale(1.06);box-shadow:0 8px 26px rgba(0,0,0,.3)}
.lc-sc-fab:focus-visible,.lc-sc-btn:focus-visible,.lc-sc-chip:focus-visible,.lc-sc-input:focus-visible,.lc-sc-link:focus-visible{outline:3px solid var(--lc-sc-accent);outline-offset:2px}
.lc-sc-panel{position:fixed;right:20px;bottom:88px;z-index:10000;width:380px;max-width:calc(100vw - 24px);height:560px;max-height:calc(100vh - 110px);background:var(--lc-sc-bg);color:var(--lc-sc-text);border:1px solid var(--lc-sc-border);border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.28);display:none;flex-direction:column;overflow:hidden}
.lc-sc-panel.lc-sc-open{display:flex}
.lc-sc-head{display:flex;align-items:center;gap:8px;padding:12px 14px;background:var(--lc-sc-main);color:#fff}
.lc-sc-title{flex:1;font-size:15px;font-weight:700;margin:0}
.lc-sc-sub{font-size:11px;opacity:.85;font-weight:400;display:block}
.lc-sc-hbtn{background:rgba(255,255,255,.16);color:#fff;border:0;border-radius:8px;padding:6px 9px;font-size:12px;cursor:pointer}
.lc-sc-hbtn:hover{background:rgba(255,255,255,.28)}
.lc-sc-msgs{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;background:#fff}
.lc-sc-msg{max-width:88%;padding:8px 11px;border-radius:12px;font-size:13.5px;line-height:1.45;word-wrap:break-word;overflow-wrap:anywhere}
.lc-sc-msg p{margin:0 0 4px}.lc-sc-msg p:last-child{margin-bottom:0}
.lc-sc-msg ul{margin:4px 0 4px 18px;padding:0}
.lc-sc-msg a{color:var(--lc-sc-main);text-decoration:underline;font-weight:600}
.lc-sc-bot{align-self:flex-start;background:#f6f1e4;border-top-left-radius:4px}
.lc-sc-user{align-self:flex-end;background:var(--lc-sc-main);color:#fff;border-top-right-radius:4px}
.lc-sc-user a{color:#fff}
.lc-sc-err{align-self:flex-start;background:#fdecec;color:#7f1d1d}
.lc-sc-typing{align-self:flex-start;margin:0 12px 8px;display:none;gap:4px;padding:10px 12px;background:#f6f1e4;border-radius:12px}
.lc-sc-typing.lc-sc-on{display:flex}
.lc-sc-typing i{width:6px;height:6px;border-radius:50%;background:var(--lc-sc-muted);animation:lc-sc-blink 1.2s infinite both}
.lc-sc-typing i:nth-child(2){animation-delay:.2s}.lc-sc-typing i:nth-child(3){animation-delay:.4s}
@keyframes lc-sc-blink{0%,80%,100%{opacity:.25}40%{opacity:1}}
.lc-sc-chips{display:flex;flex-wrap:wrap;gap:6px;padding:0 12px 8px;background:#fff}
.lc-sc-chip{border:1px solid var(--lc-sc-border);background:var(--lc-sc-bg);color:var(--lc-sc-text);border-radius:999px;padding:5px 10px;font-size:12px;cursor:pointer}
.lc-sc-chip:hover{border-color:var(--lc-sc-main)}
.lc-sc-resolve{display:none;flex-wrap:wrap;align-items:center;gap:6px;padding:0 12px 8px;background:#fff;font-size:12.5px;font-weight:600}
.lc-sc-resolve.lc-sc-on{display:flex}
.lc-sc-resolve .lc-sc-chip{font-weight:600}
.lc-sc-resolve .lc-sc-team{border-color:var(--lc-sc-main);color:var(--lc-sc-main)}
.lc-sc-form{display:flex;gap:8px;align-items:flex-end;padding:8px 12px;border-top:1px solid var(--lc-sc-border);background:var(--lc-sc-bg)}
.lc-sc-input{flex:1;resize:none;border:1px solid var(--lc-sc-border);border-radius:10px;padding:8px 10px;font:inherit;font-size:13.5px;max-height:96px;min-height:38px;background:#fff;color:var(--lc-sc-text)}
.lc-sc-btn{border:0;border-radius:10px;background:var(--lc-sc-main);color:#fff;padding:9px 14px;font-size:13px;font-weight:700;cursor:pointer}
.lc-sc-btn:disabled{opacity:.5;cursor:not-allowed}
.lc-sc-foot{display:flex;align-items:center;gap:12px;padding:6px 12px 10px;font-size:11.5px;color:var(--lc-sc-muted);background:var(--lc-sc-bg)}
.lc-sc-foot .lc-sc-count{margin-left:auto}
.lc-sc-link{color:var(--lc-sc-main);text-decoration:underline}
@media (max-width:480px){
  .lc-sc-panel{right:0;left:0;bottom:0;width:100%;max-width:100%;height:100%;max-height:100%;border-radius:0}
  .lc-sc-fab{right:14px;bottom:14px}
}
@media (prefers-reduced-motion:reduce){.lc-sc-fab,.lc-sc-typing i{transition:none;animation:none}}
</style>

<div id="lc-sc-root" data-csrf="<?= $_scCsrf ?>">
  <button type="button" class="lc-sc-fab" id="lc-sc-fab" aria-label="Abrir assistente de suporte" aria-expanded="false" aria-controls="lc-sc-panel" title="Precisa de ajuda?">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.3A8 8 0 1 1 21 12z"/><circle cx="9" cy="12" r=".9" fill="currentColor"/><circle cx="12.5" cy="12" r=".9" fill="currentColor"/><circle cx="16" cy="12" r=".9" fill="currentColor"/></svg>
  </button>

  <section class="lc-sc-panel" id="lc-sc-panel" role="dialog" aria-modal="false" aria-labelledby="lc-sc-title" hidden>
    <header class="lc-sc-head">
      <h2 class="lc-sc-title" id="lc-sc-title">Assistente LumiClinic<span class="lc-sc-sub">Tire dúvidas sobre o sistema</span></h2>
      <button type="button" class="lc-sc-hbtn" id="lc-sc-new" aria-label="Iniciar nova conversa">Nova conversa</button>
      <button type="button" class="lc-sc-hbtn" id="lc-sc-close" aria-label="Fechar assistente">Fechar</button>
    </header>
    <div class="lc-sc-msgs" id="lc-sc-msgs" role="log" aria-live="polite" aria-label="Mensagens do assistente"></div>
    <div class="lc-sc-typing" id="lc-sc-typing" aria-label="O assistente está escrevendo"><i></i><i></i><i></i></div>
    <div class="lc-sc-chips" id="lc-sc-chips"></div>
    <div class="lc-sc-resolve" id="lc-sc-resolve" role="group" aria-label="A resposta resolveu?">
      <span>Isso resolveu?</span>
      <button type="button" class="lc-sc-chip" id="lc-sc-yes">Sim</button>
      <button type="button" class="lc-sc-chip lc-sc-team" id="lc-sc-no">Não, falar com a equipe</button>
    </div>
    <form class="lc-sc-form" id="lc-sc-form" autocomplete="off">
      <textarea class="lc-sc-input" id="lc-sc-input" rows="1" maxlength="1000" placeholder="Digite sua dúvida..." aria-label="Mensagem para o assistente"></textarea>
      <button type="submit" class="lc-sc-btn" id="lc-sc-send">Enviar</button>
    </form>
    <div class="lc-sc-foot">
      <a class="lc-sc-link" href="/tutorial/guia-completo">Guia completo</a>
      <a class="lc-sc-link" id="lc-sc-wa" href="#" target="_blank" rel="noopener" hidden>WhatsApp</a>
      <span class="lc-sc-count" id="lc-sc-count" aria-live="off">0/1000</span>
    </div>
  </section>
</div>

<script>
(function () {
  'use strict';
  var root = document.getElementById('lc-sc-root');
  if (!root) return;
  var $ = function (id) { return document.getElementById(id); };
  var fab = $('lc-sc-fab'), panel = $('lc-sc-panel'), msgs = $('lc-sc-msgs'), typing = $('lc-sc-typing');
  var chipsBox = $('lc-sc-chips'), resolveBox = $('lc-sc-resolve'), yesBtn = $('lc-sc-yes'), noBtn = $('lc-sc-no'), form = $('lc-sc-form'), input = $('lc-sc-input');
  var sendBtn = $('lc-sc-send'), countEl = $('lc-sc-count'), waLink = $('lc-sc-wa');
  var csrf = root.getAttribute('data-csrf') || '';
  var KEY = 'lcScConversation';
  var ESCALATE_TEXT = 'Não, falar com a equipe'; // igual a SupportBotService::ESCALATE_TEXT
  var booted = false, busy = false, maxChars = 1000, greeting = '';
  var convId = null;
  try { convId = window.sessionStorage.getItem(KEY) || null; } catch (e) {}

  function saveConv() {
    try {
      if (convId) window.sessionStorage.setItem(KEY, String(convId)); else window.sessionStorage.removeItem(KEY);
    } catch (e) {}
  }

  // ---- renderização segura: só nós de texto, **negrito**, listas "- " e links internos "/caminho"
  function inline(parent, text) {
    var re = /\*\*([^*]+)\*\*|\[([^\]]+)\]\((\/[^)\s]*)\)/g, last = 0, m;
    while ((m = re.exec(text)) !== null) {
      if (m.index > last) parent.appendChild(document.createTextNode(text.slice(last, m.index)));
      if (m[1] !== undefined) {
        var b = document.createElement('strong'); b.textContent = m[1]; parent.appendChild(b);
      } else if (m[3].charAt(0) === '/' && /^\/[A-Za-z0-9]/.test(m[3]) && m[3].indexOf('\\') === -1) {
        var a = document.createElement('a'); a.textContent = m[2]; a.setAttribute('href', m[3]); parent.appendChild(a);
      } else {
        parent.appendChild(document.createTextNode(m[0]));
      }
      last = re.lastIndex;
    }
    if (last < text.length) parent.appendChild(document.createTextNode(text.slice(last)));
  }

  function renderRich(el, text) {
    var lines = String(text || '').replace(/\r/g, '').split('\n'), list = null, i, line, li, p;
    for (i = 0; i < lines.length; i++) {
      line = lines[i];
      var item = /^\s*[-*]\s+(.*)$/.exec(line);
      if (item) {
        if (!list) { list = document.createElement('ul'); el.appendChild(list); }
        li = document.createElement('li'); inline(li, item[1]); list.appendChild(li);
        continue;
      }
      list = null;
      if (line.trim() === '') continue;
      p = document.createElement('p'); inline(p, line); el.appendChild(p);
    }
  }

  function scrollDown() { msgs.scrollTop = msgs.scrollHeight; }

  function addMsg(kind, text, rich) {
    var d = document.createElement('div');
    d.className = 'lc-sc-msg lc-sc-' + kind;
    if (rich) renderRich(d, text); else d.textContent = text;
    msgs.appendChild(d);
    scrollDown();
    return d;
  }

  function setBusy(v) {
    busy = v;
    sendBtn.disabled = v;
    yesBtn.disabled = v;
    noBtn.disabled = v;
    typing.classList.toggle('lc-sc-on', v);
    if (v) scrollDown();
  }

  function api(method, url, body) {
    var opts = { method: method, credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (body) { opts.headers['Content-Type'] = 'application/json'; opts.headers['X-CSRF-Token'] = csrf; opts.body = JSON.stringify(body); }
    return fetch(url, opts).then(function (r) {
      return r.json().catch(function () { return { ok: false, error: 'Resposta inesperada do servidor.' }; })
        .then(function (j) { j.__status = r.status; return j; });
    });
  }

  function currentView() {
    try {
      var v = new URLSearchParams(window.location.search).get('view');
      if (v === 'day' || v === 'week' || v === 'month') return v;
      return window.location.pathname === '/schedule' ? 'week' : '';
    } catch (e) { return ''; }
  }

  function showResolve(v) { resolveBox.classList.toggle('lc-sc-on', !!v); }

  function showChips(list) {
    chipsBox.textContent = '';
    (list || []).forEach(function (t) {
      var b = document.createElement('button');
      b.type = 'button'; b.className = 'lc-sc-chip'; b.textContent = t;
      b.addEventListener('click', function () { send(t, false); });
      chipsBox.appendChild(b);
    });
  }

  function boot() {
    if (booted) return;
    booted = true;
    api('GET', '/support/chat/bootstrap?page_path=' + encodeURIComponent(window.location.pathname)).then(function (j) {
      if (!j || !j.ok) { booted = false; addMsg('err', (j && j.error) || 'Não foi possível carregar o assistente.', false); return; }
      if (j.csrf) csrf = j.csrf;
      if (j.max_chars) { maxChars = j.max_chars; input.setAttribute('maxlength', String(maxChars)); }
      greeting = j.greeting || '';
      if (j.whatsapp_url && String(j.whatsapp_url).indexOf('https://wa.me/') === 0) { waLink.setAttribute('href', j.whatsapp_url); waLink.hidden = false; }
      if (!convId) { addMsg('bot', greeting, false); showChips(j.chips); }
      else { showChips(j.chips); loadHistory(); }
      updateCount();
    }).catch(function () { booted = false; addMsg('err', 'Sem conexão com o servidor. Tente novamente.', false); });
  }

  function loadHistory() {
    api('GET', '/support/chat/history?conversation_id=' + encodeURIComponent(convId)).then(function (j) {
      if (!j || !j.ok) { convId = null; saveConv(); addMsg('bot', greeting, false); return; }
      msgs.textContent = '';
      if (!j.messages || !j.messages.length) addMsg('bot', greeting, false);
      (j.messages || []).forEach(function (m) { addMsg(m.role === 'user' ? 'user' : 'bot', m.content, m.role !== 'user'); });
      var last = j.messages && j.messages.length ? j.messages[j.messages.length - 1] : null;
      if (j.status && j.status !== 'open') { convId = null; saveConv(); showResolve(false); }
      else { chipsBox.textContent = ''; showResolve(!!last && last.role !== 'user'); }
    }).catch(function () {});
  }

  function send(text, force) {
    text = String(text || '').trim();
    if (!text || busy) return;
    chipsBox.textContent = '';
    showResolve(false);
    addMsg('user', text, false);
    input.value = ''; autoGrow(); updateCount();
    setBusy(true);
    var body = { message: text, page_path: window.location.pathname, page_view: currentView(), force_ticket: !!force, client_time: new Date().toISOString() };
    if (convId) body.conversation_id = convId;
    api('POST', '/support/chat/message', body).then(function (j) {
      setBusy(false);
      if (!j || !j.ok) {
        if (j && j.__status === 404 && convId) { convId = null; saveConv(); }
        addMsg('err', (j && j.error) || 'Não foi possível enviar sua mensagem.', false);
        return;
      }
      convId = j.conversation_id || convId; saveConv();
      addMsg('bot', j.reply, true);
      if (j.action === 'create_ticket') { convId = null; saveConv(); }
      else if (j.action === 'answer' || j.action === 'ask_details') showResolve(true);
      input.focus();
    }).catch(function () {
      setBusy(false);
      addMsg('err', 'Sem conexão com o servidor. Tente novamente.', false);
    });
  }

  function newConversation() {
    if (busy) return;
    convId = null; saveConv();
    msgs.textContent = '';
    showResolve(false);
    addMsg('bot', greeting || 'Olá! Como posso ajudar?', false);
    api('GET', '/support/chat/bootstrap?page_path=' + encodeURIComponent(window.location.pathname)).then(function (j) { if (j && j.ok) showChips(j.chips); }).catch(function () {});
    input.focus();
  }

  function autoGrow() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 96) + 'px'; }
  function updateCount() { countEl.textContent = input.value.length + '/' + maxChars; }

  function openPanel() {
    panel.hidden = false;
    panel.classList.add('lc-sc-open');
    fab.setAttribute('aria-expanded', 'true');
    fab.setAttribute('aria-label', 'Fechar assistente de suporte');
    boot();
    window.setTimeout(function () { input.focus(); }, 30);
  }
  function closePanel() {
    panel.classList.remove('lc-sc-open');
    panel.hidden = true;
    fab.setAttribute('aria-expanded', 'false');
    fab.setAttribute('aria-label', 'Abrir assistente de suporte');
    fab.focus();
  }

  fab.addEventListener('click', function () { if (panel.classList.contains('lc-sc-open')) closePanel(); else openPanel(); });
  $('lc-sc-close').addEventListener('click', closePanel);
  $('lc-sc-new').addEventListener('click', newConversation);
  yesBtn.addEventListener('click', function () {
    if (busy) return;
    showResolve(false);
    addMsg('user', 'Sim', false);
    addMsg('bot', 'Que bom que deu certo! Se surgir outra dúvida, é só escrever aqui.', false);
    input.focus();
  });
  // O texto do botão não é a descrição do problema: o servidor o ignora na contagem e faz a triagem antes do chamado.
  noBtn.addEventListener('click', function () { send(ESCALATE_TEXT, true); });
  form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value, false); });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); send(input.value, false); }
  });
  input.addEventListener('input', function () { autoGrow(); updateCount(); });
  panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') { e.stopPropagation(); closePanel(); } });
})();
</script>
