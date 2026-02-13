<?php
/** @var array<string,mixed> $patient */
/** @var list<array<string,mixed>> $items */
/** @var int $page */
/** @var int $per_page */
/** @var bool $has_next */
/** @var string $status */
/** @var string $start_date */
/** @var string $end_date */

$title = 'Paciente - Consultas';
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
$hasNext = isset($has_next) ? (bool)$has_next : false;

$status = isset($status) ? (string)$status : 'all';
$startDate = isset($start_date) ? (string)$start_date : '';
$endDate = isset($end_date) ? (string)$end_date : '';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Consultas</div>
        <div class="lc-muted" style="margin-top:6px;">
            <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao perfil</a>
        <a class="lc-btn lc-btn--secondary" href="/schedule?view=week&date=<?= urlencode(date('Y-m-d')) ?>">Abrir agenda</a>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Histórico</div>
    <div class="lc-card__body">
        <form method="get" action="/patients/appointments" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr 120px; align-items:end; margin-bottom:12px;">
            <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos</option>
                    <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Agendado</option>
                    <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Confirmado</option>
                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>Em atendimento</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Concluído</option>
                    <option value="no_show" <?= $status === 'no_show' ? 'selected' : '' ?>>No-show</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="start_date" value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="end_date" value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <button class="lc-btn lc-btn--secondary" type="submit">Filtrar</button>
        </form>

        <?php if (!is_array($items) || $items === []): ?>
            <div class="lc-muted">Nenhuma consulta/agendamento encontrado.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Serviço</th>
                    <th>Profissional</th>
                    <th>Plano</th>
                    <th>Orçamento</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php
                        $apptId = (int)($it['id'] ?? 0);
                        $startAt = (string)($it['start_at'] ?? '');
                        $date = $startAt !== '' ? substr($startAt, 0, 10) : date('Y-m-d');
                        $planId = isset($it['patient_procedure_id']) && $it['patient_procedure_id'] !== null ? (int)$it['patient_procedure_id'] : 0;
                        $planTotal = (int)($it['plan_total_sessions'] ?? 0);
                        $planUsed = (int)($it['plan_used_sessions'] ?? 0);
                        $planSaleId = (int)($it['plan_sale_id'] ?? 0);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($it['start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['end_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($it['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($planId > 0): ?>
                                <?= $planUsed ?> / <?= $planTotal ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($planSaleId > 0): ?>
                                <a class="lc-btn lc-btn--secondary" href="/finance/sales/view?id=<?= $planSaleId ?>">#<?= $planSaleId ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string)($it['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a class="lc-btn lc-btn--secondary" href="/schedule?view=week&date=<?= urlencode($date) ?>">Agenda</a>
                            <a class="lc-btn lc-btn--secondary" href="/schedule/reschedule?appointment_id=<?= $apptId ?>">Reagendar</a>
                            <a class="lc-btn lc-btn--secondary" href="/schedule/logs?appointment_id=<?= $apptId ?>">Logs</a>
                            <a class="lc-btn lc-btn--primary" href="/patients/consultation?appointment_id=<?= $apptId ?>">Executar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
                <div class="lc-muted">Página <?= (int)$page ?></div>
                <div class="lc-flex lc-gap-sm">
                    <?php if ($page > 1): ?>
                        <a class="lc-btn lc-btn--secondary" href="/patients/appointments?patient_id=<?= (int)($patient['id'] ?? 0) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>&status=<?= urlencode($status) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">Anterior</a>
                    <?php endif; ?>
                    <?php if ($hasNext): ?>
                        <a class="lc-btn lc-btn--secondary" href="/patients/appointments?patient_id=<?= (int)($patient['id'] ?? 0) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>&status=<?= urlencode($status) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">Próxima</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
