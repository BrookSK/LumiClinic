<?php
$csrf = $_SESSION['_csrf'] ?? '';
$isProfessional = isset($is_professional) ? (bool)$is_professional : false;
$professionalId = isset($professional_id) ? (int)$professional_id : 0;
$title = 'Agenda (Mês)';

$svcMap = [];
foreach (($services ?? []) as $s) {
    $svcMap[(int)$s['id']] = $s;
}

$profMap = [];
foreach (($professionals ?? []) as $p) {
    $profMap[(int)$p['id']] = $p;
}

$byDay = [];
foreach (($items ?? []) as $it) {
    $d = substr((string)$it['start_at'], 0, 10);
    if (!isset($byDay[$d])) {
        $byDay[$d] = [];
    }
    $byDay[$d][] = $it;
}

$monthStart = \DateTimeImmutable::createFromFormat('Y-m-d', (string)($month_start ?? ($date ?? date('Y-m-d'))));
if ($monthStart !== false) {
    $monthStart = $monthStart->modify('first day of this month');
}

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card" style="margin-bottom: 16px; border-left: 4px solid #b91c1c;">
        <div class="lc-card__body"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (isset($created) && $created !== ''): ?>
    <div class="lc-card" style="margin-bottom: 16px; border-left: 4px solid #16a34a;">
        <div class="lc-card__body">Atualizado. ID: <?= htmlspecialchars((string)$created, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__body">
        <form method="get" action="/schedule" class="lc-form" style="display:flex; gap: 12px; align-items:end; flex-wrap: wrap;">
            <input type="hidden" name="view" value="month" />

            <div class="lc-field">
                <label class="lc-label">Mês de</label>
                <input class="lc-input" type="date" name="date" value="<?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <?php if (!$isProfessional): ?>
                <div class="lc-field" style="min-width: 280px;">
                    <label class="lc-label">Profissional</label>
                    <select class="lc-select" name="professional_id">
                        <option value="0">Todos</option>
                        <?php foreach (($professionals ?? []) as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professionalId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" name="professional_id" value="<?= (int)$professionalId ?>" />
            <?php endif; ?>

            <div>
                <button class="lc-btn" type="submit">Ver Mês</button>
                <a class="lc-btn lc-btn--secondary" href="/schedule?view=day&date=<?= urlencode((string)$date) ?><?= $professionalId>0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Ver Dia</a>
                <a class="lc-btn lc-btn--secondary" href="/schedule?view=week&date=<?= urlencode((string)$date) ?><?= $professionalId>0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Ver Semana</a>
            </div>
        </form>
    </div>
</div>

<?php if ($monthStart !== false): ?>
    <?php
        $gridStart = $monthStart->modify('-' . (int)$monthStart->format('w') . ' days');
        $nextMonth = $monthStart->modify('first day of next month');
        $prevMonth = $monthStart->modify('first day of previous month');
        $today = date('Y-m-d');
    ?>

    <div class="lc-card" style="margin-bottom:16px;">
        <div class="lc-card__header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div>
                <?= htmlspecialchars($monthStart->format('m/Y'), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div style="display:flex; gap:10px;">
                <a class="lc-btn lc-btn--secondary" href="/schedule?view=month&date=<?= urlencode($prevMonth->format('Y-m-d')) ?><?= $professionalId>0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Anterior</a>
                <a class="lc-btn lc-btn--secondary" href="/schedule?view=month&date=<?= urlencode($nextMonth->format('Y-m-d')) ?><?= $professionalId>0 ? ('&professional_id=' . (int)$professionalId) : '' ?>">Próximo</a>
            </div>
        </div>
        <div class="lc-card__body">
            <div style="display:grid; grid-template-columns: repeat(7, 1fr); gap: 10px;">
                <?php for ($i=0; $i<42; $i++): ?>
                    <?php
                        $d = $gridStart->modify('+' . $i . ' days');
                        $ymd = $d->format('Y-m-d');
                        $inMonth = $d->format('m') === $monthStart->format('m');
                        $dayItems = $byDay[$ymd] ?? [];
                        $count = count($dayItems);
                        $border = $ymd === $today ? '4px solid #2563eb' : '1px solid rgba(255,255,255,0.10)';
                        $opacity = $inMonth ? '1' : '0.45';
                    ?>
                    <a href="/schedule?view=day&date=<?= urlencode($ymd) ?><?= $professionalId>0 ? ('&professional_id=' . (int)$professionalId) : '' ?>" style="text-decoration:none; color:inherit;">
                        <div class="lc-card" style="margin:0; border-left: <?= htmlspecialchars($border, ENT_QUOTES, 'UTF-8') ?>; opacity: <?= htmlspecialchars($opacity, ENT_QUOTES, 'UTF-8') ?>;">
                            <div class="lc-card__body" style="padding:12px; min-height: 88px; display:flex; flex-direction:column; gap:8px;">
                                <div style="display:flex; justify-content:space-between; align-items:baseline;">
                                    <div style="font-weight: 700;">
                                        <?= htmlspecialchars($d->format('d'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="lc-muted" style="font-size:12px;">
                                        <?= $count > 0 ? ((int)$count . ' ag.') : '' ?>
                                    </div>
                                </div>

                                <?php if ($dayItems === []): ?>
                                    <div class="lc-muted" style="font-size:12px;">&nbsp;</div>
                                <?php else: ?>
                                    <?php
                                        usort($dayItems, fn ($a, $b) => strcmp((string)$a['start_at'], (string)$b['start_at']));
                                        $maxShow = 3;
                                        $shown = array_slice($dayItems, 0, $maxShow);
                                    ?>
                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                        <?php foreach ($shown as $it): ?>
                                            <?php
                                                $pid = (int)$it['professional_id'];
                                                $sid = (int)$it['service_id'];
                                                $pname = isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : ('#' . $pid);
                                                $sname = isset($svcMap[$sid]) ? (string)$svcMap[$sid]['name'] : ('#' . $sid);
                                                $status = (string)$it['status'];
                                                $dot = '#d4af37';
                                                if ($status === 'cancelled') $dot = '#6b7280';
                                                if ($status === 'confirmed') $dot = '#2563eb';
                                                if ($status === 'in_progress') $dot = '#f59e0b';
                                                if ($status === 'completed') $dot = '#16a34a';
                                                if ($status === 'no_show') $dot = '#b91c1c';
                                            ?>
                                            <div style="display:flex; gap:8px; align-items:center;">
                                                <span style="width:8px; height:8px; border-radius:999px; background: <?= htmlspecialchars($dot, ENT_QUOTES, 'UTF-8') ?>; display:inline-block;"></span>
                                                <div style="font-size:12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                    <span style="font-weight:600;">
                                                        <?= htmlspecialchars(substr((string)$it['start_at'], 11, 5), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                    <span class="lc-muted">• <?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars($sname, ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($count > $maxShow): ?>
                                            <div class="lc-muted" style="font-size:12px;">+<?= (int)($count - $maxShow) ?>...</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
