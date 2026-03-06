<?php
/** @var list<array<string,mixed>> $items */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Fila de chegada';

$items = is_array($items ?? null) ? (array)$items : [];

$statusLabelMap = [
    'scheduled' => 'Agendado',
    'confirmed' => 'Confirmado',
    'in_progress' => 'Em atendimento',
    'completed' => 'Concluído',
    'no_show' => 'Faltou',
    'cancelled' => 'Cancelado',
];

ob_start();
?>

<div class="lc-card">
    <div class="lc-card__header">Pacientes que chegaram</div>
    <div class="lc-card__body" style="padding:0;">
        <table class="lc-table" style="margin:0;">
            <thead>
                <tr>
                    <th>Chegada</th>
                    <th>Horário</th>
                    <th>Paciente</th>
                    <th>Serviço</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items === []): ?>
                    <tr><td colspan="6" class="lc-muted" style="padding:12px;">Nenhum paciente aguardando.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $it): ?>
                        <?php
                            $apptId = (int)($it['id'] ?? 0);
                            $st = (string)($it['status'] ?? '');
                            $checkedInAt = (string)($it['checked_in_at'] ?? '');
                            $startAt = (string)($it['start_at'] ?? '');
                            $hm = $startAt !== '' ? substr($startAt, 11, 5) : '';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($checkedInAt !== '' ? substr($checkedInAt, 11, 5) : '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($hm, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($statusLabelMap[$st] ?? $st), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="white-space:nowrap; text-align:right;">
                                <form method="post" action="/schedule/start" style="display:inline;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= $apptId ?>" />
                                    <input type="hidden" name="view" value="day" />
                                    <input type="hidden" name="date" value="<?= htmlspecialchars(substr($startAt, 0, 10), ENT_QUOTES, 'UTF-8') ?>" />
                                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Iniciar atendimento</button>
                                </form>
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/schedule?date=<?= urlencode(substr($startAt, 0, 10)) ?>&created=<?= $apptId ?>">Ver na agenda</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
