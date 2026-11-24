<?php
$title = 'Admin - Assistente de suporte';
$csrf = $_SESSION['_csrf'] ?? '';

$settings = isset($settings) && is_array($settings) ? $settings : [];
$enabled = !empty($settings['enabled']);
$model = (string)($settings['model'] ?? 'gpt-4o-mini');
$apiKeySet = !empty($settings['api_key_set']);
$emailFallback = !isset($settings['email_fallback']) || !empty($settings['email_fallback']);
$globalKeySet = !empty($global_key_set);
$envKeySet = !empty($env_key_set);
$success = isset($success) ? (string)$success : '';
$error = isset($error) ? (string)$error : '';

$e = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Assistente de suporte</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/support/tickets">Chamados</a>
    </div>
</div>

<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= $e($success) ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= $e($error) ?></div>
<?php endif; ?>

<div class="lc-card lc-card--soft">
    <div class="lc-card__header">
        <div class="lc-card__title">Chatbot de suporte e chamados</div>
    </div>
    <div class="lc-card__body">
        <form method="post" action="/sys/support/settings" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />

            <div class="lc-field" style="grid-column:1 / -1;">
                <label class="lc-label">
                    <input type="checkbox" name="bot_enabled" value="1"<?= $enabled ? ' checked' : '' ?> />
                    Ativar o assistente de suporte para as clínicas
                </label>
                <div class="lc-muted" style="font-size:12px;margin-top:6px;">Quando desativado, as clínicas continuam vendo apenas o botão de contato por WhatsApp.</div>
            </div>

            <div class="lc-field">
                <label class="lc-label">Modelo da OpenAI</label>
                <input class="lc-input" type="text" name="bot_model" value="<?= $e($model) ?>" placeholder="gpt-4o-mini" maxlength="64" pattern="[A-Za-z0-9._:\-]{1,64}" />
                <div class="lc-muted" style="font-size:12px;margin-top:6px;">Padrão: gpt-4o-mini (bom custo-benefício). Use apenas letras, números, ponto, hífen e dois-pontos.</div>
            </div>

            <div class="lc-field">
                <label class="lc-label">Chave da OpenAI exclusiva do suporte (opcional)</label>
                <input class="lc-input" type="password" name="bot_api_key" value="" autocomplete="new-password" placeholder="<?= $apiKeySet ? 'Já configurada (deixe em branco para manter)' : 'sk-...' ?>" />
                <?php if ($apiKeySet): ?>
                    <label style="display:block;margin-top:6px;font-size:12px;">
                        <input type="checkbox" name="clear_api_key" value="1" /> Remover a chave salva
                    </label>
                <?php endif; ?>
                <div class="lc-muted" style="font-size:12px;margin-top:6px;">
                    Ordem de uso: 1) esta chave; 2) chave global de IA (<a href="/sys/settings/ai">IA (OpenAI)</a>)<?= $globalKeySet ? ' (configurada)' : ' (não configurada)' ?>;
                    3) variável de ambiente OPENAI_API_KEY<?= $envKeySet ? ' (definida)' : ' (não definida)' ?>.
                    O assistente nunca usa a chave nem o saldo de IA das clínicas.
                    A chave fica salva na tabela de configurações do sistema, como as demais, e não é exibida novamente.
                </div>
            </div>

            <div class="lc-field" style="grid-column:1 / -1;">
                <label class="lc-label">
                    <input type="checkbox" name="email_fallback" value="1"<?= $emailFallback ? ' checked' : '' ?> />
                    Avisar por e-mail quando o WhatsApp não puder ser usado
                </label>
                <div class="lc-muted" style="font-size:12px;margin-top:6px;">Usa os e-mails cadastrados em <a href="/sys/settings/dev-alerts">Alertas de erro</a>.</div>
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column:1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
