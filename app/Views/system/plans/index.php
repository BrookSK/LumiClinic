<?php
$title = 'Admin - Planos';
$items = $items ?? [];
$error = isset($error) ? (string)$error : '';
$ok = isset($ok) ? (string)$ok : '';
$csrf = $_SESSION['_csrf'] ?? '';

$periodLabel = ['monthly'=>'Mensal','semiannual'=>'Semestral','annual'=>'Anual'];

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Planos</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Gerencie os planos de assinatura disponíveis para as clínicas.</div>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/sys/billing">Assinaturas</a>
        <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('newPlanForm');f.style.display=f.style.display==='none'?'block':'none';">+ Novo plano</button>
    </div>
</div>

<?php if ($ok !== ''): ?><div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<!-- Criar plano -->
<div id="newPlanForm" style="display:none;margin-bottom:16px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <div style="font-weight:750;font-size:14px;margin-bottom:12px;">Novo plano</div>
        <form method="post" action="/sys/plans/create">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="currency" value="BRL" />
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px;align-items:end;">
                <div class="lc-field"><label class="lc-label">Nome</label><input class="lc-input" type="text" name="name" required placeholder="Ex: Básico, Profissional..." /></div>
                <div class="lc-field"><label class="lc-label">Preço (R$)</label><input class="lc-input" type="number" name="price_cents" value="0" min="0" step="1" /><div style="font-size:10px;color:rgba(31,41,55,.35);margin-top:2px;">Em centavos (ex: 9990 = R$ 99,90)</div></div>
                <div class="lc-field"><label class="lc-label">Cobrança</label>
                    <select class="lc-select" name="billing_period"><option value="monthly">Mensal</option><option value="semiannual">Semestral</option><option value="annual">Anual</option></select></div>
            </div>
            <details style="margin-top:10px;">
                <summary style="font-size:12px;color:rgba(31,41,55,.45);cursor:pointer;list-style:none;">Mais opções</summary>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;margin-top:10px;">
                    <div class="lc-field"><label class="lc-label">Dias de teste</label><input class="lc-input" type="number" name="trial_days" value="0" min="0" /></div>
                    <div class="lc-field"><label class="lc-label">Limite usuários</label><input class="lc-input" type="number" name="limit_users" value="0" min="0" /><div style="font-size:10px;color:rgba(31,41,55,.35);">0 = ilimitado</div></div>
                    <div class="lc-field"><label class="lc-label">Limite pacientes</label><input class="lc-input" type="number" name="limit_patients" value="0" min="0" /></div>
                    <div class="lc-field"><label class="lc-label">Armazenamento (MB)</label><input class="lc-input" type="number" name="limit_storage_mb" value="0" min="0" /></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:8px;">
                    <div class="lc-field"><label class="lc-label">Portal do paciente</label><select class="lc-select" name="portal_enabled"><option value="1">Ativo</option><option value="0">Desativado</option></select></div>
                    <div class="lc-field"><label class="lc-label">Status</label><select class="lc-select" name="status"><option value="active">Ativo</option><option value="inactive">Inativo</option></select></div>
                </div>
            </details>
            <div style="margin-top:12px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Criar plano</button></div>
        </form>
    </div>
</div>

<!-- Lista -->
<?php if ($items === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);"><div style="font-size:32px;margin-bottom:8px;">📋</div><div>Nenhum plano cadastrado.</div></div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
    <?php foreach ($items as $it): ?>
        <?php
        $id = (int)($it['id'] ?? 0);
        $st = (string)($it['status'] ?? '');
        $stOk = $st === 'active';
        $price = (int)($it['price_cents'] ?? 0);
        $iu = (string)($it['interval_unit'] ?? 'month');
        $ic = (int)($it['interval_count'] ?? 1);
        $bp = 'monthly';
        if ($iu === 'year' && $ic === 1) $bp = 'annual';
        elseif ($iu === 'month' && $ic === 6) $bp = 'semiannual';
        $bpLbl = $periodLabel[$bp] ?? $bp;
        $trial = (int)($it['trial_days'] ?? 0);
        $limits = json_decode((string)($it['limits_json'] ?? '{}'), true) ?: [];
        ?>
        <div style="padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <span style="font-weight:800;font-size:16px;color:rgba(31,41,55,.96);"><?= htmlspecialchars((string)($it['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <span style="display:inline-flex;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;background:<?= $stOk ? 'rgba(22,163,74,.12)' : 'rgba(107,114,128,.10)' ?>;color:<?= $stOk ? '#16a34a' : '#6b7280' ?>;border:1px solid <?= $stOk ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;"><?= $stOk ? 'Ativo' : 'Inativo' ?></span>
            </div>
            <div style="font-size:22px;font-weight:900;color:rgba(129,89,1,1);">R$ <?= number_format($price / 100, 2, ',', '.') ?><span style="font-size:13px;font-weight:600;color:rgba(31,41,55,.45);">/<?= htmlspecialchars($bpLbl, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div style="font-size:12px;color:rgba(31,41,55,.45);">
                <?= $trial > 0 ? $trial . ' dias de teste' : 'Sem teste' ?>
                · Código: <code style="font-size:11px;"><?= htmlspecialchars((string)($it['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
            </div>

            <details style="margin-top:4px;">
                <summary style="font-size:12px;color:rgba(129,89,1,1);font-weight:600;cursor:pointer;list-style:none;">Editar</summary>
                <div style="margin-top:10px;">
                    <form method="post" action="/sys/plans/update">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="id" value="<?= $id ?>" />
                        <input type="hidden" name="currency" value="BRL" />
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div class="lc-field"><label class="lc-label">Nome</label><input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($it['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required /></div>
                            <div class="lc-field"><label class="lc-label">Preço (centavos)</label><input class="lc-input" type="number" name="price_cents" value="<?= $price ?>" min="0" /></div>
                            <div class="lc-field"><label class="lc-label">Cobrança</label><select class="lc-select" name="billing_period"><option value="monthly" <?= $bp === 'monthly' ? 'selected' : '' ?>>Mensal</option><option value="semiannual" <?= $bp === 'semiannual' ? 'selected' : '' ?>>Semestral</option><option value="annual" <?= $bp === 'annual' ? 'selected' : '' ?>>Anual</option></select></div>
                            <div class="lc-field"><label class="lc-label">Teste (dias)</label><input class="lc-input" type="number" name="trial_days" value="<?= $trial ?>" min="0" /></div>
                            <div class="lc-field"><label class="lc-label">Limite usuários</label><input class="lc-input" type="number" name="limit_users" value="<?= (int)($limits['users'] ?? 0) ?>" min="0" /></div>
                            <div class="lc-field"><label class="lc-label">Limite pacientes</label><input class="lc-input" type="number" name="limit_patients" value="<?= (int)($limits['patients'] ?? 0) ?>" min="0" /></div>
                            <div class="lc-field"><label class="lc-label">Armazenamento (MB)</label><input class="lc-input" type="number" name="limit_storage_mb" value="<?= (int)($limits['storage_mb'] ?? 0) ?>" min="0" /></div>
                            <div class="lc-field"><label class="lc-label">Portal</label><select class="lc-select" name="portal_enabled"><option value="1" <?= (bool)($limits['portal'] ?? true) ? 'selected' : '' ?>>Ativo</option><option value="0" <?= !(bool)($limits['portal'] ?? true) ? 'selected' : '' ?>>Desativado</option></select></div>
                        </div>
                        <div style="display:flex;gap:8px;margin-top:10px;">
                            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
                        </div>
                    </form>
                    <form method="post" action="/sys/plans/set-status" style="margin-top:8px;display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="id" value="<?= $id ?>" />
                        <input type="hidden" name="status" value="<?= $stOk ? 'inactive' : 'active' ?>" />
                        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit"><?= $stOk ? 'Desativar' : 'Ativar' ?></button>
                    </form>
                </div>
            </details>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
