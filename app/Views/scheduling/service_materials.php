<?php
/** @var array<string,mixed> $service */
/** @var list<array<string,mixed>> $services */
/** @var list<array<string,mixed>> $materials */
/** @var list<array<string,mixed>> $defaults */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Materiais do Serviço';

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

$hasService = isset($service) && is_array($service) && $service !== [];

ob_start();
?>

<a href="/services" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para serviços
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Vínculo com estoque</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:600px;line-height:1.5;">
    Defina quais materiais são consumidos automaticamente quando um serviço é realizado. Ao finalizar o atendimento, o sistema dá baixa no estoque.
</div>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!$hasService): ?>
<!-- Seletor de serviço -->
<div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);">
    <form method="get" action="/services/materials" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <div class="lc-field" style="min-width:280px;flex:1;">
            <label class="lc-label">Selecione um serviço</label>
            <select class="lc-select" name="service_id" required>
                <option value="">Escolha...</option>
                <?php foreach (($services ?? []) as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="padding-bottom:1px;">
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Abrir</button>
        </div>
    </form>
</div>

<?php else: ?>
<!-- Serviço selecionado -->
<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
    <div>
        <div style="font-weight:750;font-size:15px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)$service['name'], ENT_QUOTES, 'UTF-8') ?></div>
        <div style="font-size:12px;color:rgba(31,41,55,.50);">Materiais consumidos por sessão</div>
    </div>
    <form method="get" action="/services/materials" style="display:flex;gap:8px;align-items:center;margin:0;">
        <select class="lc-select" name="service_id" style="min-width:200px;padding:8px 10px;font-size:12px;" onchange="this.form.submit()">
            <?php foreach (($services ?? []) as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (int)$s['id'] === (int)$service['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Adicionar material -->
<?php if ($can('services.manage')): ?>
<div id="addMatForm" style="display:none;margin-bottom:16px;">
    <div style="padding:16px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <form method="post" action="/services/materials/create" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>" />
            <div class="lc-field" style="min-width:220px;flex:1;">
                <label class="lc-label">Material</label>
                <select class="lc-select" name="material_id">
                    <?php foreach ($materials as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$m['unit'], ENT_QUOTES, 'UTF-8') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lc-field" style="min-width:120px;">
                <label class="lc-label">Qtd por sessão</label>
                <input class="lc-input" type="text" name="quantity_per_session" required placeholder="Ex: 1.000" />
            </div>
            <div style="padding-bottom:1px;">
                <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
            </div>
        </form>
    </div>
</div>
<div style="margin-bottom:16px;">
    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('addMatForm');f.style.display=f.style.display==='none'?'block':'none';">+ Adicionar material</button>
</div>
<?php endif; ?>

<!-- Lista -->
<?php if ($defaults === []): ?>
    <div style="text-align:center;padding:30px 20px;color:rgba(31,41,55,.45);font-size:13px;">Nenhum material vinculado a este serviço.</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($defaults as $d): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);"><?= htmlspecialchars((string)$d['material_name'], ENT_QUOTES, 'UTF-8') ?></span>
                <span style="font-size:12px;color:rgba(31,41,55,.50);"><?= number_format((float)$d['quantity_per_session'], 3, ',', '.') ?> <?= htmlspecialchars((string)$d['material_unit'], ENT_QUOTES, 'UTF-8') ?> por sessão</span>
            </div>
            <?php if ($can('services.manage')): ?>
                <form method="post" action="/services/materials/delete" style="margin:0;" onsubmit="return confirm('Remover?');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>" />
                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>" />
                    <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Remover</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
