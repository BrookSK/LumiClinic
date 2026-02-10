<?php
$title = 'Editar papel';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$role = $role ?? null;
$catalog = $catalog ?? [];
$decisions = $decisions ?? ['allow' => [], 'deny' => []];

$allow = is_array($decisions['allow'] ?? null) ? $decisions['allow'] : [];
$deny = is_array($decisions['deny'] ?? null) ? $decisions['deny'] : [];

ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Permissões do papel</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$role): ?>
        <div class="lc-alert lc-alert--danger">Role não encontrada.</div>
    <?php else: ?>
        <div class="lc-badge lc-badge--primary" style="margin-bottom:12px;">
            <?= htmlspecialchars((string)$role['name'], ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars((string)$role['code'], ENT_QUOTES, 'UTF-8') ?>)
        </div>

        <?php if ((int)$role['is_editable'] !== 1): ?>
            <div class="lc-alert">Este papel é travado e não pode ser editado.</div>
        <?php endif; ?>

        <form method="post" action="/rbac/edit" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)$role['id'] ?>" />

            <label class="lc-label">Nome do papel</label>
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)$role['name'], ENT_QUOTES, 'UTF-8') ?>" <?= ((int)$role['is_editable'] !== 1) ? 'disabled' : '' ?> />

            <div class="lc-table-wrap" style="margin-top:14px;">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Módulo</th>
                        <th>Ação</th>
                        <th>Permissão</th>
                        <th>Allow</th>
                        <th>Deny</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($catalog as $p): ?>
                        <?php
                        $code = (string)$p['code'];
                        $isAllow = in_array($code, $allow, true);
                        $isDeny = in_array($code, $deny, true);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$p['module'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$p['action'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div style="font-weight:650;"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="lc-muted" style="font-size:12px;">
                                    <?= htmlspecialchars((string)($p['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>
                            <td>
                                <input type="checkbox" name="allow[]" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $isAllow ? 'checked' : '' ?> <?= ((int)$role['is_editable'] !== 1) ? 'disabled' : '' ?> />
                            </td>
                            <td>
                                <input type="checkbox" name="deny[]" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $isDeny ? 'checked' : '' ?> <?= ((int)$role['is_editable'] !== 1) ? 'disabled' : '' ?> />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <?php if ((int)$role['is_editable'] === 1): ?>
                    <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <?php endif; ?>
                <a class="lc-btn lc-btn--secondary" href="/rbac">Voltar</a>
            </div>
        </form>

        <?php if ((int)$role['is_editable'] === 1): ?>
            <form method="post" action="/rbac/reset" style="margin-top:10px;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)$role['id'] ?>" />
                <button class="lc-btn lc-btn--danger" type="submit">Resetar padrão</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
