<?php
/** @var array<string,mixed> $row */
/** @var list<array<string,mixed>> $segments */
/** @var list<array<string,mixed>> $templates */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Automação de Marketing - Campanha';

$row = $row ?? [];
$segments = $segments ?? [];
$templates = $templates ?? [];

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$id = (int)($row['id'] ?? 0);
$name = (string)($row['name'] ?? '');
$channel = (string)($row['channel'] ?? 'whatsapp');
$status = (string)($row['status'] ?? 'draft');
$segmentId = (int)($row['segment_id'] ?? 0);
$scheduledFor = (string)($row['scheduled_for'] ?? '');
$scheduledForLocal = '';
if (trim($scheduledFor) !== '') {
    // MySQL DATETIME -> datetime-local
    $scheduledForLocal = str_replace(' ', 'T', substr($scheduledFor, 0, 16));
}
$triggerEvent = (string)($row['trigger_event'] ?? '');
$triggerDelay = (string)($row['trigger_delay_minutes'] ?? '');
$waTpl = (string)($row['whatsapp_template_code'] ?? '');
$emailSubject = (string)($row['email_subject'] ?? '');
$emailBody = (string)($row['email_body'] ?? '');
$clickUrl = (string)($row['click_url'] ?? '');

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

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Editar campanha</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/marketing/automation/campaigns">Voltar</a>
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
        <?php if ($can('marketing.automation.manage')): ?>
            <form method="post" action="/marketing/automation/campaign/update" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= $id ?>" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 160px 1fr; align-items:end;">
                    <div class="lc-field">
                        <label class="lc-label">Nome</label>
                        <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required />
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Canal</label>
                        <select class="lc-select" name="channel">
                            <option value="whatsapp" <?= $channel === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                            <option value="email" <?= $channel === 'email' ? 'selected' : '' ?>>E-mail</option>
                        </select>
                    </div>

                    <div class="lc-field">
                        <label class="lc-label">Status</label>
                        <select class="lc-select" name="status">
                            <?php foreach (['draft'=>'Rascunho','scheduled'=>'Agendada','running'=>'Rodando','paused'=>'Pausada','completed'=>'Concluída','cancelled'=>'Cancelada'] as $k=>$lbl): ?>
                                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lc-field" style="grid-column:1 / -1;">
                        <label class="lc-label">Segmento (opcional)</label>
                        <select class="lc-select" name="segment_id">
                            <option value="">(todos)</option>
                            <?php foreach ($segments as $s): ?>
                                <?php $sid = (int)($s['id'] ?? 0); if ($sid <= 0) continue; ?>
                                <option value="<?= $sid ?>" <?= $segmentId === $sid ? 'selected' : '' ?>><?= htmlspecialchars((string)($s['name'] ?? ('Segmento #' . $sid)), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="lc-field" style="grid-column:1 / -1;">
                        <label class="lc-label">Agendar para (opcional)</label>
                        <input class="lc-input" type="datetime-local" name="scheduled_for" value="<?= htmlspecialchars($scheduledForLocal, ENT_QUOTES, 'UTF-8') ?>" />
                    </div>

                    <div class="lc-field" style="grid-column:1 / -1;">
                        <label class="lc-label">Trigger por evento (opcional)</label>
                        <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 200px; align-items:end;">
                            <div>
                                <select class="lc-select" name="trigger_event">
                                    <option value="" <?= $triggerEvent === '' ? 'selected' : '' ?>>(nenhum)</option>
                                    <?php foreach (['appointment.completed'=>'Agendamento concluído','appointment.no_show'=>'Faltou (no-show)','appointment.cancelled'=>'Cancelado','appointment.confirmed'=>'Confirmado'] as $k=>$lbl): ?>
                                        <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $triggerEvent === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="lc-label">Delay (min)</label>
                                <input class="lc-input" type="number" name="trigger_delay_minutes" value="<?= htmlspecialchars($triggerDelay, ENT_QUOTES, 'UTF-8') ?>" min="0" />
                            </div>
                        </div>
                    </div>

                    <div class="lc-field" style="grid-column:1 / -1;">
                        <label class="lc-label">URL de clique (opcional)</label>
                        <input class="lc-input" type="url" name="click_url" value="<?= htmlspecialchars($clickUrl, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://..." />
                    </div>

                    <div class="lc-card lc-card--soft" style="grid-column:1 / -1;">
                        <div class="lc-card__header">Conteúdo (WhatsApp)</div>
                        <div class="lc-card__body">
                            <div class="lc-field">
                                <label class="lc-label">Template code</label>
                                <select class="lc-select" name="whatsapp_template_code">
                                    <option value="">(selecione)</option>
                                    <?php foreach ($templates as $t): ?>
                                        <?php $code = (string)($t['code'] ?? ''); if (trim($code) === '') continue; ?>
                                        <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $waTpl === $code ? 'selected' : '' ?>><?= htmlspecialchars((string)($t['name'] ?? $code), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="lc-muted" style="margin-top:6px;">Variáveis: <code>{patient_name}</code> e <code>{click_url}</code>.</div>
                            </div>
                        </div>
                    </div>

                    <div class="lc-card lc-card--soft" style="grid-column:1 / -1;">
                        <div class="lc-card__header">Conteúdo (E-mail)</div>
                        <div class="lc-card__body">
                            <div class="lc-field">
                                <label class="lc-label">Assunto</label>
                                <input class="lc-input" type="text" name="email_subject" value="<?= htmlspecialchars($emailSubject, ENT_QUOTES, 'UTF-8') ?>" />
                            </div>
                            <div class="lc-field" style="margin-top:10px;">
                                <label class="lc-label">HTML</label>
                                <textarea class="lc-input" name="email_body" rows="8"><?= htmlspecialchars($emailBody, ENT_QUOTES, 'UTF-8') ?></textarea>
                                <div class="lc-muted" style="margin-top:6px;">Variáveis: <code>{patient_name}</code> e <code>{click_url}</code>.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                    <a class="lc-btn lc-btn--secondary" href="/marketing/automation/campaigns">Voltar</a>
                </div>
            </form>

            <form method="post" action="/marketing/automation/campaign/run" style="margin-top:12px;" onsubmit="return confirm('Enfileirar execução agora?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= $id ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Rodar agora</button>
            </form>
        <?php else: ?>
            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 160px 1fr; align-items:start;">
                <div>
                    <div class="lc-muted">Nome</div>
                    <div><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <div class="lc-muted">Canal</div>
                    <div><?= htmlspecialchars($channel, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <div class="lc-muted">Status</div>
                    <div><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <div class="lc-muted">Agendada</div>
                    <div><?= htmlspecialchars($scheduledFor, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <div class="lc-muted">URL de clique</div>
                    <div><?= htmlspecialchars($clickUrl, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <a class="lc-btn lc-btn--secondary" href="/marketing/automation/campaigns">Voltar</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
