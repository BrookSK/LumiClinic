<?php
$title = 'Documentos LGPD';
$csrf = $_SESSION['_csrf'] ?? '';
$portalDocs = $portal_docs ?? [];
$systemDocs = $system_docs ?? [];
$error = $error ?? '';
$success = $success ?? '';

ob_start();
?>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Documentos LGPD</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:650px;line-height:1.5;">
    Gerencie os termos e políticas que precisam ser aceitos. Documentos marcados como obrigatórios bloqueiam o acesso até serem aceitos.
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Portal do Paciente -->
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
        <div>
            <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);">Termos do Portal do Paciente</div>
            <div style="font-size:12px;color:rgba(31,41,55,.45);margin-top:2px;">Documentos que os pacientes precisam aceitar ao acessar o portal online.</div>
        </div>
        <a class="lc-btn lc-btn--primary lc-btn--sm" href="/settings/lgpd/edit?scope=patient_portal">+ Novo</a>
    </div>

    <?php if (empty($portalDocs)): ?>
        <div style="text-align:center;padding:20px;color:rgba(31,41,55,.40);font-size:13px;">Nenhum documento cadastrado para o portal.</div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px;">
            <?php foreach ($portalDocs as $d): ?>
                <?php
                $st = (string)($d['status'] ?? '');
                $req = (int)($d['is_required'] ?? 0) === 1;
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);"><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($req): ?>
                            <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(185,28,28,.08);color:#b91c1c;border:1px solid rgba(185,28,28,.16);">Obrigatório</span>
                        <?php endif; ?>
                        <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:<?= $st === 'active' ? 'rgba(22,163,74,.12)' : 'rgba(107,114,128,.10)' ?>;color:<?= $st === 'active' ? '#16a34a' : '#6b7280' ?>;border:1px solid <?= $st === 'active' ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;"><?= $st === 'active' ? 'Ativo' : 'Inativo' ?></span>
                    </div>
                    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/lgpd/edit?id=<?= (int)($d['id'] ?? 0) ?>&scope=patient_portal">Editar</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Equipe Interna -->
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
        <div>
            <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);">Termos da Equipe Interna</div>
            <div style="font-size:12px;color:rgba(31,41,55,.45);margin-top:2px;">Documentos que os funcionários da clínica precisam aceitar ao fazer login.</div>
        </div>
        <a class="lc-btn lc-btn--primary lc-btn--sm" href="/settings/lgpd/edit?scope=system_user">+ Novo</a>
    </div>

    <?php if (empty($systemDocs)): ?>
        <div style="text-align:center;padding:20px;color:rgba(31,41,55,.40);font-size:13px;">Nenhum documento cadastrado para a equipe.</div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px;">
            <?php foreach ($systemDocs as $d): ?>
                <?php
                $st = (string)($d['status'] ?? '');
                $req = (int)($d['is_required'] ?? 0) === 1;
                $role = trim((string)($d['target_role_code'] ?? ''));
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <span style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);"><?= htmlspecialchars((string)($d['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($role !== ''): ?>
                            <span style="font-size:11px;color:rgba(31,41,55,.45);">Papel: <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($req): ?>
                            <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(185,28,28,.08);color:#b91c1c;border:1px solid rgba(185,28,28,.16);">Obrigatório</span>
                        <?php endif; ?>
                        <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:<?= $st === 'active' ? 'rgba(22,163,74,.12)' : 'rgba(107,114,128,.10)' ?>;color:<?= $st === 'active' ? '#16a34a' : '#6b7280' ?>;border:1px solid <?= $st === 'active' ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;"><?= $st === 'active' ? 'Ativo' : 'Inativo' ?></span>
                    </div>
                    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/lgpd/edit?id=<?= (int)($d['id'] ?? 0) ?>&scope=system_user">Editar</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
