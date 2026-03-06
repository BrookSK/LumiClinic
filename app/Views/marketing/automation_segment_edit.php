<?php
/** @var array<string,mixed> $row */
/** @var array<string,mixed> $rules */
/** @var int $audience_count */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação de Marketing - Segmento';

$row = $row ?? [];
$rules = $rules ?? [];
$audience_count = isset($audience_count) ? (int)$audience_count : 0;

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$id = (int)($row['id'] ?? 0);
$name = (string)($row['name'] ?? '');
$status = (string)($row['status'] ?? 'active');

$ruleStatus = (string)($rules['status'] ?? 'active');
$ruleWa = (int)($rules['whatsapp_opt_in'] ?? 1);
$rulePhone = (int)($rules['has_phone'] ?? 1);
$ruleEmail = (int)($rules['has_email'] ?? 0);

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Editar segmento</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/marketing/automation/segments">Voltar</a>
        <a class="lc-btn lc-btn--secondary" href="/marketing/automation/campaigns">Campanhas</a>
        <a class="lc-btn lc-btn--secondary" href="/marketing/automation/logs">Logs</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Cadastro</div>
    <div class="lc-card__body">
        <form method="post" action="/marketing/automation/segment/update" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= $id ?>" />

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 180px; align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Nome</label>
                    <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Status</label>
                    <select class="lc-select" name="status">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Desativado</option>
                    </select>
                </div>
            </div>

            <div class="lc-card lc-card--soft" style="margin-top:14px;">
                <div class="lc-card__header">Regras (MVP)</div>
                <div class="lc-card__body">
                    <div class="lc-grid lc-gap-grid" style="grid-template-columns: 180px 1fr; align-items:center;">
                        <div class="lc-field">
                            <label class="lc-label">Status do paciente</label>
                            <select class="lc-select" name="rule_status">
                                <option value="active" <?= $ruleStatus === 'active' ? 'selected' : '' ?>>Ativo</option>
                                <option value="inactive" <?= $ruleStatus === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                            </select>
                        </div>

                        <div class="lc-field">
                            <label class="lc-label">Restrições</label>
                            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:6px;">
                                <label class="lc-checkbox"><input type="checkbox" name="rule_whatsapp_opt_in" value="1" <?= $ruleWa === 1 ? 'checked' : '' ?> /> WhatsApp opt-in</label>
                                <label class="lc-checkbox"><input type="checkbox" name="rule_has_phone" value="1" <?= $rulePhone === 1 ? 'checked' : '' ?> /> Tem telefone</label>
                                <label class="lc-checkbox"><input type="checkbox" name="rule_has_email" value="1" <?= $ruleEmail === 1 ? 'checked' : '' ?> /> Tem e-mail</label>
                            </div>
                        </div>
                    </div>

                    <div class="lc-muted" style="margin-top:10px;">Audiência estimada: <strong><?= (int)$audience_count ?></strong> paciente(s).</div>
                </div>
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/marketing/automation/segments">Voltar</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
