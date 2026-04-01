<?php
$title = 'Receituário';
$patient = $patient ?? null;
$prescriptions = $prescriptions ?? [];
$professionals = $professionals ?? [];
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? '';
$success = $success ?? '';

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) {
        if (in_array($p, $perms['deny'], true)) return false;
        return in_array($p, $perms['allow'], true);
    }
    return in_array($p, $perms, true);
};

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Receituário — <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao paciente</a>
        <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Prontuário</a>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($can('medical_records.create')): ?>
<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__header">Nova receita</div>
    <div class="lc-card__body">
        <form method="post" action="/patients/prescriptions/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr; align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Título</label>
                    <input class="lc-input" type="text" name="title" value="Receita" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Data de emissão</label>
                    <input class="lc-input" type="date" name="issued_at" value="<?= date('Y-m-d') ?>" required />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Profissional (opcional)</label>
                    <select class="lc-select" name="professional_id">
                        <option value="">(opcional)</option>
                        <?php foreach ($professionals as $pr): ?>
                            <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="lc-field" style="margin-top:10px;">
                <label class="lc-label">Conteúdo da receita</label>
                <textarea class="lc-input" name="body" rows="8" required placeholder="Ex: Amoxicilina 500mg — 1 cápsula de 8 em 8 horas por 7 dias..."></textarea>
            </div>

            <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar receita</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__header">Receitas emitidas</div>
    <div class="lc-card__body">
        <?php if ($prescriptions === []): ?>
            <div class="lc-muted">Nenhuma receita emitida.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Título</th>
                    <th>Data</th>
                    <th>Profissional</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($prescriptions as $rx): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($rx['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($rx['issued_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($rx['professional_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="lc-td-actions">
                            <div class="lc-flex lc-gap-sm">
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/patients/prescription/edit?id=<?= (int)$rx['id'] ?>">Editar</a>
                                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/patients/prescription/print?id=<?= (int)$rx['id'] ?>" target="_blank">Imprimir</a>
                                <?php if ($can('medical_records.create')): ?>
                                <form method="post" action="/patients/prescriptions/delete" onsubmit="return confirm('Excluir receita?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)$rx['id'] ?>" />
                                    <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />
                                    <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Excluir</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
