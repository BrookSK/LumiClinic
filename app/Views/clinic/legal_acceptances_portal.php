<?php
$title = 'Aceites (Portal do Paciente)';
$rows = $rows ?? [];
$limit = (int)($limit ?? 300);
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Aceites de termos obrigatórios (Portal)</div>

    <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:12px;">
        <a class="lc-btn lc-btn--secondary" href="/clinic/legal-documents">Configurar textos</a>
        <a class="lc-btn lc-btn--secondary" href="/clinic">Voltar</a>
    </div>

    <div class="lc-tablewrap" style="margin-top:12px;">
        <table class="lc-table">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>E-mail (Portal)</th>
                    <th>Obrigatórios</th>
                    <th>Aceitos</th>
                    <th>Pendentes</th>
                    <th>Último aceite</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7">Nenhum registro.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                        $total = (int)($r['required_total'] ?? 0);
                        $acc = (int)($r['required_accepted'] ?? 0);
                        $pending = max(0, $total - $acc);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($r['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($r['portal_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $total ?></td>
                        <td><?= $acc ?></td>
                        <td><?= $pending ?></td>
                        <td><?= htmlspecialchars((string)($r['last_accepted_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($r['patient_id'] ?? 0) ?>">Ver paciente</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
