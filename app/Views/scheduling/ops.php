<?php
/** @var string $date */
/** @var array<string,int> $counts */
$title = 'Operação (Agenda)';
ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Indicadores do dia</div>
    <div class="lc-card__body">
        <form method="get" action="/schedule/ops" class="lc-form" style="display:flex; gap: 12px; align-items:end; flex-wrap: wrap;">
            <div class="lc-field">
                <label class="lc-label">Data</label>
                <input class="lc-input" type="date" name="date" value="<?= htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <button class="lc-btn" type="submit">Ver</button>
                <a class="lc-btn lc-btn--secondary" href="/schedule?date=<?= urlencode((string)$date) ?>">Voltar à agenda</a>
            </div>
        </form>

        <div style="display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; margin-top: 14px;">
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Total: <strong><?= (int)($counts['total'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Confirmados: <strong><?= (int)($counts['confirmed'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Em atendimento: <strong><?= (int)($counts['in_progress'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Concluídos: <strong><?= (int)($counts['completed'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Cancelados: <strong><?= (int)($counts['cancelled'] ?? 0) ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">No-show: <strong><?= (int)($counts['no_show'] ?? 0) ?></strong></div></div>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
