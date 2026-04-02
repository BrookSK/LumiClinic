<?php
$title = 'Admin - IA (OpenAI)';
$csrf = $_SESSION['_csrf'] ?? '';
$key_set = isset($key_set) ? (bool)$key_set : false;
$success = isset($success) ? (string)$success : '';

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">IA (OpenAI) - Global</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Chave global usada por todas as clínicas para transcrição de áudio e geração de feriados.</div>
    </div>
    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/settings/billing">Configurações</a>
</div>

<?php if ($success !== ''): ?><div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid <?= $key_set ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;background:<?= $key_set ? 'rgba(22,163,74,.06)' : 'rgba(107,114,128,.04)' ?>;margin-bottom:16px;">
    <span style="font-size:16px;"><?= $key_set ? '✅' : '⚠️' ?></span>
    <span style="font-weight:700;font-size:13px;color:<?= $key_set ? '#16a34a' : '#6b7280' ?>;"><?= $key_set ? 'Chave global configurada' : 'Sem chave global' ?></span>
</div>

<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:600px;margin-bottom:16px;">
    <form method="post" action="/sys/settings/ai">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-field">
            <label class="lc-label">Chave da API OpenAI</label>
            <input class="lc-input" type="password" name="openai_api_key" placeholder="<?= $key_set ? 'Já configurada (deixe vazio para manter)' : 'sk-...' ?>" autocomplete="off" />
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Quando configurada, todas as clínicas usam esta chave automaticamente. As clínicas não precisam configurar a própria.</div>
        </div>

        <?php if ($key_set): ?>
        <div class="lc-field">
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(31,41,55,.55);cursor:pointer;">
                <input type="checkbox" name="clear_key" value="1" style="width:16px;height:16px;" />
                Remover chave global (clínicas voltam a usar a própria)
            </label>
        </div>
        <?php endif; ?>

        <div style="margin-top:14px;"><button class="lc-btn lc-btn--primary" type="submit">Salvar</button></div>
    </form>
</div>

<div style="padding:14px 16px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.10);font-size:13px;color:rgba(31,41,55,.70);line-height:1.5;max-width:600px;">
    A IA é usada para: transcrição de áudio no prontuário (Whisper) e geração automática de feriados. O limite de transcrição por clínica é configurado no plano (campo "Limite de transcrição").
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
