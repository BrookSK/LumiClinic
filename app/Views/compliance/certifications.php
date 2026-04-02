<?php
$title = 'Certificações';
$csrf = $_SESSION['_csrf'] ?? '';
$policies = $policies ?? [];
$controls = $controls ?? [];
$users = $users ?? [];
$error = $error ?? '';

$can = function (string $pc): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $p = $_SESSION['permissions'] ?? [];
    if (!is_array($p)) return false;
    if (isset($p['allow'],$p['deny'])&&is_array($p['allow'])&&is_array($p['deny'])) {
        if (in_array($pc,$p['deny'],true)) return false;
        return in_array($pc,$p['allow'],true);
    }
    return in_array($pc,$p,true);
};

$policyStatusLabel = ['draft'=>'Rascunho','active'=>'Ativa','retired'=>'Desativada'];
$policyStatusColor = ['draft'=>'#6b7280','active'=>'#16a34a','retired'=>'#b91c1c'];
$controlStatusLabel = ['planned'=>'Planejado','implemented'=>'Implementado','tested'=>'Testado','failed'=>'Falhou'];
$controlStatusColor = ['planned'=>'#6b7280','implemented'=>'#16a34a','tested'=>'#815901','failed'=>'#b91c1c'];

$userMap = [];
foreach ($users as $u) {
    $uid = (int)($u['id'] ?? 0);
    $nm = trim((string)($u['name'] ?? ''));
    $em = trim((string)($u['email'] ?? ''));
    $userMap[$uid] = $nm !== '' ? $nm : ($em !== '' ? $em : 'Usuário #' . $uid);
}

ob_start();
?>

<style>
.cert-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.cert-section__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px}
.cert-section__desc{font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;line-height:1.5}
.cert-card{padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);margin-bottom:10px;transition:all 160ms ease}
.cert-card:hover{border-color:rgba(129,89,1,.18)}
.cert-card__head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.cert-card__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.96)}
.cert-card__code{font-size:12px;color:rgba(31,41,55,.45);font-family:monospace}
.cert-card__meta{display:flex;gap:12px;flex-wrap:wrap;margin-top:6px;font-size:12px;color:rgba(31,41,55,.55)}
.cert-badge{display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase}
.cert-edit{margin-top:10px;padding:14px;border-radius:12px;border:1px solid rgba(238,184,16,.18);background:rgba(253,229,159,.06)}
.cert-edit summary{list-style:none;cursor:pointer;font-size:12px;font-weight:650;color:rgba(129,89,1,1)}
.cert-edit summary::-webkit-details-marker{display:none}
.cert-empty{text-align:center;padding:30px 20px;color:rgba(31,41,55,.40);font-size:13px}
</style>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Certificações</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:650px;line-height:1.5;">
    Gerencie as políticas de segurança e compliance da clínica. Cadastre políticas (ex: LGPD, ISO 27001) e os controles que garantem o cumprimento de cada uma.
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- POLÍTICAS -->
<div class="cert-section">
    <div class="cert-section__title">Políticas</div>
    <div class="cert-section__desc">Políticas são documentos que definem regras e diretrizes (ex: Política de Privacidade, Política de Segurança da Informação).</div>

    <?php if ($can('compliance.policies.create')): ?>
    <div id="newPolicyForm" style="display:none;margin-bottom:14px;">
        <div style="padding:16px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
            <div style="font-weight:750;font-size:13px;margin-bottom:10px;">Nova política</div>
            <form method="post" action="/compliance/certifications/policies/create">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <div style="display:grid;grid-template-columns:1fr 2fr 140px;gap:12px;align-items:end;">
                    <div class="lc-field"><label class="lc-label">Código</label><input class="lc-input" type="text" name="code" placeholder="ex: lgpd-01" required /></div>
                    <div class="lc-field"><label class="lc-label">Título</label><input class="lc-input" type="text" name="title" required placeholder="Ex: Política de Privacidade" /></div>
                    <div class="lc-field"><label class="lc-label">Status</label>
                        <select class="lc-select" name="status"><option value="draft">Rascunho</option><option value="active">Ativa</option></select></div>
                </div>
                <details style="margin-top:8px;">
                    <summary style="font-size:12px;color:rgba(31,41,55,.45);cursor:pointer;list-style:none;">Mais opções</summary>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:8px;">
                        <div class="lc-field"><label class="lc-label">Descrição</label><textarea class="lc-input" name="description" rows="2"></textarea></div>
                        <div class="lc-field"><label class="lc-label">Responsável</label>
                            <select class="lc-select" name="owner_user_id"><option value="">Nenhum</option>
                            <?php foreach ($users as $u): ?><option value="<?= (int)($u['id'] ?? 0) ?>"><?= htmlspecialchars($userMap[(int)($u['id'] ?? 0)] ?? '', ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="lc-field"><label class="lc-label">Versão</label><input class="lc-input" type="number" name="version" min="1" value="1" /></div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px;">
                        <div class="lc-field"><label class="lc-label">Revisado em</label><input class="lc-input" type="date" name="reviewed_at" /></div>
                        <div class="lc-field"><label class="lc-label">Próxima revisão</label><input class="lc-input" type="date" name="next_review_at" /></div>
                    </div>
                </details>
                <div style="margin-top:10px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Criar política</button></div>
            </form>
        </div>
    </div>
    <div style="margin-bottom:14px;"><button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('newPolicyForm');f.style.display=f.style.display==='none'?'block':'none';">+ Nova política</button></div>
    <?php endif; ?>

    <?php if ($policies === []): ?>
        <div class="cert-empty">Nenhuma política cadastrada.</div>
    <?php else: ?>
        <?php foreach ($policies as $p): ?>
            <?php
            $pid = (int)($p['id'] ?? 0);
            $pst = (string)($p['status'] ?? 'draft');
            $pstLbl = $policyStatusLabel[$pst] ?? $pst;
            $pstClr = $policyStatusColor[$pst] ?? '#6b7280';
            $ownerId = (int)($p['owner_user_id'] ?? 0);
            $ownerName = $ownerId > 0 ? ($userMap[$ownerId] ?? '—') : '—';
            $reviewedAt = trim((string)($p['reviewed_at'] ?? ''));
            if ($reviewedAt === '0000-00-00 00:00:00') $reviewedAt = '';
            $nextReviewAt = trim((string)($p['next_review_at'] ?? ''));
            if ($nextReviewAt === '0000-00-00 00:00:00') $nextReviewAt = '';
            ?>
            <div class="cert-card">
                <div class="cert-card__head">
                    <div>
                        <span class="cert-card__title"><?= htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="cert-card__code"><?= htmlspecialchars((string)($p['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <span class="cert-badge" style="background:<?= $pstClr ?>18;color:<?= $pstClr ?>;border:1px solid <?= $pstClr ?>30"><?= htmlspecialchars($pstLbl, ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="font-size:11px;color:rgba(31,41,55,.40);">v<?= (int)($p['version'] ?? 1) ?></span>
                    </div>
                </div>
                <div class="cert-card__meta">
                    <span>Responsável: <?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($reviewedAt !== ''): ?><span>Revisado: <?= htmlspecialchars(date('d/m/Y', strtotime($reviewedAt)), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    <?php if ($nextReviewAt !== ''): ?><span>Próxima revisão: <?= htmlspecialchars(date('d/m/Y', strtotime($nextReviewAt)), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                </div>
                <?php if ($can('compliance.policies.update')): ?>
                <details class="cert-edit">
                    <summary>Editar política</summary>
                    <form method="post" action="/compliance/certifications/policies/update" style="margin-top:10px;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="id" value="<?= $pid ?>" />
                        <div style="display:grid;grid-template-columns:2fr 140px 80px;gap:12px;align-items:end;">
                            <div class="lc-field"><label class="lc-label">Título</label><input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($p['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required /></div>
                            <div class="lc-field"><label class="lc-label">Status</label>
                                <select class="lc-select" name="status"><?php foreach ($policyStatusLabel as $k=>$v): ?><option value="<?= $k ?>" <?= $pst === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                            <div class="lc-field"><label class="lc-label">Versão</label><input class="lc-input" type="number" name="version" min="1" value="<?= (int)($p['version'] ?? 1) ?>" /></div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:4px;">
                            <div class="lc-field"><label class="lc-label">Responsável</label>
                                <select class="lc-select" name="owner_user_id"><option value="">Nenhum</option>
                                <?php foreach ($users as $u): ?><option value="<?= (int)($u['id'] ?? 0) ?>" <?= $ownerId === (int)($u['id'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($userMap[(int)($u['id'] ?? 0)] ?? '', ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="lc-field"><label class="lc-label">Revisado em</label><input class="lc-input" type="date" name="reviewed_at" value="<?= $reviewedAt !== '' ? htmlspecialchars(substr($reviewedAt, 0, 10), ENT_QUOTES, 'UTF-8') : '' ?>" /></div>
                            <div class="lc-field"><label class="lc-label">Próxima revisão</label><input class="lc-input" type="date" name="next_review_at" value="<?= $nextReviewAt !== '' ? htmlspecialchars(substr($nextReviewAt, 0, 10), ENT_QUOTES, 'UTF-8') : '' ?>" /></div>
                        </div>
                        <div style="margin-top:10px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button></div>
                    </form>
                </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- CONTROLES -->
<div class="cert-section">
    <div class="cert-section__title">Controles</div>
    <div class="cert-section__desc">Controles são ações ou medidas que garantem o cumprimento das políticas (ex: criptografia de dados, backup diário, controle de acesso).</div>

    <?php if ($can('compliance.controls.create')): ?>
    <div id="newControlForm" style="display:none;margin-bottom:14px;">
        <div style="padding:16px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
            <div style="font-weight:750;font-size:13px;margin-bottom:10px;">Novo controle</div>
            <form method="post" action="/compliance/certifications/controls/create">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <div style="display:grid;grid-template-columns:1fr 1fr 2fr 140px;gap:12px;align-items:end;">
                    <div class="lc-field"><label class="lc-label">Política</label>
                        <select class="lc-select" name="policy_id"><option value="">Nenhuma</option>
                        <?php foreach ($policies as $pp): ?><option value="<?= (int)($pp['id'] ?? 0) ?>"><?= htmlspecialchars(trim((string)($pp['code'] ?? '') . ' - ' . (string)($pp['title'] ?? '')), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="lc-field"><label class="lc-label">Código</label><input class="lc-input" type="text" name="code" placeholder="ex: ctrl-01" required /></div>
                    <div class="lc-field"><label class="lc-label">Título</label><input class="lc-input" type="text" name="title" required placeholder="Ex: Criptografia de dados" /></div>
                    <div class="lc-field"><label class="lc-label">Status</label>
                        <select class="lc-select" name="status"><option value="planned">Planejado</option><option value="implemented">Implementado</option><option value="tested">Testado</option></select></div>
                </div>
                <details style="margin-top:8px;">
                    <summary style="font-size:12px;color:rgba(31,41,55,.45);cursor:pointer;list-style:none;">Mais opções</summary>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:8px;">
                        <div class="lc-field"><label class="lc-label">Descrição</label><textarea class="lc-input" name="description" rows="2"></textarea></div>
                        <div class="lc-field"><label class="lc-label">Responsável</label>
                            <select class="lc-select" name="owner_user_id"><option value="">Nenhum</option>
                            <?php foreach ($users as $u): ?><option value="<?= (int)($u['id'] ?? 0) ?>"><?= htmlspecialchars($userMap[(int)($u['id'] ?? 0)] ?? '', ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="lc-field"><label class="lc-label">Link de evidência</label><input class="lc-input" type="url" name="evidence_url" placeholder="https://..." /></div>
                    </div>
                </details>
                <div style="margin-top:10px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Criar controle</button></div>
            </form>
        </div>
    </div>
    <div style="margin-bottom:14px;"><button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('newControlForm');f.style.display=f.style.display==='none'?'block':'none';">+ Novo controle</button></div>
    <?php endif; ?>

    <?php if ($controls === []): ?>
        <div class="cert-empty">Nenhum controle cadastrado.</div>
    <?php else: ?>
        <?php foreach ($controls as $c): ?>
            <?php
            $cid = (int)($c['id'] ?? 0);
            $cst = (string)($c['status'] ?? 'planned');
            $cstLbl = $controlStatusLabel[$cst] ?? $cst;
            $cstClr = $controlStatusColor[$cst] ?? '#6b7280';
            $cpid = (int)($c['policy_id'] ?? 0);
            $cpLabel = '—';
            foreach ($policies as $pp) { if ((int)($pp['id'] ?? 0) === $cpid) { $cpLabel = trim((string)($pp['code'] ?? '') . ' - ' . (string)($pp['title'] ?? '')); break; } }
            $cOwnerId = (int)($c['owner_user_id'] ?? 0);
            $cOwnerName = $cOwnerId > 0 ? ($userMap[$cOwnerId] ?? '—') : '—';
            $evUrl = trim((string)($c['evidence_url'] ?? ''));
            $lastTested = trim((string)($c['last_tested_at'] ?? ''));
            if ($lastTested === '0000-00-00 00:00:00') $lastTested = '';
            ?>
            <div class="cert-card">
                <div class="cert-card__head">
                    <div>
                        <span class="cert-card__title"><?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="cert-card__code"><?= htmlspecialchars((string)($c['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <span class="cert-badge" style="background:<?= $cstClr ?>18;color:<?= $cstClr ?>;border:1px solid <?= $cstClr ?>30"><?= htmlspecialchars($cstLbl, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="cert-card__meta">
                    <span>Política: <?= htmlspecialchars($cpLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span>Responsável: <?= htmlspecialchars($cOwnerName, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($evUrl !== ''): ?><span><a href="<?= htmlspecialchars($evUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="color:rgba(129,89,1,1);font-weight:600;">Evidência</a></span><?php endif; ?>
                    <?php if ($lastTested !== ''): ?><span>Testado: <?= htmlspecialchars(date('d/m/Y', strtotime($lastTested)), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                </div>
                <?php if ($can('compliance.controls.update')): ?>
                <details class="cert-edit">
                    <summary>Editar controle</summary>
                    <form method="post" action="/compliance/certifications/controls/update" style="margin-top:10px;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="id" value="<?= $cid ?>" />
                        <div style="display:grid;grid-template-columns:1fr 2fr 140px;gap:12px;align-items:end;">
                            <div class="lc-field"><label class="lc-label">Política</label>
                                <select class="lc-select" name="policy_id"><option value="">Nenhuma</option>
                                <?php foreach ($policies as $pp): ?><option value="<?= (int)($pp['id'] ?? 0) ?>" <?= $cpid === (int)($pp['id'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars(trim((string)($pp['code'] ?? '') . ' - ' . (string)($pp['title'] ?? '')), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="lc-field"><label class="lc-label">Título</label><input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required /></div>
                            <div class="lc-field"><label class="lc-label">Status</label>
                                <select class="lc-select" name="status"><?php foreach ($controlStatusLabel as $k=>$v): ?><option value="<?= $k ?>" <?= $cst === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:4px;">
                            <div class="lc-field"><label class="lc-label">Responsável</label>
                                <select class="lc-select" name="owner_user_id"><option value="">Nenhum</option>
                                <?php foreach ($users as $u): ?><option value="<?= (int)($u['id'] ?? 0) ?>" <?= $cOwnerId === (int)($u['id'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($userMap[(int)($u['id'] ?? 0)] ?? '', ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="lc-field"><label class="lc-label">Evidência</label><input class="lc-input" type="url" name="evidence_url" value="<?= htmlspecialchars($evUrl, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://..." /></div>
                            <div class="lc-field"><label class="lc-label">Último teste</label><input class="lc-input" type="date" name="last_tested_at" value="<?= $lastTested !== '' ? htmlspecialchars(substr($lastTested, 0, 10), ENT_QUOTES, 'UTF-8') : '' ?>" /></div>
                        </div>
                        <div style="margin-top:10px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button></div>
                    </form>
                </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
