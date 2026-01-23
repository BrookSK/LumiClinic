<?php
$title = 'Admin do Sistema';
$items = $items ?? [];
$plans = $plans ?? [];
$csrf = $_SESSION['_csrf'] ?? '';
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
    <div class="lc-badge lc-badge--gold">Billing (SaaS)</div>
    <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Voltar</a>
</div>

<div class="lc-card">
    <div class="lc-card__title">Assinaturas por clínica</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Clínica</th>
                <th>Tenant</th>
                <th>Plano</th>
                <th>Status</th>
                <th>Gateway</th>
                <th>IDs</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td><?= htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($it['tenant_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <div>
                                <?= htmlspecialchars((string)($it['plan_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars((string)($it['plan_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <form method="post" action="/sys/billing/set-plan" style="display:flex; gap:8px; align-items:center;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="clinic_id" value="<?= (int)$it['id'] ?>" />
                                <select class="lc-input" name="plan_id">
                                    <?php foreach ($plans as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>" <?= ((int)($it['plan_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string)$p['code'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="lc-btn lc-btn--secondary" type="submit">Trocar</button>
                            </form>
                        </div>
                    </td>
                    <td>
                        <form method="post" action="/sys/billing/set-status" style="display:flex; gap:8px; align-items:center;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="clinic_id" value="<?= (int)$it['id'] ?>" />
                            <select class="lc-input" name="status">
                                <?php $cur = (string)($it['subscription_status'] ?? ''); ?>
                                <?php foreach (['trial','active','past_due','canceled','suspended'] as $st): ?>
                                    <option value="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>" <?= ($cur === $st) ? 'selected' : '' ?>><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="lc-btn lc-btn--secondary" type="submit">Salvar</button>
                        </form>
                    </td>
                    <td>
                        <form method="post" action="/sys/billing/set-gateway" style="display:flex; gap:8px; align-items:center;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="clinic_id" value="<?= (int)$it['id'] ?>" />
                            <?php $gp = (string)($it['gateway_provider'] ?? ''); ?>
                            <select class="lc-input" name="gateway_provider">
                                <option value="asaas" <?= ($gp === 'asaas') ? 'selected' : '' ?>>asaas</option>
                                <option value="mercadopago" <?= ($gp === 'mercadopago') ? 'selected' : '' ?>>mercadopago</option>
                            </select>
                            <button class="lc-btn lc-btn--secondary" type="submit">Salvar</button>
                        </form>
                    </td>
                    <td style="max-width:360px;">
                        <div style="font-size:12px; line-height:1.4;">
                            <div>Asaas cust: <?= htmlspecialchars((string)($it['asaas_customer_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div>Asaas sub: <?= htmlspecialchars((string)($it['asaas_subscription_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div>MP preapproval: <?= htmlspecialchars((string)($it['mp_preapproval_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </td>
                    <td>
                        <form method="post" action="/sys/billing/ensure-gateway" style="display:inline;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="clinic_id" value="<?= (int)$it['id'] ?>" />
                            <button class="lc-btn lc-btn--primary" type="submit">Ensure gateway</button>
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
