<?php
$title = 'Feriados e Recesso';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$items = $items ?? [];

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
<?php if ($can('clinics.update')): ?>
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

        <label class="lc-label">Funcionamento</label>
        <select class="lc-select" name="is_open">
            <option value="0" selected>Fechado</option>
            <option value="1">Aberto</option>
        </select>

            <div class="lc-flex lc-gap-sm" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Adicionar</button>
            </div>
        </form>

        <div class="lc-flex lc-gap-sm" style="margin-top:10px;">
            <form method="post" action="/clinic/closed-days/ai" style="margin:0;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="year" value="<?= (int)date('Y') ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit" onclick="return confirm('Gerar feriados automaticamente com IA? Isso irá inserir/atualizar as datas do ano.');">Gerar feriados com IA</button>
            </form>
            <a class="lc-btn lc-btn--secondary" href="/clinic">Voltar</a>
        </div>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__title">Datas cadastradas</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>Data</th>
                <th>Motivo</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$it['closed_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php $open = isset($it['is_open']) && (int)$it['is_open'] === 1; ?>
                        <span class="lc-badge <?= $open ? 'lc-badge--success' : 'lc-badge--danger' ?>"><?= $open ? 'Aberto' : 'Fechado' ?></span>
                    </td>
                    <td>
                        <?php if ($can('clinics.update')): ?>
                            <form method="post" action="/clinic/closed-days/delete" style="margin:0;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit">Remover</button>
                            </form>
                        <?php else: ?>
                            <span style="opacity:.7;">-</span>
                        <?php endif; ?>
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
