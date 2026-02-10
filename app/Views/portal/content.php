<?php
$title = 'Conteúdos';
$csrf = $_SESSION['_csrf'] ?? '';
$items = $items ?? [];
ob_start();
?>

    <div class="lc-card" style="margin-top:16px; padding:16px;">
        <div class="lc-card__title">Disponíveis</div>
        <div class="lc-card__body">
            <?php if (!is_array($items) || $items === []): ?>
                <div>Nenhum conteúdo disponível.</div>
            <?php else: ?>
                <div class="lc-grid" style="gap:10px;">
                    <?php foreach ($items as $c): ?>
                        <div class="lc-card" style="padding:12px;">
                            <div><strong><?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                            <div><?= htmlspecialchars((string)($c['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (isset($c['url']) && (string)$c['url'] !== ''): ?>
                                <div style="margin-top:8px;">
                                    <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars((string)$c['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">Abrir</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php
$portal_content = (string)ob_get_clean();
$portal_active = null;
require __DIR__ . '/_shell.php';
