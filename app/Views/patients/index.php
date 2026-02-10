<?php
$title = 'Pacientes';
$csrf = $_SESSION['_csrf'] ?? '';
$patients = $patients ?? [];
$q = $q ?? '';
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 25;
$hasNext = isset($has_next) ? (bool)$has_next : false;
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Gestão de pacientes</div>
    <div class="lc-flex lc-gap-sm lc-flex--center lc-flex--wrap">
        <form method="get" action="/patients" class="lc-flex lc-gap-sm" style="align-items:center;">
            <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
            <input type="hidden" name="page" value="1" />
            <input class="lc-input" style="width:260px;" type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome, email ou telefone" />
            <button class="lc-btn lc-btn--secondary" type="submit">Buscar</button>
        </form>
        <a class="lc-btn lc-btn--primary" href="/patients/create">Novo paciente</a>
    </div>

    <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
        <div class="lc-muted">Página <?= (int)$page ?></div>
        <div class="lc-flex lc-gap-sm">
            <?php if ($page > 1): ?>
                <a class="lc-btn lc-btn--secondary" href="/patients?q=<?= urlencode((string)$q) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
            <?php endif; ?>
            <?php if ($hasNext): ?>
                <a class="lc-btn lc-btn--secondary" href="/patients?q=<?= urlencode((string)$q) ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Lista</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td><?= (int)$p['id'] ?></td>
                    <td><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($p['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($p['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$p['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="lc-flex lc-flex--wrap" style="gap:8px;">
                        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)$p['id'] ?>">Abrir</a>
                        <a class="lc-btn lc-btn--secondary" href="/patients/edit?id=<?= (int)$p['id'] ?>">Editar</a>
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
