<?php
/** @var array<string,mixed> $row */
/** @var string $error */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Editar profissional';

$id = (int)($row['id'] ?? 0);
$name = (string)($row['name'] ?? '');
$specialty = (string)($row['specialty'] ?? '');
$allowOnline = (int)($row['allow_online_booking'] ?? 0) === 1;

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
        return true;
    }

    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) {
            return false;
        }
        return in_array($permissionCode, $permissions['allow'], true);
    }

    return in_array($permissionCode, $permissions, true);
};

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
        <?php if ($can('professionals.manage')): ?>
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
        <?php else: ?>
            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 2fr 2fr 1fr;">
                <div>
                    <div class="lc-muted">Nome</div>
                    <div><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <div class="lc-muted">Especialidade</div>
                    <div><?= htmlspecialchars($specialty, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div>
                    <div class="lc-muted">Agendamento online?</div>
                    <div><?= $allowOnline ? 'Sim' : 'Não' ?></div>
                </div>
            </div>

            <div class="lc-form__actions" style="padding-top: 14px;">
                <a class="lc-btn lc-btn--secondary" href="/professionals">Voltar</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
