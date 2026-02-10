<?php
$title = 'Notificações';
$csrf = $_SESSION['_csrf'] ?? '';
$notifications = $notifications ?? [];
ob_start();
?>

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

<?php
$portal_content = (string)ob_get_clean();
$portal_active = 'notificacoes';
require __DIR__ . '/_shell.php';
