<?php
/** @var array<string,mixed> $row */
/** @var string $error */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Editar profissional';

$id = (int)($row['id'] ?? 0);
$name = (string)($row['name'] ?? '');
$specialty = (string)($row['specialty'] ?? '');
$allowOnline = (int)($row['allow_online_booking'] ?? 0) === 1;

ob_start();
?>

<?php if (is_string($error) && trim($error) !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Editar profissional</div>
    <div class="lc-card__body">
        <form method="post" action="/professionals/edit" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 2fr 1fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)$id ?>" />

            <div class="lc-field">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Especialidade</label>
                <input class="lc-input" type="text" name="specialty" value="<?= htmlspecialchars($specialty, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Agendamento online?</label>
                <select class="lc-select" name="allow_online_booking">
                    <option value="0" <?= $allowOnline ? '' : 'selected' ?>>Não</option>
                    <option value="1" <?= $allowOnline ? 'selected' : '' ?>>Sim</option>
                </select>
            </div>

            <div class="lc-form__actions" style="grid-column: 1 / -1; padding-top: 4px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar alterações</button>
                <a class="lc-btn lc-btn--secondary" href="/professionals">Voltar</a>
            </div>
        </form>

        <div style="margin-top: 14px;">
            <form method="post" action="/professionals/delete" onsubmit="return confirm('Excluir (inativar) este profissional?');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)$id ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Excluir profissional</button>
            </form>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
