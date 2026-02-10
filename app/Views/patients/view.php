<?php
$title = 'Paciente';
$patient = $patient ?? null;
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Perfil</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients">Voltar</a>
        <a class="lc-btn lc-btn--secondary" href="/medical-records?patient_id=<?= (int)($patient['id'] ?? 0) ?>">Prontuário</a>
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
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
