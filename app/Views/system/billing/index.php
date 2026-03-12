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
                <th>Plano</th>
                <th>Status</th>
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
                        <?php $curPlanId = (int)($it['plan_id'] ?? 0); ?>
                        <?= htmlspecialchars((string)($planLabel[$curPlanId] ?? (($it['plan_name'] ?? '') !== '' ? (string)$it['plan_name'] : (string)($it['plan_code'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <?php $cur = (string)($it['subscription_status'] ?? ''); ?>
                        <?= htmlspecialchars((string)($statusLabel[$cur] ?? ($cur !== '' ? $cur : '-')), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <a class="lc-btn lc-btn--secondary" href="/sys/billing/view?clinic_id=<?= (int)$it['id'] ?>">Ver detalhes</a>
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
