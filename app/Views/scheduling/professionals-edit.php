<?php
/** @var array<string,mixed> $row */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Editar profissional';
$error = is_string($error ?? null) ? (string)$error : '';
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$id = (int)($row['id'] ?? 0);
$name = (string)($row['name'] ?? '');
$specialty = (string)($row['specialty'] ?? '');
$councilNumber = (string)($row['council_number'] ?? '');
$allowOnline = (int)($row['allow_online_booking'] ?? 0) === 1;

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

<a href="/professionals" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para profissionais
</a>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= $e($error) ?></div>
<?php endif; ?>

<div style="font-weight:850;font-size:20px;color:#1f2937;margin-bottom:18px;"><?= $e($name) ?></div>

<?php if ($can('professionals.manage')): ?>
<form method="post" action="/professionals/edit" class="lc-form" style="max-width:680px;">
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
    <input type="hidden" name="id" value="<?= $id ?>" />

    <div style="padding:20px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,.06);margin-bottom:16px;">
        <div style="font-weight:750;font-size:14px;color:#1f2937;margin-bottom:12px;">Dados do profissional</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="lc-field">
                <label class="lc-label">Nome completo</label>
                <input class="lc-input" type="text" name="name" value="<?= $e($name) ?>" required />
            </div>
            <div class="lc-field">
                <label class="lc-label">Especialidade</label>
                <input class="lc-input" type="text" name="specialty" value="<?= $e($specialty) ?>" placeholder="Ex: Dermatologia, Estética" />
            </div>
        </div>
        <div class="lc-field" style="margin-top:12px;max-width:340px;">
            <label class="lc-label">Nº do conselho (CRM, CRO, etc.)</label>
            <input class="lc-input" type="text" name="council_number" value="<?= $e($councilNumber) ?>" placeholder="Ex: CRM 123456/SP" />
            <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Aparece no receituário e na impressão de receitas.</div>
        </div>
    </div>

    <input type="hidden" name="allow_online_booking" value="<?= $allowOnline ? '1' : '0' ?>" />

    <div style="display:flex;gap:10px;align-items:center;">
        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        <a class="lc-btn lc-btn--secondary" href="/professionals">Cancelar</a>
    </div>
</form>

<details style="margin-top:20px;max-width:680px;">
    <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;">Excluir profissional</summary>
    <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
        <div style="font-size:12px;color:rgba(185,28,28,.70);margin-bottom:8px;">O profissional será desativado e não aparecerá mais na agenda.</div>
        <form method="post" action="/professionals/delete" onsubmit="return confirm('Excluir este profissional?');">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
            <input type="hidden" name="id" value="<?= $id ?>" />
            <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Confirmar exclusão</button>
        </form>
    </div>
</details>
<?php else: ?>
<div style="padding:20px;border-radius:14px;border:1px solid #e5e7eb;background:#fff;max-width:680px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div><div style="font-size:12px;color:#9ca3af;">Nome</div><div style="font-weight:600;"><?= $e($name) ?></div></div>
        <div><div style="font-size:12px;color:#9ca3af;">Especialidade</div><div><?= $e($specialty ?: '—') ?></div></div>
        <div><div style="font-size:12px;color:#9ca3af;">Nº do conselho</div><div><?= $e($councilNumber ?: '—') ?></div></div>
    </div>
    <div style="margin-top:14px;"><a class="lc-btn lc-btn--secondary" href="/professionals">Voltar</a></div>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
