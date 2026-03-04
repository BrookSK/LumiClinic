<?php
/** @var string $title */
/** @var string $mode */
/** @var string $date */
/** @var string $start_date */
/** @var string $end_date */
/** @var int $professional_id */
/** @var list<array<string,mixed>> $professionals */
/** @var array{start_at:string,end_at:string,label:string,days:list<string>} $range */
/** @var array{by_day:array<string,array<string,mixed>>,totals:array<string,mixed>,workload:array<int,array<string,mixed>>} $metrics */
/** @var list<array{date:string,available_slots:int,blocked_slots:int,occupied_slots:int,occupancy_pct:float}> $forecast */

$csrf = $_SESSION['_csrf'] ?? '';

$fmtPct = static function ($v): string {
    return number_format((float)$v, 1, ',', '') . '%';
};

$fmtHm = static function (int $minutes): string {
    $h = (int)floor($minutes / 60);
    $m = $minutes % 60;
    return str_pad((string)$h, 2, '0', STR_PAD_LEFT) . 'h' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
};

$tot = $metrics['totals'] ?? [];
$byDay = $metrics['by_day'] ?? [];
$workload = $metrics['workload'] ?? [];

ob_start();
?>

<div class="lc-page">
    <div class="lc-page__header">
        <div>
            <h1 class="lc-h1">Painel Gestor</h1>
            <div class="lc-muted">Período: <?= htmlspecialchars((string)($range['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px;">
        <div class="lc-card__body">
            <form method="get" action="/manager/panel" class="lc-grid lc-gap-grid" style="grid-template-columns: 160px 180px 180px 1fr 220px; align-items:end;">
                <div>
                    <label class="lc-label" for="mode">Visão</label>
                    <select class="lc-select" name="mode" id="mode">
                        <option value="week" <?= $mode==='week' ? 'selected' : '' ?>>Semana</option>
                        <option value="day" <?= $mode==='day' ? 'selected' : '' ?>>Dia</option>
                        <option value="range" <?= $mode==='range' ? 'selected' : '' ?>>Período</option>
                    </select>
                </div>

                <div>
                    <label class="lc-label" for="date">Data (base)</label>
                    <input class="lc-input" type="date" name="date" id="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" />
                </div>

                <div>
                    <label class="lc-label" for="start_date">Início</label>
                    <input class="lc-input" type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') ?>" />
                </div>

                <div>
                    <label class="lc-label" for="end_date">Fim</label>
                    <input class="lc-input" type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8') ?>" />
                </div>

                <div>
                    <label class="lc-label" for="professional_id">Profissional</label>
                    <select class="lc-select" name="professional_id" id="professional_id">
                        <option value="0">Todos</option>
                        <?php foreach ($professionals as $p): ?>
                            <?php $pid = (int)($p['id'] ?? 0); ?>
                            <option value="<?= $pid ?>" <?= $professional_id===$pid ? 'selected' : '' ?>><?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="grid-column: 1 / -1; display:flex; gap:10px;">
                    <button class="lc-btn" type="submit">Aplicar</button>
                    <a class="lc-btn lc-btn--secondary" href="/manager/panel">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(4, 1fr); margin-top:16px;">
        <div class="lc-card" style="margin:0;">
            <div class="lc-card__body">
                <div class="lc-muted" style="font-size:12px;">Taxa de ocupação</div>
                <div style="font-weight:800; font-size:24px; margin-top:6px;">
                    <?= $fmtPct($tot['occupancy_pct'] ?? 0) ?>
                </div>
                <div class="lc-muted" style="font-size:12px; margin-top:4px;">
                    Ocupado: <?= (int)($tot['occupied_slots'] ?? 0) ?> / Capacidade: <?= max(0, (int)($tot['available_slots'] ?? 0) - (int)($tot['blocked_slots'] ?? 0)) ?>
                </div>
            </div>
        </div>

        <div class="lc-card" style="margin:0;">
            <div class="lc-card__body">
                <div class="lc-muted" style="font-size:12px;">Comparecimento</div>
                <div style="font-weight:800; font-size:24px; margin-top:6px;">
                    <?= $fmtPct($tot['attendance_pct'] ?? 0) ?>
                </div>
                <div class="lc-muted" style="font-size:12px; margin-top:4px;">
                    Total: <?= (int)($tot['total'] ?? 0) ?> | No-show: <?= (int)($tot['no_show'] ?? 0) ?>
                </div>
            </div>
        </div>

        <div class="lc-card" style="margin:0;">
            <div class="lc-card__body">
                <div class="lc-muted" style="font-size:12px;">Atendimentos concluídos</div>
                <div style="font-weight:800; font-size:24px; margin-top:6px;">
                    <?= (int)($tot['completed'] ?? 0) ?>
                </div>
                <div class="lc-muted" style="font-size:12px; margin-top:4px;">
                    Em andamento: <?= (int)($tot['in_progress'] ?? 0) ?>
                </div>
            </div>
        </div>

        <div class="lc-card" style="margin:0;">
            <div class="lc-card__body">
                <div class="lc-muted" style="font-size:12px;">Agendados/Confirmados</div>
                <div style="font-weight:800; font-size:24px; margin-top:6px;">
                    <?= (int)($tot['scheduled'] ?? 0) + (int)($tot['confirmed'] ?? 0) ?>
                </div>
                <div class="lc-muted" style="font-size:12px; margin-top:4px;">
                    Agendados: <?= (int)($tot['scheduled'] ?? 0) ?> | Confirmados: <?= (int)($tot['confirmed'] ?? 0) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1.2fr 0.8fr; margin-top:16px;">
        <div class="lc-card" style="margin:0;">
            <div class="lc-card__body">
                <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
                    <div>
                        <div style="font-weight:800;">Ocupação por dia</div>
                        <div class="lc-muted" style="font-size:12px;">Slots de 15min (capacidade considera horário de funcionamento - bloqueios)</div>
                    </div>
                </div>

                <div class="lc-table-wrap" style="margin-top:12px;">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Dia</th>
                            <th>Ocupação</th>
                            <th>Capacidade</th>
                            <th>Ocupado</th>
                            <th>No-show</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($byDay as $ymd => $d): ?>
                            <?php
                                $cap = max(0, (int)($d['available_slots'] ?? 0) - (int)($d['blocked_slots'] ?? 0));
                                $occ = (int)($d['occupied_slots'] ?? 0);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($ymd, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $fmtPct($d['occupancy_pct'] ?? 0) ?></td>
                                <td><?= $cap ?></td>
                                <td><?= $occ ?></td>
                                <td><?= (int)($d['no_show'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lc-card" style="margin:0;">
            <div class="lc-card__body">
                <div style="font-weight:800;">Carga por profissional</div>
                <div class="lc-muted" style="font-size:12px;">Tempo total (somente consultas n e3o canceladas)</div>

                <div class="lc-table-wrap" style="margin-top:12px;">
                    <table class="lc-table">
                        <thead>
                        <tr>
                            <th>Profissional</th>
                            <th>Tempo</th>
                            <th>Atend.</th>
                            <th>No-show</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$workload): ?>
                            <tr><td colspan="4" class="lc-muted">Sem dados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($workload as $w): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($w['professional_name'] ?? ('#' . (int)($w['professional_id'] ?? 0))), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($fmtHm((int)($w['minutes'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int)($w['appointments'] ?? 0) ?></td>
                                    <td><?= (int)($w['no_show'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="lc-card" style="margin-top:16px;">
        <div class="lc-card__body">
            <div style="font-weight:800;">Previsão (próximos 14 dias)</div>
            <div class="lc-muted" style="font-size:12px;">Ocupação estimada com base nos agendamentos futuros</div>

            <div class="lc-table-wrap" style="margin-top:12px;">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ocupação</th>
                        <th>Capacidade</th>
                        <th>Ocupado</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($forecast as $f): ?>
                        <?php $cap = max(0, (int)$f['available_slots'] - (int)$f['blocked_slots']); ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$f['date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $fmtPct((float)$f['occupancy_pct']) ?></td>
                            <td><?= $cap ?></td>
                            <td><?= (int)$f['occupied_slots'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
  const mode = document.getElementById('mode');
  const date = document.getElementById('date');
  const sd = document.getElementById('start_date');
  const ed = document.getElementById('end_date');

  function applyVisibility() {
    const m = mode ? mode.value : 'week';
    const isRange = m === 'range';
    if (date) date.closest('div').style.display = isRange ? 'none' : '';
    if (sd) sd.closest('div').style.display = isRange ? '' : 'none';
    if (ed) ed.closest('div').style.display = isRange ? '' : 'none';
  }

  if (mode) mode.addEventListener('change', applyVisibility);
  applyVisibility();
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
