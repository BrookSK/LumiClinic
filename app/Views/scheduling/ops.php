<?php
/** @var string $date */
/** @var array<string,int> $counts */
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
            <div>
                <button class="lc-btn" type="submit">Ver</button>
                <a class="lc-btn lc-btn--secondary" href="/schedule?date=<?= urlencode((string)$date) ?>">Voltar à agenda</a>
            </div>
        </form>

        <div class="lc-grid lc-grid--3 lc-gap-grid" style="margin-top:14px;">
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
