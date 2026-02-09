<?php
/** @var string $date */
/** @var string $view */
/** @var int $professional_id */
/** @var string $created */
/** @var string $error */
/** @var string $week_start */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>> $services */
$isProfessional = isset($is_professional) ? (bool)$is_professional : false;
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Agenda (Semana)';

$weekdayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

$svcMap = [];
foreach ($services as $s) {
    $svcMap[(int)$s['id']] = $s;
}

$profMap = [];
foreach ($professionals as $p) {
    $profMap[(int)$p['id']] = $p;
}

$byDay = [];
foreach ($items as $it) {
    $d = substr((string)$it['start_at'], 0, 10);
    if (!isset($byDay[$d])) {
        $byDay[$d] = [];
    }
    $byDay[$d][] = $it;
}

$weekStart = \DateTimeImmutable::createFromFormat('Y-m-d', $week_start);

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card" style="margin-bottom: 16px; border-left: 4px solid #b91c1c;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (isset($created) && $created !== ''): ?>
    <div class="lc-card" style="margin-bottom: 16px; border-left: 4px solid #16a34a;">
        <div class="lc-card__body">Atualizado. ID: <?= htmlspecialchars($created, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__body">
        <form method="get" action="/schedule" class="lc-form" style="display:flex; gap: 12px; align-items:end; flex-wrap: wrap;">
            <input type="hidden" name="view" value="week" />

            <div class="lc-field">
                <label class="lc-label">Semana de</label>
                <input class="lc-input" type="date" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <?php if (!$isProfessional): ?>
                <div class="lc-field" style="min-width: 280px;">
                    <label class="lc-label">Profissional</label>
                    <select class="lc-select" name="professional_id">
                        <option value="0">Todos</option>
                        <?php foreach ($professionals as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professional_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
            <?php endif; ?>

            <div>
                <button class="lc-btn" type="submit">Ver Semana</button>
                <a class="lc-btn lc-btn--secondary" href="/schedule?view=day&date=<?= urlencode($date) ?><?= $professional_id>0 ? ('&professional_id=' . (int)$professional_id) : '' ?>">Ver Dia</a>
                <a class="lc-btn lc-btn--secondary" href="/schedule?view=month&date=<?= urlencode($date) ?><?= $professional_id>0 ? ('&professional_id=' . (int)$professional_id) : '' ?>">Ver Mês</a>
            </div>
        </form>
    </div>
</div>

<?php if ($weekStart !== false): ?>
    <div class="lc-card">
        <div class="lc-card__header">Semana</div>
        <div class="lc-card__body">
            <div style="display:grid; grid-template-columns: repeat(7, 1fr); gap: 12px;">
                <?php for ($i=0; $i<7; $i++): ?>
                    <?php $d = $weekStart->modify('+' . $i . ' days'); $ymd = $d->format('Y-m-d'); ?>
                    <div class="lc-card" style="margin:0;">
                        <div class="lc-card__header"><?= htmlspecialchars($weekdayNames[(int)$d->format('w')], ENT_QUOTES, 'UTF-8') ?>
                            <div class="lc-muted" style="font-size: 12px; margin-top: 4px;">
                                <?= htmlspecialchars($ymd, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <div class="lc-card__body" style="display:flex; flex-direction: column; gap: 8px;">
                            <?php $dayItems = $byDay[$ymd] ?? []; ?>
                            <?php if ($dayItems === []): ?>
                                <div class="lc-muted">Sem agendamentos</div>
                            <?php else: ?>
                                <?php foreach ($dayItems as $it): ?>
                                    <?php
                                        $pid = (int)$it['professional_id'];
                                        $sid = (int)$it['service_id'];
                                        $pname = isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : ('#' . $pid);
                                        $sname = isset($svcMap[$sid]) ? (string)$svcMap[$sid]['name'] : ('#' . $sid);
                                        $status = (string)$it['status'];
                                        $border = '#d4af37';
                                        if ($status === 'cancelled') $border = '#6b7280';
                                        if ($status === 'confirmed') $border = '#2563eb';
                                        if ($status === 'in_progress') $border = '#f59e0b';
                                        if ($status === 'completed') $border = '#16a34a';
                                        if ($status === 'no_show') $border = '#b91c1c';
                                    ?>
                                    <div style="border-left:4px solid <?= htmlspecialchars($border, ENT_QUOTES, 'UTF-8') ?>; padding-left:8px;">
                                        <div style="font-weight: 600;">
                                            <?= htmlspecialchars(substr((string)$it['start_at'], 11, 5), ENT_QUOTES, 'UTF-8') ?>
                                            -
                                            <?= htmlspecialchars(substr((string)$it['end_at'], 11, 5), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="lc-muted" style="font-size:12px;">
                                            <?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars($sname, ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div style="display:flex; gap:8px; margin-top:6px; flex-wrap: wrap;">
                                            <a class="lc-btn lc-btn--secondary" href="/schedule/reschedule?id=<?= (int)$it['id'] ?>">Reagendar</a>
                                            <a class="lc-btn lc-btn--secondary" href="/schedule/logs?appointment_id=<?= (int)$it['id'] ?>">Logs</a>
                                            <form method="post" action="/schedule/status">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                                <input type="hidden" name="status" value="confirmed" />
                                                <input type="hidden" name="view" value="week" />
                                                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                                                <button class="lc-btn lc-btn--secondary" type="submit">Confirmar</button>
                                            </form>
                                            <form method="post" action="/schedule/status">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                                <input type="hidden" name="status" value="in_progress" />
                                                <input type="hidden" name="view" value="week" />
                                                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                                                <button class="lc-btn lc-btn--secondary" type="submit">Atender</button>
                                            </form>
                                            <form method="post" action="/schedule/status">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                                <input type="hidden" name="status" value="completed" />
                                                <input type="hidden" name="view" value="week" />
                                                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                                                <button class="lc-btn lc-btn--secondary" type="submit">Concluir</button>
                                            </form>
                                            <form method="post" action="/schedule/status">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                                <input type="hidden" name="status" value="no_show" />
                                                <input type="hidden" name="view" value="week" />
                                                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                                                <button class="lc-btn lc-btn--secondary" type="submit">No-show</button>
                                            </form>
                                            <form method="post" action="/schedule/cancel">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                                <button class="lc-btn lc-btn--secondary" type="submit">Cancelar</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
