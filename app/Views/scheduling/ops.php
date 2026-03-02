<?php
/** @var string $date */
/** @var array<string,int> $counts */
/** @var string|null $category */
/** @var list<array<string, mixed>>|null $items */
$title = 'Operação (Agenda)';
ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Indicadores do dia</div>
    <div class="lc-card__body">
        <form method="get" action="/schedule/ops" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end;">
            <div class="lc-field">
                <label class="lc-label">Data</label>
                <input class="lc-input" type="date" name="date" value="<?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Categoria</label>
                <select class="lc-input" name="category">
                    <option value="all" <?= (($category ?? 'all') === 'all') ? 'selected' : '' ?>>Todos</option>
                    <option value="pending" <?= (($category ?? 'all') === 'pending') ? 'selected' : '' ?>>Pendentes</option>
                    <option value="finalized" <?= (($category ?? 'all') === 'finalized') ? 'selected' : '' ?>>Finalizados</option>
                </select>
            </div>
            <div>
                <button class="lc-btn" type="submit">Ver</button>
                <a class="lc-btn lc-btn--secondary" href="/schedule?date=<?= urlencode((string)$date) ?>">Voltar à agenda</a>
            </div>
        </form>

        <div class="lc-grid lc-grid--3 lc-gap-grid" style="margin-top:14px;">
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Total: <strong><?= (int)($counts['total'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Pendentes: <strong><?= (int)($counts['pending'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Finalizados: <strong><?= (int)($counts['finalized'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Confirmados: <strong><?= (int)($counts['confirmed'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Em atendimento: <strong><?= (int)($counts['in_progress'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Concluídos: <strong><?= (int)($counts['completed'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Cancelados: <strong><?= (int)($counts['cancelled'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Faltou: <strong><?= (int)($counts['no_show'] ?? 0) ?></strong></div></div>
        </div>

        <?php $pendingRequests = $pending_requests ?? []; ?>
        <div class="lc-card" style="margin-top:16px;">
            <div class="lc-card__header">Solicitações do portal (pendentes)</div>
            <div class="lc-card__body">
                <?php if (!is_array($pendingRequests) || $pendingRequests === []): ?>
                    <div class="lc-muted">Nenhuma solicitação pendente.</div>
                <?php else: ?>
                    <div class="lc-table-wrap">
                        <table class="lc-table" style="margin:0;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Paciente</th>
                                    <th>Agendamento</th>
                                    <th>Profissional</th>
                                    <th>Serviço</th>
                                    <th>Data solicitada</th>
                                    <th>Obs.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRequests as $r): ?>
                                    <?php
                                        $type = (string)($r['type'] ?? '');
                                        $typeLabel = $type === 'reschedule' ? 'Reagendamento' : ($type === 'cancel' ? 'Cancelamento' : $type);
                                        $apptStart = (string)($r['appointment_start_at'] ?? '');
                                        $apptDate = $apptStart !== '' ? substr($apptStart, 0, 10) : (string)$date;
                                        $apptId = (int)($r['appointment_id'] ?? 0);
                                    ?>
                                    <tr>
                                        <td>#<?= (int)($r['id'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($r['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <a href="/schedule?date=<?= urlencode($apptDate) ?>&created=<?= $apptId ?>">
                                                #<?= $apptId ?>
                                                <?= $apptStart !== '' ? (' • ' . htmlspecialchars(substr($apptStart, 11, 5), ENT_QUOTES, 'UTF-8')) : '' ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars((string)($r['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($r['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($r['requested_start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($r['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($items) && is_array($items)): ?>
            <?php
                $statusLabelMap = [
                    'scheduled' => 'Agendado',
                    'confirmed' => 'Confirmado',
                    'in_progress' => 'Em atendimento',
                    'completed' => 'Concluído',
                    'no_show' => 'Faltou',
                    'cancelled' => 'Cancelado',
                ];
            ?>
            <div class="lc-card" style="margin-top:16px;">
                <div class="lc-card__header">Agendamentos (<?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?>)</div>
                <div class="lc-card__body" style="padding:0;">
                    <table class="lc-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items === []): ?>
                                <tr><td colspan="4" class="lc-muted" style="padding:12px;">Nenhum agendamento encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $it): ?>
                                    <?php $st = (string)($it['status'] ?? ''); ?>
                                    <tr>
                                        <td><?= htmlspecialchars(substr((string)($it['start_at'] ?? ''), 11, 5), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(substr((string)($it['end_at'] ?? ''), 11, 5), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <a href="/schedule?date=<?= urlencode((string)$date) ?>&created=<?= (int)($it['id'] ?? 0) ?>">
                                                #<?= (int)($it['id'] ?? 0) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars((string)($statusLabelMap[$st] ?? $st), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
