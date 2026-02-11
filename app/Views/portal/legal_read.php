<?php
$title = 'Ler documento';
$doc = $doc ?? null;
ob_start();
?>
<div class="lc-card" style="margin-top:16px; padding:16px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($doc['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body" style="white-space:pre-wrap; line-height:1.6;">
        <?= nl2br(htmlspecialchars((string)($doc['body'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
    </div>
</div>
<?php
$portal_content = (string)ob_get_clean();
$portal_active = null;
require __DIR__ . '/_shell.php';
