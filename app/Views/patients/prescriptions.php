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
        <!-- Patient & prescriber info -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;padding:12px;border-radius:10px;background:rgba(0,0,0,.02);border:1px solid rgba(0,0,0,.06);">
            <div>
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                    <span style="font-weight:700;font-size:12px;color:#6b7280;">Paciente</span>
                    <a href="/patients/edit?id=<?= (int)($patient['id'] ?? 0) ?>" style="font-size:11px;color:rgba(99,102,241,.7);text-decoration:none;" title="Editar dados do paciente">✏️ editar</a>
                </div>
                <div style="font-size:13px;font-weight:600;color:#1f2937;"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <?php if (($patient['cpf'] ?? '') !== ''): ?><div style="font-size:12px;color:#6b7280;">CPF: <?= htmlspecialchars((string)$patient['cpf'], ENT_QUOTES, 'UTF-8') ?></div><?php else: ?><div style="font-size:11px;color:#d97706;">⚠ CPF não cadastrado — <a href="/patients/edit?id=<?= (int)($patient['id'] ?? 0) ?>" style="color:#d97706;">cadastrar</a></div><?php endif; ?>
                <?php if (($patient['address'] ?? '') !== ''): ?><div style="font-size:12px;color:#6b7280;"><?= htmlspecialchars((string)$patient['address'], ENT_QUOTES, 'UTF-8') ?></div><?php else: ?><div style="font-size:11px;color:#d97706;">⚠ Endereço não cadastrado — <a href="/patients/edit?id=<?= (int)($patient['id'] ?? 0) ?>" style="color:#d97706;">cadastrar</a></div><?php endif; ?>
            </div>
            <div id="prescriberInfo" style="display:none;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                    <span style="font-weight:700;font-size:12px;color:#6b7280;">Prescritor</span>
                    <a id="prescriberEditLink" href="#" style="font-size:11px;color:rgba(99,102,241,.7);text-decoration:none;" title="Editar dados do profissional">✏️ editar</a>
                </div>
                <div id="prescriberName" style="font-size:13px;font-weight:600;color:#1f2937;"></div>
                <div id="prescriberCouncil" style="font-size:12px;color:#6b7280;"></div>
                <div id="prescriberCouncilWarn" style="display:none;font-size:11px;color:#d97706;"></div>
            </div>
        </div>

        <form method="post" action="/patients/prescriptions/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Profissional que prescreve</label>
                    <select class="lc-select" name="professional_id" id="rxProfSelect" onchange="updatePrescriberInfo()">
                        <option value="">(selecione)</option>
                        <?php foreach ($professionals as $pr): ?>
                            <option value="<?= (int)$pr['id'] ?>" data-name="<?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?>" data-specialty="<?= htmlspecialchars((string)($pr['specialty'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-council="<?= htmlspecialchars((string)($pr['council_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Título</label>
                    <input class="lc-input" type="text" name="title" value="Receita" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Data de emissão</label>
                    <input class="lc-input" type="date" name="issued_at" value="<?= date('Y-m-d') ?>" required />
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

<script>
function updatePrescriberInfo() {
    var sel = document.getElementById('rxProfSelect');
    var info = document.getElementById('prescriberInfo');
    var nameEl = document.getElementById('prescriberName');
    var councilEl = document.getElementById('prescriberCouncil');
    var warnEl = document.getElementById('prescriberCouncilWarn');
    var editLink = document.getElementById('prescriberEditLink');
    if (!sel || !info) return;
    var opt = sel.options[sel.selectedIndex];
    if (sel.value === '') { info.style.display = 'none'; return; }
    info.style.display = 'block';
    if (nameEl) nameEl.textContent = opt.dataset.name || '';
    if (editLink) editLink.href = '/professionals/edit?id=' + sel.value;
    var parts = [];
    if (opt.dataset.specialty) parts.push(opt.dataset.specialty);
    if (opt.dataset.council) parts.push('Conselho: ' + opt.dataset.council);
    if (councilEl) councilEl.textContent = parts.join(' · ') || '';
    if (warnEl) {
        if (!opt.dataset.council) {
            warnEl.style.display = 'block';
            warnEl.innerHTML = '⚠ Nº do conselho não cadastrado — <a href="/professionals/edit?id=' + sel.value + '" style="color:#d97706;">cadastrar</a>';
        } else {
            warnEl.style.display = 'none';
        }
    }
}
</script>

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
