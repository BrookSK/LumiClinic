<?php
$title = 'Pacientes';
$csrf = $_SESSION['_csrf'] ?? '';
$patients = $patients ?? [];
$q = $q ?? '';
$src = trim((string)($_GET['src'] ?? ''));
$terminology = isset($terminology) && is_array($terminology) ? $terminology : [];
$patientLabel = trim((string)($terminology['patient_label'] ?? 'Paciente'));
$patientsLabel = $patientLabel !== '' ? ($patientLabel . 's') : 'Pacientes';
$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 25;
$hasNext = isset($has_next) ? (bool)$has_next : false;
$originId = isset($origin_id) ? (int)$origin_id : 0;
$patientOrigins = $patient_origins ?? [];

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
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Gestão de <?= htmlspecialchars(mb_strtolower($patientsLabel, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-flex lc-gap-sm lc-flex--center lc-flex--wrap">
        <form method="get" action="/patients" class="lc-flex lc-gap-sm" style="align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="per_page" value="<?= (int)$perPage ?>" />
            <input type="hidden" name="page" value="1" />
            <input class="lc-input" style="width:220px;" type="text" name="q" value="<?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome, email ou telefone" />
            <select class="lc-select" name="origin_id" style="width:160px;">
                <option value="">Todas origens</option>
                <?php foreach ($patientOrigins as $po): ?>
                    <option value="<?= (int)($po['id'] ?? 0) ?>" <?= $originId === (int)($po['id'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($po['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <button class="lc-btn lc-btn--secondary" type="submit">Buscar</button>
        </form>
        <?php if ($can('patients.create')): ?>
            <a class="lc-btn lc-btn--primary" href="/patients/create">Novo <?= htmlspecialchars(mb_strtolower($patientLabel, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
    </div>

    <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
        <div class="lc-muted">Página <?= (int)$page ?></div>
        <div class="lc-flex lc-gap-sm">
            <?php if ($page > 1): ?>
                <a class="lc-btn lc-btn--secondary" href="/patients?q=<?= urlencode((string)$q) ?>&origin_id=<?= (int)$originId ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
            <?php endif; ?>
            <?php if ($hasNext): ?>
                <a class="lc-btn lc-btn--secondary" href="/patients?q=<?= urlencode((string)$q) ?>&origin_id=<?= (int)$originId ?>&per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($src === 'quick' && trim((string)$q) !== ''): ?>
    <div class="lc-alert lc-alert--info" style="margin-bottom:12px;">
        Busca rápida: procurando <strong>pacientes</strong> por <strong><?= htmlspecialchars((string)$q, ENT_QUOTES, 'UTF-8') ?></strong>.
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__title">Lista</div>

    <?php
        $statusLabelMap = [
            'active' => 'Ativo',
            'disabled' => 'Desativado',
            'inactive' => 'Inativo',
        ];
    ?>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Origem</th>
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
                    <td><?= htmlspecialchars((string)($p['origin_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($statusLabelMap[(string)($p['status'] ?? '')] ?? (string)($p['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="lc-flex lc-flex--wrap" style="gap:8px;">
                        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)$p['id'] ?>">Abrir</a>
                        <?php if ($can('patients.update')): ?>
                            <a class="lc-btn lc-btn--secondary" href="/patients/edit?id=<?= (int)$p['id'] ?>">Editar</a>
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
