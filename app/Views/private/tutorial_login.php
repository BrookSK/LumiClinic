<?php
$title = 'Tutorial Privado';
ob_start();
$config = $GLOBALS['container']->get('config');
$csrfKey = is_array($config) && isset($config['csrf']['token_key']) ? (string)$config['csrf']['token_key'] : '_csrf';
$csrf = isset($_SESSION[$csrfKey]) ? (string)$_SESSION[$csrfKey] : '';
?>
<div class="lc-grid">
    <div class="lc-card">
        <div class="lc-card__title">Acesso privado</div>
        <div class="lc-card__body">
            <?php if (!empty($error)): ?>
                <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= htmlspecialchars((string)$target) ?>">
                <input type="hidden" name="<?= htmlspecialchars($csrfKey) ?>" value="<?= htmlspecialchars($csrf) ?>">
                <div class="lc-field">
                    <label class="lc-label">Senha</label>
                    <input class="lc-input" type="password" name="password" required>
                </div>
                <div style="margin-top: 12px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Entrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
