<?php
/** @var list<array<string,mixed>> $items */
/** @var array<string,int> $avg_duration_by_procedure */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Procedimentos';

$error = is_string($error ?? null) ? (string)$error : '';
$success = is_string($success ?? null) ? (string)$success : '';

ob_start();
?>

<?php if ($error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom:16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="lc-card" style="margin-bottom:16px;">
        <div class="lc-card__body"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Novo procedimento</div>
    <div class="lc-card__body">
        <form method="post" action="/procedures/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" required />
            </div>

            <div>
                <button class="lc-btn" type="submit">Criar</button>
            </div>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Procedimentos</div>
    <div class="lc-card__body">
        <?php if ($items === []): ?>
            <div class="lc-muted">Nenhum procedimento cadastrado.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Duração média real</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php
                        $id = (int)$it['id'];
                        $avg = $avg_duration_by_procedure[(string)$id] ?? null;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $avg === null ? '-' : ((int)$avg . ' min') ?></td>
                        <td style="text-align:right;">
                            <a class="lc-btn lc-btn--secondary" href="/procedures/edit?id=<?= $id ?>">Abrir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
