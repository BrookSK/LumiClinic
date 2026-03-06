<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';

$webpush_public_key = isset($webpush_public_key) ? (string)$webpush_public_key : '';
$webpush_private_key = isset($webpush_private_key) ? (string)$webpush_private_key : '';
$webpush_subject = isset($webpush_subject) ? (string)$webpush_subject : '';
$success = isset($success) ? (string)$success : '';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Configurações (Web Push)</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/mail">E-mail</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/settings/support">Suporte</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header">
        <div class="lc-card__title">VAPID / WebPush</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/settings/webpush/generate" class="lc-form" style="margin-bottom:14px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary" type="submit">Gerar novas chaves VAPID</button>
        </form>

        <form method="post" action="/sys/settings/webpush" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Public key</label>
                <input class="lc-input" type="text" name="webpush_public_key" value="<?= htmlspecialchars($webpush_public_key, ENT_QUOTES, 'UTF-8') ?>" placeholder="B..." />
                <div class="lc-muted" style="font-size:12px; margin-top:6px;">Usada no navegador (frontend/service worker) para criar a subscription.</div>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Private key</label>
                <input class="lc-input" type="password" name="webpush_private_key" value="<?= htmlspecialchars($webpush_private_key, ENT_QUOTES, 'UTF-8') ?>" placeholder="(mantenha em segredo)" />
                <div class="lc-muted" style="font-size:12px; margin-top:6px;">Fica no servidor e é usada para assinar o envio.</div>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Subject</label>
                <input class="lc-input" type="text" name="webpush_subject" value="<?= htmlspecialchars($webpush_subject, ENT_QUOTES, 'UTF-8') ?>" placeholder="mailto:suporte@seudominio.com" />
                <div class="lc-muted" style="font-size:12px; margin-top:6px;">Recomendado usar mailto: (contato do admin).</div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
