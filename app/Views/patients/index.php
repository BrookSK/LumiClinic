<?php
$title = 'Pacientes';
$csrf = $_SESSION['_csrf'] ?? '';
$patients = $patients ?? [];
$q = $q ?? '';
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:12px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Gestão de pacientes</div>
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <form method="get" action="/patients" style="display:flex; gap:10px; align-items:center;">
            <input class="lc-input" style="width:260px;" type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome, email ou telefone" />
            <button class="lc-btn lc-btn--secondary" type="submit">Buscar</button>
        </form>
        <a class="lc-btn lc-btn--primary" href="/patients/create">Novo paciente</a>
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
                    <td style="display:flex; gap:8px; flex-wrap:wrap;">
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
