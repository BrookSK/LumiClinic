<?php
$title = 'Billing Event';
$csrf = $_SESSION['_csrf'] ?? '';
$row = $row ?? null;
$ok = isset($ok) ? (string)$ok : '';
$error = isset($error) ? (string)$error : '';

if (!is_array($row)) {
    $row = [];
}

$payload = $row['payload_json'] ?? null;
if (is_string($payload)) {
    $decoded = json_decode($payload, true);
    $payload = is_array($decoded) ? $decoded : $payload;
}

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Billing Event #<?= (int)($row['id'] ?? 0) ?></div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/sys/billing-events">Voltar</a>
    </div>
</div>

<?php if ($ok !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Resumo</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr;">
            <div>
                <div class="lc-muted" style="font-size:12px;">Provider</div>
                <div><?= htmlspecialchars((string)($row['provider'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Event type</div>
                <div><?= htmlspecialchars((string)($row['event_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">External ID</div>
                <div><?= htmlspecialchars((string)($row['external_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-muted" style="font-size:12px;">Processed at</div>
                <div><?= htmlspecialchars((string)($row['processed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <form method="post" action="/sys/billing-events/reprocess" style="margin:0;" onsubmit="return confirm('Reprocessar este evento?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Reprocessar</button>
            </form>
        </div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Payload</div>
    <div class="lc-card__body">
        <pre style="white-space:pre-wrap; margin:0;"><?= htmlspecialchars(is_array($payload) ? json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string)$payload, ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
