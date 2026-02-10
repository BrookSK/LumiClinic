<?php
$title = 'Feriados e Recesso';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$items = $items ?? [];
ob_start();
?>
<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__title">Adicionar feriado/recesso</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/clinic/closed-days">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Data</label>
        <input class="lc-input" type="date" name="closed_date" required />

        <label class="lc-label">Motivo (opcional)</label>
        <input class="lc-input" type="text" name="reason" />

        <div style="margin-top:14px; display:flex; gap:10px;">
            <button class="lc-btn lc-btn--primary" type="submit">Adicionar</button>
            <a class="lc-btn lc-btn--secondary" href="/clinic">Voltar</a>
        </div>
    </form>
</div>

<div class="lc-card">
    <div class="lc-card__title">Datas cadastradas</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>Data</th>
                <th>Motivo</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$it['closed_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <form method="post" action="/clinic/closed-days/delete" style="margin:0;">
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
