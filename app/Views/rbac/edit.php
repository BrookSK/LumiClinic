<?php
$title = 'Editar Papel';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$role = $role ?? null;
$catalog = $catalog ?? [];
$decisions = $decisions ?? ['allow' => [], 'deny' => []];

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

$allow = is_array($decisions['allow'] ?? null) ? $decisions['allow'] : [];
$deny = is_array($decisions['deny'] ?? null) ? $decisions['deny'] : [];

$moduleLabels = [
    'scheduling'=>'Agenda','patients'=>'Pacientes','medical_records'=>'Prontuários',
    'medical_images'=>'Imagens Clínicas','anamnesis'=>'Anamnese','consent_terms'=>'Consentimento',
    'finance'=>'Financeiro','stock'=>'Estoque','marketing'=>'Marketing',
    'rbac'=>'Permissões','users'=>'Usuários','settings'=>'Configurações',
    'clinics'=>'Clínica','audit'=>'Auditoria','compliance'=>'Compliance',
    'reports'=>'Relatórios','procedures'=>'Procedimentos','schedule_rules'=>'Regras de Agenda',
    'professionals'=>'Profissionais','blocks'=>'Bloqueios','services'=>'Serviços',
    'medical_record_templates'=>'Modelos Prontuário',
];
$actionLabels = [
    'read'=>'Visualizar','create'=>'Criar','update'=>'Editar','delete'=>'Excluir',
    'cancel'=>'Cancelar','manage'=>'Gerenciar','finalize'=>'Finalizar','fill'=>'Preencher',
    'accept'=>'Aceitar','ops'=>'Operações','refund'=>'Estornar','export'=>'Exportar',
];

// Agrupar por módulo
$byModule = [];
foreach ($catalog as $p) {
    $mod = (string)$p['module'];
    $byModule[$mod][] = $p;
}

$isEditable = $role && (int)($role['is_editable'] ?? 0) === 1;

ob_start();
?>

<style>
.rbac-back{display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px}
.rbac-back:hover{color:rgba(129,89,1,1)}
.rbac-module{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:12px}
.rbac-module__head{display:flex;align-items:center;justify-content:space-between;gap:10px;cursor:pointer;list-style:none}
.rbac-module__head::-webkit-details-marker{display:none}
.rbac-module__name{font-weight:750;font-size:14px;color:rgba(31,41,55,.90)}
.rbac-module__count{font-size:12px;color:rgba(31,41,55,.40)}
.rbac-module__chev{color:rgba(31,41,55,.35);transition:transform 160ms ease}
.rbac-module[open] .rbac-module__chev{transform:rotate(180deg)}
.rbac-perm{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(17,24,39,.05)}
.rbac-perm:last-child{border-bottom:none}
.rbac-perm__info{flex:1;min-width:0}
.rbac-perm__action{font-weight:700;font-size:13px;color:rgba(31,41,55,.85)}
.rbac-perm__code{font-size:11px;color:rgba(31,41,55,.40);font-family:monospace}
.rbac-perm__checks{display:flex;gap:16px;flex-shrink:0}
.rbac-perm__check{display:flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:rgba(31,41,55,.60)}
.rbac-perm__check input{width:18px;height:18px}
</style>

<a href="/rbac" class="rbac-back">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para papéis
</a>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!$role): ?>
    <div class="lc-alert lc-alert--danger">Papel não encontrado.</div>
<?php else: ?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)$role['name'], ENT_QUOTES, 'UTF-8') ?></div>
        <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(107,114,128,.10);color:#6b7280;border:1px solid rgba(107,114,128,.18);"><?= htmlspecialchars((string)$role['code'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php if (!$isEditable): ?>
            <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:10px;font-weight:700;background:rgba(185,28,28,.08);color:#b91c1c;border:1px solid rgba(185,28,28,.16);">Somente leitura</span>
        <?php endif; ?>
    </div>
    <?php if ($isEditable && $can('rbac.manage')): ?>
    <div style="display:flex;gap:8px;">
        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" id="lcSelectAllAllow">✓ Permitir tudo</button>
        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" id="lcClearAll">Limpar tudo</button>
    </div>
    <?php endif; ?>
</div>

<?php if ($can('rbac.manage')): ?>
<form method="post" action="/rbac/edit" class="lc-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="id" value="<?= (int)$role['id'] ?>" />

    <?php if ($isEditable): ?>
    <div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
        <div class="lc-field">
            <label class="lc-label">Nome do papel</label>
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)$role['name'], ENT_QUOTES, 'UTF-8') ?>" style="max-width:400px;" />
        </div>
    </div>
    <?php else: ?>
        <input type="hidden" name="name" value="<?= htmlspecialchars((string)$role['name'], ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif; ?>

    <!-- Permissões agrupadas por módulo -->
    <?php foreach ($byModule as $mod => $perms): ?>
        <?php
        $modLabel = $moduleLabels[$mod] ?? ucfirst(str_replace('_', ' ', $mod));
        $allowCount = 0;
        foreach ($perms as $p) { if (in_array((string)$p['code'], $allow, true)) $allowCount++; }
        ?>
        <details class="rbac-module" <?= $allowCount > 0 ? 'open' : '' ?>>
            <summary class="rbac-module__head">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="rbac-module__name"><?= htmlspecialchars($modLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="rbac-module__count"><?= $allowCount ?>/<?= count($perms) ?> ativas</span>
                </div>
                <svg class="rbac-module__chev" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div style="margin-top:12px;">
                <?php foreach ($perms as $p): ?>
                    <?php
                    $code = (string)$p['code'];
                    $isAllow = in_array($code, $allow, true);
                    $isDeny = in_array($code, $deny, true);
                    $actRaw = (string)$p['action'];
                    $actLabel = $actionLabels[$actRaw] ?? ucfirst(str_replace('_', ' ', $actRaw));
                    $desc = trim((string)($p['description'] ?? ''));
                    ?>
                    <div class="rbac-perm">
                        <div class="rbac-perm__info">
                            <div class="rbac-perm__action"><?= htmlspecialchars($actLabel, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($desc !== ''): ?>
                                <div style="font-size:11px;color:rgba(31,41,55,.45);margin-top:1px;"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <div class="rbac-perm__code"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="rbac-perm__checks">
                            <label class="rbac-perm__check" style="color:#16a34a;">
                                <input type="checkbox" class="lc-rbac-allow" name="allow[]" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $isAllow ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> />
                                Permitir
                            </label>
                            <label class="rbac-perm__check" style="color:#b91c1c;">
                                <input type="checkbox" class="lc-rbac-deny" name="deny[]" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $isDeny ? 'checked' : '' ?> <?= !$isEditable ? 'disabled' : '' ?> />
                                Negar
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endforeach; ?>

    <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;">
        <?php if ($isEditable): ?>
            <button class="lc-btn lc-btn--primary" type="submit">Salvar permissões</button>
        <?php endif; ?>
        <a class="lc-btn lc-btn--secondary" href="/rbac">Voltar</a>
    </div>
</form>

<?php if ($isEditable): ?>
<details style="margin-top:16px;">
    <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;">Resetar para padrão</summary>
    <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
        <div style="font-size:12px;color:rgba(185,28,28,.70);margin-bottom:8px;">Isso vai restaurar todas as permissões para o padrão do sistema.</div>
        <form method="post" action="/rbac/reset" style="margin:0;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)$role['id'] ?>" />
            <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit" onclick="return confirm('Resetar permissões?');">Confirmar reset</button>
        </form>
    </div>
</details>

<script>
(function(){
    var btnAll=document.getElementById('lcSelectAllAllow');
    var btnClear=document.getElementById('lcClearAll');
    if(btnAll)btnAll.addEventListener('click',function(){
        document.querySelectorAll('.lc-rbac-allow').forEach(function(cb){cb.checked=true;});
        document.querySelectorAll('.lc-rbac-deny').forEach(function(cb){cb.checked=false;});
    });
    if(btnClear)btnClear.addEventListener('click',function(){
        document.querySelectorAll('.lc-rbac-allow,.lc-rbac-deny').forEach(function(cb){cb.checked=false;});
    });
})();
</script>
<?php endif; ?>

<?php endif; /* can rbac.manage */ ?>
<?php endif; /* role exists */ ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
