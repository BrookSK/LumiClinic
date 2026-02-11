<?php
$title = 'Admin do Sistema';
$items = $items ?? [];
$q = $q ?? '';
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Gestão de clínicas</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/sys/billing">Assinaturas</a>
        <a class="lc-btn lc-btn--primary" href="/sys/clinics/create">Nova clínica</a>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Clínicas</div>

    <div class="lc-card__body" style="padding-bottom:0;">
        <form method="get" action="/sys/clinics" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:center;">
            <input class="lc-input" style="width:320px;" type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome, identificação ou domínio" />
            <button class="lc-btn lc-btn--secondary" type="submit">Buscar</button>
            <?php if (trim((string)$q) !== ''): ?>
                <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Identificação</th>
                <th>Domínio</th>
                <th>Status</th>
                <th>Criada em</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                    $id = (int)($it['id'] ?? 0);
                    $status = (string)($it['status'] ?? '');
                    $statusLabel = match ($status) {
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                        default => ($status !== '' ? $status : '-'),
                    };
                ?>
                <tr>
                    <td><?= (int)$id ?></td>
                    <td>
                        <a class="lc-link" href="/sys/clinics/edit?id=<?= (int)$id ?>">
                            <?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars((string)($it['tenant_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['primary_domain'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$statusLabel, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$it['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="lc-flex lc-gap-sm lc-flex--wrap">
                            <a class="lc-btn lc-btn--secondary" href="/sys/clinics/edit?id=<?= (int)$id ?>">Ver / Editar</a>

                            <form method="post" action="/sys/clinics/set-status">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($_SESSION['_csrf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$id ?>" />
                                <input type="hidden" name="status" value="<?= ($status === 'active') ? 'inactive' : 'active' ?>" />
                                <button class="lc-btn lc-btn--secondary" type="submit"><?= ($status === 'active') ? 'Desativar' : 'Ativar' ?></button>
                            </form>

                            <form method="post" action="/sys/clinics/delete" onsubmit="return confirm('Tem certeza que deseja excluir esta clínica?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($_SESSION['_csrf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$id ?>" />
                                <button class="lc-btn lc-btn--danger" type="submit">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
