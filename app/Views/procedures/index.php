<?php
/** @var list<array<string,mixed>> $items */
/** @var array<string,int> $avg_duration_by_procedure */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Procedimentos';

$error = is_string($error ?? null) ? (string)$error : '';
$success = is_string($success ?? null) ? (string)$success : '';

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

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Procedimentos</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Procedimentos são tipos de atendimento que podem ser vinculados a serviços para rastrear custos e duração.</div>
    </div>
    <?php if ($can('procedures.manage')): ?>
        <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('newProcForm');f.style.display=f.style.display==='none'?'block':'none';">+ Novo procedimento</button>
    <?php endif; ?>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Novo procedimento -->
<?php if ($can('procedures.manage')): ?>
<div id="newProcForm" style="display:none;margin-bottom:16px;">
    <div style="padding:16px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <form method="post" action="/procedures/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div class="lc-field" style="min-width:250px;flex:1;">
                <label class="lc-label">Nome do procedimento</label>
                <input class="lc-input" type="text" name="name" required placeholder="Ex: Botox, Preenchimento, Limpeza..." />
            </div>
            <div style="padding-bottom:1px;display:flex;gap:8px;">
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Criar</button>
                <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="document.getElementById('newProcForm').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Lista -->
<?php if ($items === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">🔧</div>
        <div style="font-size:14px;">Nenhum procedimento cadastrado.</div>
    </div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($items as $it): ?>
        <?php
        $id = (int)$it['id'];
        $avg = $avg_duration_by_procedure[(string)$id] ?? null;
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 2px 8px rgba(17,24,39,.04);">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-weight:700;font-size:14px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($avg !== null): ?>
                    <span style="font-size:12px;color:rgba(31,41,55,.45);">Duração média: <?= (int)$avg ?> min</span>
                <?php endif; ?>
            </div>
            <?php if ($can('procedures.manage')): ?>
                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/procedures/edit?id=<?= $id ?>">Editar</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
