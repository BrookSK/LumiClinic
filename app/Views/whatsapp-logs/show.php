<?php
$title = 'Log WhatsApp';
$log = $log ?? null;
$csrf = $_SESSION['_csrf'] ?? '';

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

$resentOk = isset($_GET['resent']) && (string)$_GET['resent'] !== '';

$statusHuman = [
    'pending' => 'Pendente',
    'processing' => 'Enviando',
    'sent' => 'Enviado',
    'failed' => 'Falhou',
    'skipped' => 'Ignorado',
    'cancelled' => 'Cancelado',
];
ob_start();

$payloadPretty = '';
if (is_array($log) && isset($log['payload_json']) && $log['payload_json'] !== null) {
    $decoded = json_decode((string)$log['payload_json'], true);
    if ($decoded !== null) {
        $payloadPretty = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        $payloadPretty = (string)$log['payload_json'];
    }
}

$responsePretty = '';
if (is_array($log) && isset($log['response_json']) && $log['response_json'] !== null) {
    $decoded = json_decode((string)$log['response_json'], true);
    if ($decoded !== null) {
        $responsePretty = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        $responsePretty = (string)$log['response_json'];
    }
}
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Log WhatsApp #<?= (int)($log['id'] ?? 0) ?></div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/whatsapp-logs">Voltar</a>
        <?php if ($can('settings.update')): ?>
            <form method="post" action="/whatsapp-logs/force-reconcile" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Verificar lembretes agora</button>
            </form>
        <?php endif; ?>
        <?php $st = (string)($log['status'] ?? ''); ?>
        <?php if ($can('settings.update') && in_array($st, ['failed', 'pending'], true)): ?>
            <form method="post" action="/whatsapp-logs/retry-send" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)($log['id'] ?? 0) ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Tentar enviar novamente</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($resentOk): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        Reenvio enfileirado. Se o WhatsApp estiver configurado e o paciente tiver opt-in, a mensagem será enviada em breve.
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Resumo</div>
    <div class="lc-card__body">
        <div class="lc-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap:10px;">
            <div><strong>Status:</strong> <?= htmlspecialchars((string)($statusHuman[(string)($log['status'] ?? '')] ?? (string)($log['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Template:</strong> <?= htmlspecialchars((string)($log['template_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Agendado:</strong> <?= htmlspecialchars((string)($log['scheduled_for'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Enviado:</strong> <?= htmlspecialchars((string)($log['sent_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Paciente:</strong>
                <?= htmlspecialchars((string)($log['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($log['patient_id'])): ?>#<?= (int)$log['patient_id'] ?><?php endif; ?>
            </div>
            <div><strong>Appointment:</strong> <?= !empty($log['appointment_id']) ? ((int)$log['appointment_id']) : '-' ?></div>
            <div><strong>Provider ID:</strong> <?= htmlspecialchars((string)($log['provider_message_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Atualizado:</strong> <?= htmlspecialchars((string)($log['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <?php if (!empty($log['error_message'])): ?>
            <div class="lc-alert lc-alert--danger" style="margin-top:12px;">
                <?= nl2br(htmlspecialchars((string)$log['error_message'], ENT_QUOTES, 'UTF-8')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Payload</div>
    <div class="lc-card__body">
        <pre style="white-space:pre-wrap;"><?= htmlspecialchars((string)$payloadPretty, ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Resposta</div>
    <div class="lc-card__body">
        <pre style="white-space:pre-wrap;"><?= htmlspecialchars((string)$responsePretty, ENT_QUOTES, 'UTF-8') ?></pre>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
