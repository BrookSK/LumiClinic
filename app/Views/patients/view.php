<?php
$title = 'Paciente';
$patient = $patient ?? null;
$patientUser = $patient_user ?? null;
$portalDocs = $portal_legal_docs ?? [];
$portalAcceptances = $portal_legal_acceptances ?? [];

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
        <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Prontuário</a>
        <a class="lc-btn lc-btn--secondary" href="/finance/sales?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Orçamentos</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/appointments?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Consultas</a>
        <a class="lc-btn lc-btn--secondary" href="/medical-images?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Imagens</a>
        <a class="lc-btn lc-btn--secondary" href="/anamnesis?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Anamnese</a>
        <a class="lc-btn lc-btn--secondary" href="/consent?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Termos</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/portal-access?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Acesso ao Portal</a>
        <a class="lc-btn lc-btn--primary" href="/patients/edit?id=<?= (int)($patient['id'] ?? 0) ?>">Editar</a>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

    <div class="lc-card__body">
        <div class="lc-grid lc-grid--2 lc-gap-grid">
            <div>
                <div class="lc-label">E-mail</div>
                <div><?= htmlspecialchars((string)($patient['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">Telefone</div>
                <div><?= htmlspecialchars((string)($patient['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">Data de nascimento</div>
                <div><?= htmlspecialchars((string)($patient['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">Sexo</div>
                <div><?= htmlspecialchars((string)($patient['sex'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">CPF</div>
                <div>
                    <?php if (isset($patient['cpf']) && (string)$patient['cpf'] !== ''): ?>
                        <?= htmlspecialchars((string)$patient['cpf'], ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        <?= isset($patient['cpf_last4']) && $patient['cpf_last4'] ? '***.' . htmlspecialchars((string)$patient['cpf_last4'], ENT_QUOTES, 'UTF-8') : '' ?>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div class="lc-label">Status</div>
                <div><?= htmlspecialchars((string)($patient['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div style="margin-top:14px;">
            <div class="lc-label">Endereço</div>
            <div><?= nl2br(htmlspecialchars((string)($patient['address'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
        </div>

        <div style="margin-top:14px;">
            <div class="lc-label">Observações</div>
            <div><?= nl2br(htmlspecialchars((string)($patient['notes'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
        </div>
    </div>
</div>

<?php if (is_array($portalDocs) && $portalDocs !== []): ?>
    <div class="lc-card" style="margin-top:14px;">
        <div class="lc-card__title">LGPD / Termos do Portal</div>
        <div class="lc-card__body">
            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-bottom:12px;">
                <a class="lc-btn lc-btn--secondary" href="/clinic/legal-documents">Configurar textos</a>
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
