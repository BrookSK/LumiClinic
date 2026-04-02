<?php
$title = 'Feriados e Recesso';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$items = $items ?? [];
$editItem = $edit_item ?? null;

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

ob_start();
?>

<a href="/clinic" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para clínica
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Feriados e Recesso</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:600px;line-height:1.5;">
    Cadastre os dias em que a clínica não funciona (feriados, recesso, etc). Nesses dias, a agenda fica bloqueada automaticamente.
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($can('clinics.update')): ?>
<!-- Edição -->
<?php if (is_array($editItem)): ?>
<div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);margin-bottom:16px;">
    <div style="font-weight:750;font-size:14px;margin-bottom:12px;">Editar feriado</div>
    <form method="post" action="/clinic/closed-days/update">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($editItem['id'] ?? 0) ?>" />
        <div style="display:grid;grid-template-columns:160px 1fr 140px;gap:12px;align-items:end;">
            <div class="lc-field"><label class="lc-label">Data</label><input class="lc-input" type="date" name="closed_date" required value="<?= htmlspecialchars((string)($editItem['closed_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></div>
            <div class="lc-field"><label class="lc-label">Motivo</label><input class="lc-input" type="text" name="reason" value="<?= htmlspecialchars((string)($editItem['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Natal, Recesso..." /></div>
            <div class="lc-field">
                <label class="lc-label">Status</label>
                <?php $isOpenEdit = isset($editItem['is_open']) && (int)$editItem['is_open'] === 1; ?>
                <select class="lc-select" name="is_open">
                    <option value="0" <?= $isOpenEdit ? '' : 'selected' ?>>Fechado</option>
                    <option value="1" <?= $isOpenEdit ? 'selected' : '' ?>>Aberto</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:12px;">
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/clinic/closed-days">Cancelar</a>
        </div>
    </form>
</div>
<?php else: ?>
<!-- Adicionar -->
<div id="addClosedForm" style="display:none;margin-bottom:16px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <div style="font-weight:750;font-size:14px;margin-bottom:12px;">Adicionar feriado/recesso</div>
        <form method="post" action="/clinic/closed-days">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div style="display:grid;grid-template-columns:160px 1fr 140px auto;gap:12px;align-items:end;">
                <div class="lc-field"><label class="lc-label">Data</label><input class="lc-input" type="date" name="closed_date" required /></div>
                <div class="lc-field"><label class="lc-label">Motivo</label><input class="lc-input" type="text" name="reason" placeholder="Ex: Natal, Recesso..." /></div>
                <div class="lc-field">
                    <label class="lc-label">Status</label>
                    <select class="lc-select" name="is_open"><option value="0" selected>Fechado</option><option value="1">Aberto</option></select>
                </div>
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
            </div>
        </form>
    </div>
</div>

<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('addClosedForm');f.style.display=f.style.display==='none'?'block':'none';">+ Adicionar feriado</button>
    <form method="post" action="/clinic/closed-days/ai" style="margin:0;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="year" value="<?= (int)date('Y') ?>" />
        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit" onclick="return confirm('Gerar feriados automaticamente com IA para <?= (int)date('Y') ?>?');">🤖 Gerar feriados com IA</button>
    </form>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Lista -->
<?php if ($items === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">📅</div>
        <div style="font-size:14px;">Nenhum feriado cadastrado. Clique em "+ Adicionar" ou use a IA para gerar automaticamente.</div>
    </div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($items as $it): ?>
        <?php
        $open = isset($it['is_open']) && (int)$it['is_open'] === 1;
        $dt = (string)($it['closed_date'] ?? '');
        $dtFmt = $dt !== '' ? date('d/m/Y', strtotime($dt)) : '—';
        $reason = trim((string)($it['reason'] ?? ''));
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 2px 8px rgba(17,24,39,.04);">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);min-width:80px;"><?= htmlspecialchars($dtFmt, ENT_QUOTES, 'UTF-8') ?></span>
                <span style="font-size:13px;color:rgba(31,41,55,.60);"><?= htmlspecialchars($reason !== '' ? $reason : '—', ENT_QUOTES, 'UTF-8') ?></span>
                <span style="display:inline-flex;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $open ? 'rgba(22,163,74,.12)' : 'rgba(185,28,28,.10)' ?>;color:<?= $open ? '#16a34a' : '#b91c1c' ?>;border:1px solid <?= $open ? 'rgba(22,163,74,.22)' : 'rgba(185,28,28,.18)' ?>;"><?= $open ? 'Aberto' : 'Fechado' ?></span>
            </div>
            <?php if ($can('clinics.update')): ?>
            <div style="display:flex;gap:6px;">
                <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/clinic/closed-days?edit_id=<?= (int)$it['id'] ?>">Editar</a>
                <form method="post" action="/clinic/closed-days/delete" style="margin:0;" onsubmit="return confirm('Remover?');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Remover</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
