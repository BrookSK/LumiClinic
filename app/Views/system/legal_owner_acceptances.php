<?php
$title = 'Admin do Sistema';
$rows = $rows ?? [];
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Aceites (Owner)</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/sys/legal-owner-documents">Termos do owner</a>
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Relatório consolidado</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>Clínica</th>
                <th>Owner</th>
                <th>Obrigatórios</th>
                <th>Aceitos</th>
                <th>Pendentes</th>
                <th>Último aceite</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">Nenhum registro.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                        $total = (int)($r['required_total'] ?? 0);
                        $accepted = (int)($r['required_accepted'] ?? 0);
                        $pending = max(0, $total - $accepted);
                    ?>
                    <tr>
                        <td>#<?= (int)($r['clinic_id'] ?? 0) ?> - <?= htmlspecialchars((string)($r['clinic_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ((int)($r['owner_user_id'] ?? 0) > 0): ?>
                                #<?= (int)($r['owner_user_id'] ?? 0) ?>
                                <?= htmlspecialchars((string)($r['owner_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <div style="opacity:.8; margin-top:4px;">
                                    <?= htmlspecialchars((string)($r['owner_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= $total ?></td>
                        <td><?= $accepted ?></td>
                        <td><?= $pending ?></td>
                        <td><?= htmlspecialchars((string)($r['last_accepted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
