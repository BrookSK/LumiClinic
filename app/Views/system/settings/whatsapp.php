<?php
$title = 'Admin - WhatsApp (Global)';
$csrf = $_SESSION['_csrf'] ?? '';
$evolution_base_url = isset($evolution_base_url) ? (string)$evolution_base_url : '';
$evolution_token_set = isset($evolution_token_set) ? (bool)$evolution_token_set : false;
$success = isset($success) ? (string)$success : '';
$error = isset($error) ? (string)$error : '';

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">WhatsApp (Global)</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Configuração da Evolution API usada por todas as clínicas.</div>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/settings/billing">Cobrança</a>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<?php if ($success !== ''): ?><div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<!-- Status -->
<div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid <?= ($evolution_base_url !== '' && $evolution_token_set) ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;background:<?= ($evolution_base_url !== '' && $evolution_token_set) ? 'rgba(22,163,74,.06)' : 'rgba(107,114,128,.04)' ?>;margin-bottom:16px;">
    <span style="font-size:16px;"><?= ($evolution_base_url !== '' && $evolution_token_set) ? '✅' : '⚠️' ?></span>
    <span style="font-weight:700;font-size:13px;color:<?= ($evolution_base_url !== '' && $evolution_token_set) ? '#16a34a' : '#6b7280' ?>;"><?= ($evolution_base_url !== '' && $evolution_token_set) ? 'Configurado' : 'Não configurado' ?></span>
</div>

<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:600px;">
    <form method="post" action="/sys/settings/whatsapp">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-field">
            <label class="lc-label">Base URL da Evolution API</label>
            <input class="lc-input" type="text" name="evolution_base_url" value="<?= htmlspecialchars($evolution_base_url, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://evolution.seudominio.com" />
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Ex: http://127.0.0.1:8080 ou https://evolution.seudominio.com</div>
        </div>

        <div class="lc-field">
            <label class="lc-label">Token (API Key)</label>
            <input class="lc-input" type="password" name="evolution_token" placeholder="<?= $evolution_token_set ? 'Já configurado (deixe vazio para manter)' : 'Cole o token aqui' ?>" autocomplete="off" />
        </div>

        <?php if ($evolution_token_set): ?>
        <div class="lc-field">
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(31,41,55,.55);cursor:pointer;">
                <input type="checkbox" name="clear_evolution_token" value="1" style="width:16px;height:16px;" />
                Remover token salvo
            </label>
        </div>
        <?php endif; ?>

        <div style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        </div>
    </form>

    <div style="margin-top:14px;padding:12px;border-radius:10px;border:1px solid rgba(238,184,16,.18);background:rgba(253,229,159,.08);font-size:12px;color:rgba(31,41,55,.55);line-height:1.5;">
        Esta configuração é global. As clínicas só precisam informar o nome da instância e a API Key delas em Configurações → WhatsApp.
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
