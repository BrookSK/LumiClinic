<?php
$title = 'Documentos LGPD';
$csrf = $_SESSION['_csrf'] ?? '';
$portalDocs = $portal_docs ?? [];
$systemDocs = $system_docs ?? [];
$error = $error ?? '';
$success = $success ?? '';

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Documentos LGPD</div>
    <a class="lc-btn lc-btn--primary" href="/settings/lgpd/edit">+ Novo documento</a>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-muted" style="margin-bottom:16px; font-size:13px; line-height:1.6;">
    Gerencie aqui os termos e documentos que precisam ser aceitos pelos pacientes (via portal) e pela equipe interna.
</div>

<!-- Termos do Portal do Paciente -->
<div class="lc-card" style="margin-bottom:16px;">
    <div class="lc-card__header lc-flex lc-flex--between lc-flex--center">
        <div>
            Termos do Portal do Paciente
            <span class="lc-badge lc-badge--secondary" style="margin-left:8px;"><?= count($portalDocs) ?></span>
        </div>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/lgpd/edit?scope=patient_portal">+ Novo</a>
    </div>
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($portalDocs)): ?>
            <div class="lc-muted" style="padding:16px;">Nenhum documento cadastrado. Crie um para que os pacientes aceitem ao acessar o portal.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Título</th>
                    <th>Obrigatório</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($portalDocs as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($d['is_required'] ?? 0) === 1 ? '<span class="lc-badge lc-badge--primary">Sim</span>' : '<span class="lc-badge lc-badge--secondary">Não</span>' ?></td>
                        <td>
                            <?php $st = (string)($d['status'] ?? ''); ?>
                            <span class="lc-badge <?= $st === 'active' ? 'lc-badge--success' : 'lc-badge--secondary' ?>">
                                <?= $st === 'active' ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/lgpd/edit?id=<?= (int)($d['id'] ?? 0) ?>&scope=patient_portal">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Termos da Equipe Interna -->
<div class="lc-card">
    <div class="lc-card__header lc-flex lc-flex--between lc-flex--center">
        <div>
            Termos da Equipe Interna
            <span class="lc-badge lc-badge--secondary" style="margin-left:8px;"><?= count($systemDocs) ?></span>
        </div>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/lgpd/edit?scope=system_user">+ Novo</a>
    </div>
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($systemDocs)): ?>
            <div class="lc-muted" style="padding:16px;">Nenhum documento cadastrado para a equipe.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Título</th>
                    <th>Papel alvo</th>
                    <th>Obrigatório</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($systemDocs as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($d['target_role_code'] ?? 'Todos'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($d['is_required'] ?? 0) === 1 ? '<span class="lc-badge lc-badge--primary">Sim</span>' : '<span class="lc-badge lc-badge--secondary">Não</span>' ?></td>
                        <td>
                            <?php $st = (string)($d['status'] ?? ''); ?>
                            <span class="lc-badge <?= $st === 'active' ? 'lc-badge--success' : 'lc-badge--secondary' ?>">
                                <?= $st === 'active' ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/lgpd/edit?id=<?= (int)($d['id'] ?? 0) ?>&scope=system_user">Editar</a>
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
