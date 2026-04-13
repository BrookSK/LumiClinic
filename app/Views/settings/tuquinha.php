<?php
$title = 'Integração Tuquinha';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? '';
$success = $success ?? '';
$tuquinhaKey = $tuquinha_api_key ?? '';
$connected = trim($tuquinhaKey) !== '';

ob_start();
?>

<style>
.tuq-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.tuq-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.tuq-section__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px}
.tuq-section__desc{font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;line-height:1.5}
</style>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="tuq-head">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">🔌 Calendário de Marketing — Tuquinha</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Sincronize eventos do calendário de marketing com o Tuquinha</div>
    </div>
    <a class="lc-btn lc-btn--secondary" href="/settings">← Voltar</a>
</div>

<!-- Conexão -->
<div class="tuq-section">
    <div class="tuq-section__title">Conexão com a API</div>
    <div class="tuq-section__desc">
        Gere uma API Key no painel do <a href="https://tuquinha.onsolutionsbrasil.com.br" target="_blank" style="color:rgba(99,102,241,.85);">Tuquinha</a>
        (menu → Integração via API → Gerar chave) e cole abaixo.
    </div>

    <form method="post" action="/marketing/calendar/tuquinha-config" class="lc-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <div class="lc-flex lc-gap-sm" style="align-items:end;">
            <div class="lc-field" style="flex:1;max-width:420px;">
                <label class="lc-label">API Key</label>
                <input class="lc-input" type="text" name="tuquinha_api_key" value="<?= htmlspecialchars($tuquinhaKey, ENT_QUOTES, 'UTF-8') ?>" placeholder="tuq_sua_chave_aqui" />
            </div>
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit"><?= $connected ? 'Atualizar chave' : 'Conectar' ?></button>
        </div>
        <?php if ($connected): ?>
            <div style="font-size:12px;color:#16a34a;margin-top:6px;font-weight:600;">✓ Conectado</div>
        <?php else: ?>
            <div style="font-size:11px;color:#9ca3af;margin-top:6px;">Após conectar, você poderá importar e enviar eventos.</div>
        <?php endif; ?>
    </form>
</div>

<?php if ($connected): ?>
<!-- Sincronização -->
<div class="tuq-section">
    <div class="tuq-section__title">Sincronizar eventos</div>
    <div class="tuq-section__desc">Escolha o mês e importe eventos do Tuquinha para o calendário local, ou envie eventos locais para o Tuquinha.</div>

    <div class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:end;">
        <div class="lc-field">
            <label class="lc-label">Mês</label>
            <input class="lc-input" type="month" id="tuq_sync_month" value="<?= date('Y-m') ?>" style="width:170px;" />
        </div>
        <form method="post" action="/marketing/calendar/tuquinha-sync" id="tuqPullForm" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="month" id="tuqPullMonth" value="<?= date('Y-m') ?>-01" />
            <input type="hidden" name="direction" value="pull" />
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">⬇️ Importar do Tuquinha</button>
        </form>
        <form method="post" action="/marketing/calendar/tuquinha-sync" id="tuqPushForm" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="month" id="tuqPushMonth" value="<?= date('Y-m') ?>-01" />
            <input type="hidden" name="direction" value="push" />
            <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">⬆️ Enviar para o Tuquinha</button>
        </form>
    </div>

    <div style="margin-top:14px;padding:12px;border-radius:10px;background:rgba(0,0,0,.02);border:1px solid rgba(0,0,0,.06);">
        <div style="font-weight:600;font-size:12px;color:#6b7280;margin-bottom:6px;">Como funciona</div>
        <div style="font-size:12px;color:#6b7280;line-height:1.6;">
            <strong>⬇️ Importar:</strong> Traz os eventos do mês selecionado do Tuquinha para o calendário de marketing local. Eventos já importados são atualizados.<br/>
            <strong>⬆️ Enviar:</strong> Cria no Tuquinha os eventos locais do mês que ainda não foram sincronizados (eventos importados do Tuquinha não são reenviados).
        </div>
    </div>

    <script>
    (function(){
        var m = document.getElementById('tuq_sync_month');
        if (!m) return;
        m.addEventListener('change', function(){
            var ymd = m.value + '-01';
            var a = document.getElementById('tuqPullMonth');
            var b = document.getElementById('tuqPushMonth');
            if (a) a.value = ymd;
            if (b) b.value = ymd;
        });
    })();
    </script>
</div>

<!-- Documentação -->
<div class="tuq-section">
    <div class="tuq-section__title">Documentação da API</div>
    <div class="tuq-section__desc">Referência rápida dos endpoints do Tuquinha usados na integração.</div>

    <div style="font-size:12px;color:#6b7280;line-height:1.8;font-family:monospace;">
        <div><strong>Base URL:</strong> https://tuquinha.onsolutionsbrasil.com.br</div>
        <div><strong>Auth:</strong> Authorization: Bearer tuq_sua_chave</div>
        <div style="margin-top:8px;"><strong>GET</strong> /api/marketing-calendar/events?year=2026&month=4</div>
        <div><strong>GET</strong> /api/marketing-calendar/events/show?id=ID</div>
        <div><strong>POST</strong> /api/marketing-calendar/events — criar evento</div>
        <div><strong>POST</strong> /api/marketing-calendar/events/update — atualizar evento</div>
        <div><strong>POST</strong> /api/marketing-calendar/events/delete — excluir evento</div>
        <div style="margin-top:8px;">Tipos: post, story, reels, video, email, anuncio, outro</div>
        <div>Status: planejado, produzido, postado</div>
    </div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
