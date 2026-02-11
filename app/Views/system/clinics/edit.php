<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';
$clinic = $clinic ?? null;
$error = $error ?? null;

$clinicId = is_array($clinic) ? (int)($clinic['id'] ?? 0) : 0;
$name = is_array($clinic) ? (string)($clinic['name'] ?? '') : '';
$tenantKey = is_array($clinic) ? (string)($clinic['tenant_key'] ?? '') : '';
$primaryDomain = is_array($clinic) ? (string)($clinic['primary_domain'] ?? '') : '';
$status = is_array($clinic) ? (string)($clinic['status'] ?? '') : '';

$statusLabel = match ($status) {
    'active' => 'Ativo',
    'inactive' => 'Inativo',
    default => ($status !== '' ? $status : '-'),
};

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Clínica #<?= (int)$clinicId ?></div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Voltar</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Detalhes</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-grid--3 lc-gap-grid">
            <div>
                <div class="lc-label">Status</div>
                <div><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">Identificação (tenant)</div>
                <div><?= htmlspecialchars($tenantKey !== '' ? $tenantKey : '-', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div>
                <div class="lc-label">Domínio</div>
                <div><?= htmlspecialchars($primaryDomain !== '' ? $primaryDomain : '-', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Editar clínica</div>
    <div class="lc-card__body">
        <?php if ($error): ?>
            <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
                <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/sys/clinics/update" class="lc-form lc-grid lc-grid--2 lc-gap-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)$clinicId ?>" />

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Nome</label>
                <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Identificação (tenant)</label>
                <input class="lc-input" type="text" name="tenant_key" value="<?= htmlspecialchars($tenantKey, ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: clinica01" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Domínio principal</label>
                <input class="lc-input" type="text" name="primary_domain" value="<?= htmlspecialchars($primaryDomain, ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: clinica01.com.br" />
            </div>

            <div class="lc-flex lc-flex--end lc-gap-sm" style="grid-column: 1 / -1;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            </div>
        </form>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px; align-items:center;">
            <form method="post" action="/sys/clinics/set-status">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)$clinicId ?>" />
                <input type="hidden" name="status" value="<?= ($status === 'active') ? 'inactive' : 'active' ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit"><?= ($status === 'active') ? 'Desativar' : 'Ativar' ?></button>
            </form>

            <form method="post" action="/sys/clinics/delete" onsubmit="return confirm('Tem certeza que deseja excluir esta clínica? Essa ação oculta a clínica do sistema.');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= (int)$clinicId ?>" />
                <button class="lc-btn lc-btn--danger" type="submit">Excluir</button>
            </form>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
