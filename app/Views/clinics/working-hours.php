<?php
$title = 'Horários de funcionamento';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$items = $items ?? [];

$weekdayLabels = [
    0 => 'Domingo',
    1 => 'Segunda',
    2 => 'Terça',
    3 => 'Quarta',
    4 => 'Quinta',
    5 => 'Sexta',
    6 => 'Sábado',
];

ob_start();
?>
<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__title">Adicionar horário</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/clinic/working-hours">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Dia da semana</label>
        <select class="lc-input" name="weekday" required>
            <?php foreach ($weekdayLabels as $k => $v): ?>
                <option value="<?= (int)$k ?>"><?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Início</label>
        <input class="lc-input" type="time" name="start_time" required />

        <label class="lc-label">Fim</label>
        <input class="lc-input" type="time" name="end_time" required />

        <div class="lc-flex lc-gap-sm" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Adicionar</button>
            <a class="lc-btn lc-btn--secondary" href="/clinic">Voltar</a>
        </div>
    </form>
</div>

<div class="lc-card">
    <div class="lc-card__title">Horários cadastrados</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>Dia</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= htmlspecialchars($weekdayLabels[(int)$it['weekday']] ?? (string)$it['weekday'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$it['start_time'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$it['end_time'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <form method="post" action="/clinic/working-hours/delete" style="margin:0;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                            <button class="lc-btn lc-btn--secondary" type="submit">Remover</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
