<?php
/** @var list<array<string,mixed>> $professionals */
/** @var int $professional_id */
/** @var list<array<string,mixed>> $items */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Regras de Agenda';

$weekdayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

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

<div class="lc-card" style="margin-bottom: 16px;">
    <form method="get" action="/schedule-rules" class="lc-form lc-flex lc-gap-md" style="align-items:end;">
        <div class="lc-field" style="min-width: 320px;">
            <label class="lc-label">Profissional</label>
            <select class="lc-select" name="professional_id" onchange="this.form.submit()">
                <option value="0">Selecione</option>
                <?php foreach ($professionals as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professional_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ((int)$professional_id > 0): ?>
    <?php if ($can('schedule_rules.manage')): ?>
        <div class="lc-card" style="margin-bottom: 16px;">
            <div class="lc-card__header">Nova regra</div>
            <div class="lc-card__body">
                <form method="post" action="/schedule-rules/create" class="lc-form lc-grid lc-grid--5 lc-gap-grid" style="align-items:end;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />

                <div class="lc-field">
                    <label class="lc-label">Dia</label>
                    <select class="lc-select" name="weekday">
                        <?php for ($i=0; $i<=6; $i++): ?>
                            <option value="<?= $i ?>"><?= htmlspecialchars($weekdayNames[$i], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Início</label>
                    <input class="lc-input" type="time" name="start_time" required />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Fim</label>
                    <input class="lc-input" type="time" name="end_time" required />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Intervalo (min)</label>
                    <input class="lc-input" type="number" name="interval_minutes" min="0" step="5" />
                </div>

                    <div>
                        <button class="lc-btn" type="submit">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="lc-card">
        <div class="lc-card__header">Regras cadastradas</div>
        <div class="lc-card__body">
            <?php if ($items === []): ?>
                <div class="lc-muted">Nenhuma regra.</div>
            <?php else: ?>
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Dia</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Intervalo</th>
                        <?php if ($can('schedule_rules.manage')): ?><th></th><?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= htmlspecialchars($weekdayNames[(int)$it['weekday']] ?? (string)$it['weekday'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(substr((string)$it['start_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(substr((string)$it['end_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $it['interval_minutes'] === null ? '-' : (int)$it['interval_minutes'] ?></td>
                            <?php if ($can('schedule_rules.manage')): ?>
                            <td>
                                <form method="post" action="/schedule-rules/delete" onsubmit="return confirm('Excluir esta regra?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                    <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                                    <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Excluir</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
