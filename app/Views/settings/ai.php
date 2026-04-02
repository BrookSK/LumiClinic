<?php
$title = 'Configurações - IA';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$success = $success ?? null;
$openai_key_set = $openai_key_set ?? false;
$global_key = $global_key ?? false;

$can = function (string $pc): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $p = $_SESSION['permissions'] ?? [];
    if (!is_array($p)) return false;
    if (isset($p['allow'],$p['deny'])&&is_array($p['allow'])&&is_array($p['deny'])) {
        if (in_array($pc,$p['deny'],true)) return false;
        return in_array($pc,$p['allow'],true);
    }
    return in_array($pc,$p,true);
};
ob_start();
?>

<a href="/settings" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para configurações
</a>

<?php if ($error): ?><div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($success): ?><div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <span style="font-size:24px;">🤖</span>
        <div>
            <div style="font-weight:850;font-size:18px;">Inteligência Artificial</div>
            <div style="font-size:13px;color:rgba(31,41,55,.50);">Transcrição de áudio no prontuário e geração de feriados.</div>
        </div>
    </div>

    <!-- Status -->
    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid <?= $openai_key_set ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;background:<?= $openai_key_set ? 'rgba(22,163,74,.06)' : 'rgba(107,114,128,.04)' ?>;margin-bottom:16px;">
        <span style="font-size:16px;"><?= $openai_key_set ? '✅' : '⚠️' ?></span>
        <span style="font-weight:700;font-size:13px;color:<?= $openai_key_set ? '#16a34a' : '#6b7280' ?>;">
            <?php if ($global_key): ?>
                IA ativa (configuração global do sistema)
            <?php elseif ($openai_key_set): ?>
                Chave própria configurada
            <?php else: ?>
                IA não configurada
            <?php endif; ?>
        </span>
    </div>

    <?php if ($global_key): ?>
        <!-- Key global ativa — clínica não precisa fazer nada -->
        <div style="font-size:13px;color:rgba(31,41,55,.60);line-height:1.5;">
            A IA está configurada globalmente pelo administrador do sistema. Você não precisa configurar nada. A transcrição de áudio e a geração de feriados já estão disponíveis.
        </div>
        <div style="margin-top:12px;font-size:12px;color:rgba(31,41,55,.40);">
            O limite de uso depende do seu plano de assinatura.
        </div>
    <?php else: ?>
        <!-- Sem key global — clínica configura a própria -->
        <?php if ($can('settings.update')): ?>
            <form method="post" action="/settings/ai">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="lc-field">
                    <label class="lc-label">Chave da API OpenAI</label>
                    <input class="lc-input" type="password" name="openai_api_key" placeholder="sk-..." autocomplete="off" style="max-width:500px;" />
                    <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Salva criptografada.</div>
                </div>
                <div style="display:flex;gap:10px;margin-top:14px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                </div>
            </form>

            <?php if ($openai_key_set): ?>
            <details style="margin-top:14px;">
                <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;">Remover chave</summary>
                <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
                    <form method="post" action="/settings/ai/clear" style="margin:0;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit" onclick="return confirm('Remover?');">Confirmar</button>
                    </form>
                </div>
            </details>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
