<?php
$title = 'Logs WhatsApp';
$payload = $items ?? [];
$items = (is_array($payload) && isset($payload['rows']) && is_array($payload['rows'])) ? $payload['rows'] : [];
$hasNext = (is_array($payload) && isset($payload['has_next'])) ? (bool)$payload['has_next'] : false;
$filters = $filters ?? ['status' => '', 'template_code' => '', 'from' => '', 'to' => '', 'appointment_id' => '', 'patient_id' => ''];
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
$csrf = $_SESSION['_csrf'] ?? '';

$reconcileOk = isset($_GET['reconcile']) && (string)$_GET['reconcile'] !== '';

$statusHuman = [
    'pending' => 'Pendente',
    'processing' => 'Enviando',
    'sent' => 'Enviado',
    'failed' => 'Falhou',
    'skipped' => 'Ignorado',
    'cancelled' => 'Cancelado',
];

$allowedStatus = [
    '' => 'Todos',
    'all' => 'Todos',
    'pending' => 'Pendente',
    'processing' => 'Processando',
    'sent' => 'Enviado',
    'failed' => 'Falhou',
    'skipped' => 'Ignorado',
    'cancelled' => 'Cancelado',
];

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:12px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Logs WhatsApp</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/settings/whatsapp">Abrir diagnóstico</a>
        <form method="post" action="/whatsapp-logs/force-reconcile" style="display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--secondary" type="submit">Forçar reconcile agora</button>
        </form>
    </div>
</div>

<?php if ($reconcileOk): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        Verificação automática enfileirada. Em alguns segundos/minutos os lembretes podem aparecer/atualizar.
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__title">Filtros</div>

    <form method="get" class="lc-form" action="/whatsapp-logs">
        <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
        <input type="hidden" name="page" value="1" />

        <label class="lc-label">Status</label>
        <?php $st = (string)($filters['status'] ?? ''); ?>
        <select class="lc-input" name="status">
            <?php foreach ($allowedStatus as $k => $label): ?>
                <option value="<?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === $st ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Template</label>
        <input class="lc-input" type="text" name="template_code" placeholder="reminder_24h" value="<?= htmlspecialchars((string)($filters['template_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Agendamento</label>
        <div class="lc-flex lc-gap-sm lc-flex--wrap">
            <div style="min-width:220px;">
                <div class="lc-muted">De</div>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)($filters['from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div style="min-width:220px;">
                <div class="lc-muted">Até</div>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)($filters['to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </div>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:10px;">
            <?php
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $weekAgo = date('Y-m-d', strtotime('-7 day'));
            ?>
            <a class="lc-btn lc-btn--secondary" href="/whatsapp-logs?status=<?= urlencode((string)($filters['status'] ?? '')) ?>&template_code=<?= urlencode((string)($filters['template_code'] ?? '')) ?>&from=<?= urlencode((string)$yesterday) ?>&to=<?= urlencode((string)$today) ?>&appointment_id=<?= urlencode((string)($filters['appointment_id'] ?? '')) ?>&patient_id=<?= urlencode((string)($filters['patient_id'] ?? '')) ?>&per_page=<?= (int)$perPage ?>&page=1">Últimas 24h</a>
            <a class="lc-btn lc-btn--secondary" href="/whatsapp-logs?status=<?= urlencode((string)($filters['status'] ?? '')) ?>&template_code=<?= urlencode((string)($filters['template_code'] ?? '')) ?>&from=<?= urlencode((string)$weekAgo) ?>&to=<?= urlencode((string)$today) ?>&appointment_id=<?= urlencode((string)($filters['appointment_id'] ?? '')) ?>&patient_id=<?= urlencode((string)($filters['patient_id'] ?? '')) ?>&per_page=<?= (int)$perPage ?>&page=1">Últimos 7 dias</a>
        </div>

        <label class="lc-label">Appointment ID</label>
        <input class="lc-input" type="text" name="appointment_id" value="<?= htmlspecialchars((string)($filters['appointment_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Patient ID</label>
        <input class="lc-input" type="text" name="patient_id" value="<?= htmlspecialchars((string)($filters['patient_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-flex lc-gap-sm" style="margin-top:14px; align-items:center;">
            <button class="lc-btn lc-btn--primary" type="submit">Aplicar</button>
            <a class="lc-btn lc-btn--secondary" href="/whatsapp-logs">Limpar</a>
        </div>
    </form>
</div>

<div class="lc-card">
    <div class="lc-card__title">Mensagens</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Template</th>
                <th>Agendado</th>
                <th>Enviado</th>
                <th>Paciente</th>
                <th>Appointment</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <?php $stRow = (string)($it['status'] ?? ''); ?>
                    <td><?= htmlspecialchars((string)($statusHuman[$stRow] ?? $stRow), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['template_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['scheduled_for'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['sent_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?= htmlspecialchars((string)($it['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($it['patient_id'])): ?>
                            #<?= (int)$it['patient_id'] ?>
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($it['appointment_id']) ? ((int)$it['appointment_id']) : '-' ?></td>
                    <td>
                        <a class="lc-btn lc-btn--secondary" href="/whatsapp-logs/show?id=<?= (int)$it['id'] ?>">Ver</a>
                        <?php if (in_array($stRow, ['failed', 'pending'], true)): ?>
                            <form method="post" action="/whatsapp-logs/retry-send" style="display:inline;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Tentar enviar novamente</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
        <div class="lc-muted">Página <?= (int)$page ?></div>
        <div class="lc-flex lc-gap-sm">
            <?php
            $q = function (int $pageNum) use ($filters, $perPage) {
                $parts = [];
                foreach ($filters as $k => $v) {
                    $parts[] = urlencode((string)$k) . '=' . urlencode((string)$v);
                }
                $parts[] = 'per_page=' . (int)$perPage;
                $parts[] = 'page=' . (int)$pageNum;
                return implode('&', $parts);
            };
            ?>
            <?php if ($page > 1): ?>
                <a class="lc-btn lc-btn--secondary" href="/whatsapp-logs?<?= $q($page - 1) ?>">Anterior</a>
            <?php endif; ?>
            <?php if ($hasNext): ?>
                <a class="lc-btn lc-btn--secondary" href="/whatsapp-logs?<?= $q($page + 1) ?>">Próxima</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
