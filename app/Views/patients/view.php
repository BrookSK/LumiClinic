<?php
$title = 'Paciente';
$patient = $patient ?? null;
$patientUser = $patient_user ?? null;
$portalDocs = $portal_legal_docs ?? [];
$portalAcceptances = $portal_legal_acceptances ?? [];

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

$statusLabelMap = [
    'active' => 'Ativo',
    'disabled' => 'Desativado',
    'inactive' => 'Inativo',
];

$acceptMap = [];
if (is_array($portalAcceptances)) {
    foreach ($portalAcceptances as $a) {
        $did = (int)($a['document_id'] ?? 0);
        if ($did > 0) {
            $acceptMap[$did] = $a;
        }
    }
}
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Perfil</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients">Voltar</a>
        <?php if ($can('anamnesis.fill')): ?>
            <a class="lc-btn lc-btn--secondary" href="/anamnesis?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Anamnese</a>
        <?php endif; ?>
        <?php if ($can('patients.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/patients/clinical-sheet?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Alertas Clínicos</a>
        <?php endif; ?>
        <?php if ($can('finance.sales.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/finance/sales?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Orçamentos</a>
        <?php endif; ?>
        <?php if ($can('medical_records.read')): ?>
            <a class="lc-btn lc-btn--primary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Prontuário</a>
        <?php endif; ?>
        <?php if ($can('medical_images.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Imagens</a>
        <?php endif; ?>
        <?php if ($can('patients.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/patients/timeline?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Linha do tempo</a>
        <?php endif; ?>
        <?php if ($can('medical_records.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/patients/prescriptions?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Receituário</a>
        <?php endif; ?>
        <?php if ($can('patients.read')): ?>
            <a class="lc-btn lc-btn--secondary" href="/patients/documents?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Documentos</a>
        <?php endif; ?>
        <?php if ($can('consent_terms.accept')): ?>
            <a class="lc-btn lc-btn--secondary" href="/consent?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Termos</a>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title" style="display:flex;align-items:center;justify-content:space-between;">
        <span id="patNameDisplay"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($can('patients.update')): ?>
            <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="togglePatientEdit()" id="btnEditPatient" style="font-size:11px;">✏️ Editar dados</button>
        <?php endif; ?>
    </div>

    <div class="lc-card__body">
        <!-- Read-only view -->
        <div id="patientReadView">
            <div class="lc-grid lc-grid--2 lc-gap-grid">
                <div><div class="lc-label">E-mail</div><div><?= htmlspecialchars((string)($patient['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="lc-label">Telefone</div><div><?= htmlspecialchars((string)($patient['phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="lc-label">Data de nascimento</div><div><?= htmlspecialchars((string)($patient['birth_date'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="lc-label">Sexo</div><div><?= htmlspecialchars((string)($patient['sex'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div></div>
                <div><div class="lc-label">CPF</div><div><?php if (isset($patient['cpf']) && (string)$patient['cpf'] !== ''): ?><?= htmlspecialchars((string)$patient['cpf'], ENT_QUOTES, 'UTF-8') ?><?php else: ?><?= isset($patient['cpf_last4']) && $patient['cpf_last4'] ? '***.' . htmlspecialchars((string)$patient['cpf_last4'], ENT_QUOTES, 'UTF-8') : '—' ?><?php endif; ?></div></div>
                <div><div class="lc-label">Status</div><div><?php $st = (string)($patient['status'] ?? ''); ?><?= htmlspecialchars((string)($statusLabelMap[$st] ?? $st), ENT_QUOTES, 'UTF-8') ?></div></div>
            </div>
            <div style="margin-top:14px;"><div class="lc-label">Endereço</div><div><?= nl2br(htmlspecialchars((string)($patient['address'] ?? '—'), ENT_QUOTES, 'UTF-8')) ?></div></div>
            <div style="margin-top:14px;"><div class="lc-label">Observações</div><div><?= nl2br(htmlspecialchars((string)($patient['notes'] ?? '—'), ENT_QUOTES, 'UTF-8')) ?></div></div>
        </div>

        <!-- Inline edit form (hidden by default) -->
        <?php if ($can('patients.update')): ?>
        <div id="patientEditView" style="display:none;">
            <form method="post" action="/patients/edit" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)($patient['id'] ?? 0) ?>" />
                <input type="hidden" name="_redirect" value="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>" />
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="lc-field"><label class="lc-label">Nome</label><input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required /></div>
                    <div class="lc-field"><label class="lc-label">E-mail</label><input class="lc-input" type="email" name="email" value="<?= htmlspecialchars((string)($patient['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
                    <div class="lc-field"><label class="lc-label">Telefone</label><input class="lc-input" type="text" name="phone" value="<?= htmlspecialchars((string)($patient['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
                    <div class="lc-field"><label class="lc-label">Data de nascimento</label><input class="lc-input" type="date" name="birth_date" value="<?= htmlspecialchars((string)($patient['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
                    <div class="lc-field"><label class="lc-label">Sexo</label><select class="lc-select" name="sex"><option value="">—</option><option value="M" <?= ($patient['sex'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option><option value="F" <?= ($patient['sex'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option></select></div>
                    <div class="lc-field"><label class="lc-label">CPF</label><input class="lc-input" type="text" name="cpf" value="<?= htmlspecialchars((string)($patient['cpf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
                </div>
                <div class="lc-field" style="margin-top:12px;"><label class="lc-label">Endereço</label><input class="lc-input" type="text" name="address" value="<?= htmlspecialchars((string)($patient['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
                <div class="lc-field" style="margin-top:12px;"><label class="lc-label">Observações</label><textarea class="lc-textarea" name="notes" rows="3"><?= htmlspecialchars((string)($patient['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="togglePatientEdit()">Cancelar</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePatientEdit() {
    var rv = document.getElementById('patientReadView');
    var ev = document.getElementById('patientEditView');
    var btn = document.getElementById('btnEditPatient');
    if (!rv || !ev) return;
    var editing = ev.style.display !== 'none';
    rv.style.display = editing ? 'block' : 'none';
    ev.style.display = editing ? 'none' : 'block';
    if (btn) btn.textContent = editing ? '✏️ Editar dados' : '✕ Cancelar';
}
</script>

<?php if (is_array($portalDocs) && $portalDocs !== []): ?>
    <div class="lc-card" style="margin-top:14px;">
        <div class="lc-card__title">LGPD / Termos do Portal</div>
        <div class="lc-card__body">
            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-bottom:12px;">
                <?php if ($can('clinics.read')): ?>
                    <a class="lc-btn lc-btn--secondary" href="/clinic/legal-documents">Configurar textos</a>
                <?php endif; ?>
            </div>

            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Obrigatório</th>
                            <th>Status</th>
                            <th>Aceito em</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($portalDocs as $d): ?>
                            <?php $did = (int)($d['id'] ?? 0); ?>
                            <?php $acc = $did > 0 && isset($acceptMap[$did]) ? $acceptMap[$did] : null; ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)($d['is_required'] ?? 0) === 1 ? 'Sim' : 'Não' ?></td>
                                <td><?= $acc ? 'Aceito' : 'Não aceito' ?></td>
                                <td><?= htmlspecialchars((string)($acc['accepted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($patientUser === null): ?>
                <div class="lc-alert lc-alert--info" style="margin-top:12px;">
                    Este paciente ainda não tem acesso ao portal.
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
