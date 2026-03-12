<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';

$evolution_base_url = isset($evolution_base_url) ? (string)$evolution_base_url : '';
$success = isset($success) ? (string)$success : '';
$error = isset($error) ? (string)$error : '';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Configurações (WhatsApp)</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/billing">Assinaturas</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/billing">Cobrança</a>
    </div>
</div>

<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header">
        <div class="lc-card__title">Evolution API</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/whatsapp" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Base URL</label>
                <input class="lc-input" type="text" name="evolution_base_url" value="<?= htmlspecialchars($evolution_base_url, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://seu-host:porta" />
                <div class="lc-muted" style="margin-top:6px;">Ex.: <code>http://127.0.0.1:8080</code> ou <code>https://evolution.seudominio.com</code></div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>

        <div class="lc-alert lc-alert--info" style="margin-top:12px;">
            <div style="font-weight:700; margin-bottom:4px;">Observação</div>
            <div>A configuração aqui é global (Super Admin). As clínicas não precisam informar a Base URL.</div>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
