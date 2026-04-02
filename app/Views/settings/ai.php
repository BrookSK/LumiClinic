<?php
$title = 'Configurações - IA';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$openai_key_set = $openai_key_set ?? false;

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};
ob_start();
?>

<a href="/settings" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para configurações
</a>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <span style="font-size:24px;">🤖</span>
        <div>
            <div style="font-weight:850;font-size:18px;">Inteligência Artificial</div>
            <div style="font-size:13px;color:rgba(31,41,55,.50);">Integração com OpenAI para transcrição e assistente de prontuário.</div>
        </div>
    </div>

    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid <?= $openai_key_set ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;background:<?= $openai_key_set ? 'rgba(22,163,74,.06)' : 'rgba(107,114,128,.04)' ?>;margin-bottom:16px;">
        <span style="font-size:16px;"><?= $openai_key_set ? '✅' : '⚠️' ?></span>
        <span style="font-weight:700;font-size:13px;color:<?= $openai_key_set ? '#16a34a' : '#6b7280' ?>;"><?= $openai_key_set ? 'Chave configurada' : 'Sem chave configurada' ?></span>
    </div>

    <?php if ($can('settings.update')): ?>
        <form method="post" class="lc-form" action="/settings/ai">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="lc-field">
                <label class="lc-label">Chave da API OpenAI</label>
                <input class="lc-input" type="password" name="openai_api_key" placeholder="sk-..." autocomplete="off" style="max-width:500px;" />
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Salva criptografada. Não exibimos o valor após salvar.</div>
            </div>
            <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar chave</button>
                <form method="post" action="/settings/ai/test" style="margin:0;display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Testar conexão</button>
                </form>
            </div>
        </form>

        <details style="margin-top:14px;">
            <summary style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;padding:6px 0;">Remover chave</summary>
            <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
                <div style="font-size:12px;color:rgba(185,28,28,.70);margin-bottom:8px;">Isso vai desativar a IA para esta clínica.</div>
                <form method="post" action="/settings/ai/clear" style="margin:0;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit" onclick="return confirm('Remover a chave de IA?');">Confirmar remoção</button>
                </form>
            </div>
        </details>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
