<?php
$title = 'Admin do Sistema';
$items = $items ?? [];
$plans = $plans ?? [];
$error = isset($error) ? (string)$error : '';
$csrf = $_SESSION['_csrf'] ?? '';

$statusLabel = [
    'trial' => 'Período de teste',
    'active' => 'Ativa',
    'past_due' => 'Em atraso',
    'canceled' => 'Cancelada',
    'suspended' => 'Suspensa',
];

$gatewayLabel = [
    'asaas' => 'Asaas',
    'mercadopago' => 'Mercado Pago',
    '' => 'Não definido',
];

$planLabel = [];
foreach ($plans as $p) {
    $code = (string)($p['code'] ?? '');
    $name = (string)($p['name'] ?? '');
    $planLabel[(int)($p['id'] ?? 0)] = trim($name !== '' ? $name : $code);
}
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Assinaturas</div>
    <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Voltar</a>
</div>

<?php if ($error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card">
    <div class="lc-card__title">Assinaturas por clínica</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Clínica</th>
                <th>Identificação</th>
                <th>Plano</th>
                <th>Status</th>
                <th>Forma de cobrança</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td>
                        <div style="font-weight:650;">
                            <a href="/sys/billing/view?clinic_id=<?= (int)$it['id'] ?>">
                                <?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                        <?php $domain = (string)($it['primary_domain'] ?? ''); ?>
                        <?php if ($domain !== ''): ?>
                            <div class="lc-muted" style="font-size:12px;">Domínio: <?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $tenantKey = (string)($it['tenant_key'] ?? '');
                        $clinicStatus = (string)($it['clinic_status'] ?? '');
                        ?>
                        <div><?= htmlspecialchars($tenantKey !== '' ? $tenantKey : '-', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($clinicStatus !== ''): ?>
                            <div class="lc-muted" style="font-size:12px;">Situação da clínica: <?= htmlspecialchars($clinicStatus, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="lc-flex lc-flex--wrap" style="gap:8px; align-items:center;">
                            <div>
                                <?php $curPlanId = (int)($it['plan_id'] ?? 0); ?>
                                <?= htmlspecialchars((string)($planLabel[$curPlanId] ?? (($it['plan_name'] ?? '') !== '' ? (string)$it['plan_name'] : (string)($it['plan_code'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <form method="post" action="/sys/billing/set-plan" class="lc-flex" style="gap:8px; align-items:center;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="clinic_id" value="<?= (int)$it['id'] ?>" />
                                <select class="lc-input" name="plan_id">
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>" <?= ((int)($it['plan_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string)($planLabel[(int)$p['id']] ?? (string)$p['code']), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="lc-btn lc-btn--secondary" type="submit">Alterar</button>
                            </form>
                        </div>
                    </td>
                    <td>
                        <form method="post" action="/sys/billing/set-status" class="lc-flex" style="gap:8px; align-items:center;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="clinic_id" value="<?= (int)$it['id'] ?>" />
                            <select class="lc-input" name="status">
                                <?php $cur = (string)($it['subscription_status'] ?? ''); ?>
                                <?php foreach (['trial','active','past_due','canceled','suspended'] as $st): ?>
                                    <option value="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>" <?= ($cur === $st) ? 'selected' : '' ?>><?= htmlspecialchars((string)($statusLabel[$st] ?? $st), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="lc-btn lc-btn--secondary" type="submit">Salvar</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="/sys/billing/set-gateway" class="lc-flex" style="gap:8px; align-items:center;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="clinic_id" value="<?= (int)$it['id'] ?>" />
                            <?php $gp = (string)($it['gateway_provider'] ?? ''); ?>
                            <select class="lc-input" name="gateway_provider">
                                <option value="asaas" <?= ($gp === 'asaas') ? 'selected' : '' ?>>Asaas</option>
                                <option value="mercadopago" <?= ($gp === 'mercadopago') ? 'selected' : '' ?>>Mercado Pago</option>
                            </select>
                            <button class="lc-btn lc-btn--secondary" type="submit">Salvar</button>
                        </form>
                    </td>
                    <td>
                        <a class="lc-btn lc-btn--secondary" href="/sys/billing/view?clinic_id=<?= (int)$it['id'] ?>">Ver detalhes</a>
                        <form method="post" action="/sys/billing/ensure-gateway" style="display:inline;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="clinic_id" value="<?= (int)$it['id'] ?>" />
                            <button class="lc-btn lc-btn--primary" type="submit">Criar/atualizar cobrança</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
