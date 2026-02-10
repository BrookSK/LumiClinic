<?php
$title = 'Papéis e Permissões';
$csrf = $_SESSION['_csrf'] ?? '';
$roles = $roles ?? [];
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">RBAC</div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Papéis da clínica</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Código</th>
                <th>Tipo</th>
                <th>Editável</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($roles as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$r['code'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$r['is_system'] === 1 ? 'Sistema' : 'Custom' ?></td>
                    <td><?= (int)$r['is_editable'] === 1 ? 'Sim' : 'Não' ?></td>
                    <td>
                        <a class="lc-btn lc-btn--secondary" href="/rbac/edit?id=<?= (int)$r['id'] ?>">Abrir</a>

                        <form method="post" action="/rbac/clone" class="lc-flex" style="gap:8px; align-items:center; margin-left:10px;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="from_role_id" value="<?= (int)$r['id'] ?>" />
                            <input class="lc-input" style="width:220px;" type="text" name="name" placeholder="Clonar como..." />
                            <button class="lc-btn lc-btn--primary" type="submit">Clonar</button>
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
