<?php
$title = 'Admin - Editar Clínica';
$csrf = $_SESSION['_csrf'] ?? '';
$clinic = $clinic ?? null;
$error = $error ?? null;

$clinicId = is_array($clinic) ? (int)($clinic['id'] ?? 0) : 0;
$name = is_array($clinic) ? (string)($clinic['name'] ?? '') : '';
$tenantKey = is_array($clinic) ? (string)($clinic['tenant_key'] ?? '') : '';
$primaryDomain = is_array($clinic) ? (string)($clinic['primary_domain'] ?? '') : '';
$cnpj = is_array($clinic) ? (string)($clinic['cnpj'] ?? '') : '';
$status = is_array($clinic) ? (string)($clinic['status'] ?? '') : '';
$createdAt = is_array($clinic) ? (string)($clinic['created_at'] ?? '') : '';

$stLbl = $status === 'active' ? 'Ativo' : ($status === 'inactive' ? 'Inativo' : $status);
$stClr = $status === 'active' ? '#16a34a' : '#b91c1c';
$createdFmt = $createdAt !== '' ? date('d/m/Y H:i', strtotime($createdAt)) : '—';

ob_start();
?>

<a href="/sys/clinics" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para clínicas
</a>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Header -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
        <span style="display:inline-flex;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700;background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= htmlspecialchars($stLbl, ENT_QUOTES, 'UTF-8') ?></span>
        <span style="font-size:12px;color:rgba(31,41,55,.35);">ID #<?= $clinicId ?> · Criada em <?= htmlspecialchars($createdFmt, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <form method="post" action="/sys/clinics/set-status" style="margin:0;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= $clinicId ?>" />
        <input type="hidden" name="status" value="<?= $status === 'active' ? 'inactive' : 'active' ?>" />
        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit"><?= $status === 'active' ? 'Desativar' : 'Ativar' ?></button>
    </form>
</div>

<!-- Editar -->
<div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;max-width:560px;">
    <div style="font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:12px;">Dados da clínica</div>
    <form method="post" action="/sys/clinics/update">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= $clinicId ?>" />
        <input type="hidden" name="tenant_key" value="<?= htmlspecialchars($tenantKey, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="primary_domain" value="<?= htmlspecialchars($primaryDomain, ENT_QUOTES, 'UTF-8') ?>" />

        <div class="lc-field">
            <label class="lc-label">Nome</label>
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required />
        </div>

        <div class="lc-field" style="margin-top:12px;">
            <label class="lc-label">CNPJ / CPF</label>
            <input class="lc-input" type="text" name="cnpj" value="<?= htmlspecialchars($cnpj, ENT_QUOTES, 'UTF-8') ?>" placeholder="00.000.000/0000-00" />
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Necessário para integração com gateways de pagamento (Asaas).</div>
        </div>

        <div style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        </div>
    </form>
</div>

<!-- Info técnica -->
<?php if ($tenantKey !== '' || $primaryDomain !== ''): ?>
<details style="margin-bottom:16px;">
    <summary style="font-size:12px;color:rgba(31,41,55,.40);cursor:pointer;list-style:none;">Informações técnicas</summary>
    <div style="margin-top:8px;padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.06);background:rgba(0,0,0,.01);font-size:13px;color:rgba(31,41,55,.55);">
        <?php if ($tenantKey !== ''): ?><div>Tenant: <code><?= htmlspecialchars($tenantKey, ENT_QUOTES, 'UTF-8') ?></code></div><?php endif; ?>
        <?php if ($primaryDomain !== ''): ?><div>Domínio: <code><?= htmlspecialchars($primaryDomain, ENT_QUOTES, 'UTF-8') ?></code></div><?php endif; ?>
    </div>
</details>
<?php endif; ?>

<!-- Excluir -->
<details>
    <summary style="font-size:12px;color:rgba(185,28,28,.60);cursor:pointer;list-style:none;">Excluir clínica</summary>
    <div style="margin-top:8px;padding:12px;border-radius:12px;border:1px solid rgba(185,28,28,.18);background:rgba(185,28,28,.04);">
        <div style="font-size:12px;color:rgba(185,28,28,.70);margin-bottom:8px;">Essa ação oculta a clínica do sistema. Os dados não são apagados permanentemente.</div>
        <form method="post" action="/sys/clinics/delete" style="margin:0;" onsubmit="return confirm('Tem certeza que deseja excluir esta clínica?');">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= $clinicId ?>" />
            <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Confirmar exclusão</button>
        </form>
    </div>
</details>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
