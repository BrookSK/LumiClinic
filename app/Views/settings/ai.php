<?php
$title = 'Configurações - IA';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$openai_key_set = $openai_key_set ?? false;

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">IA (ChatGPT/OpenAI)</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="lc-card__body">
        <div class="lc-muted" style="margin-bottom:10px;">
            A chave é salva criptografada por clínica. Não exibimos o valor após salvar.
        </div>

        <?php if ($can('settings.update')): ?>
            <form method="post" class="lc-form" action="/settings/ai">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Status</label>
            <div class="lc-badge <?= $openai_key_set ? 'lc-badge--success' : 'lc-badge--secondary' ?>">
                <?= $openai_key_set ? 'Chave configurada' : 'Sem chave' ?>
            </div>

            <label class="lc-label" style="margin-top:12px;">OpenAI API key</label>
            <input class="lc-input" type="password" name="openai_api_key" placeholder="sk-..." autocomplete="off" />

                <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                    <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
                </div>
            </form>
        <?php else: ?>
            <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
                <a class="lc-btn lc-btn--secondary" href="/settings">Voltar</a>
            </div>
        <?php endif; ?>

        <?php if ($can('settings.update')): ?>
            <form method="post" class="lc-form" action="/settings/ai/test" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Testar IA</button>
            </form>
        <?php endif; ?>

        <?php if ($can('settings.update')): ?>
            <form method="post" class="lc-form" action="/settings/ai/clear" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--danger" type="submit" onclick="return confirm('Remover a chave de IA desta clínica?');">Remover chave</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
