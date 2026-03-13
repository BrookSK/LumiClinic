<?php
$title = 'Consentimento (Assinaturas) - Legado';
$patient = $patient ?? null;
$terms = $terms ?? [];
$acceptances = $acceptances ?? [];
$signatures = $signatures ?? [];

$termTitleMap = [];
if (is_array($terms)) {
    foreach ($terms as $t) {
        $tid = isset($t['id']) ? (int)$t['id'] : 0;
        if ($tid > 0) {
            $termTitleMap[$tid] = (string)($t['title'] ?? '');
        }
    }
}
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Consentimento (Legado)</div>
    <div>
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao paciente</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">
        <?php
            $cpfLast4 = '';
            if (isset($patient['cpf_last4']) && (string)$patient['cpf_last4'] !== '') {
                $cpfLast4 = (string)$patient['cpf_last4'];
            } elseif (isset($patient['cpf']) && (string)$patient['cpf'] !== '') {
                $digits = preg_replace('/\D+/', '', (string)$patient['cpf']);
                $digits = $digits === null ? '' : $digits;
                if (strlen($digits) >= 4) {
                    $cpfLast4 = substr($digits, -4);
                }
            }
        ?>
        CPF: <?= $cpfLast4 !== '' ? ('***.' . htmlspecialchars($cpfLast4, ENT_QUOTES, 'UTF-8')) : '' ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Aceites</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Termo</th>
                <th>Procedimento</th>
                <th>Aceito em</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($acceptances as $a): ?>
                <tr>
                    <td><?= (int)$a['id'] ?></td>
                    <?php $tid = (int)($a['term_id'] ?? 0); ?>
                    <?php $snapTitle = trim((string)($a['term_title_snapshot'] ?? '')); ?>
                    <td><?= htmlspecialchars($snapTitle !== '' ? $snapTitle : (($termTitleMap[$tid] ?? '') !== '' ? (string)$termTitleMap[$tid] : ('Termo #' . $tid)), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$a['procedure_type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$a['accepted_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <a class="lc-btn lc-btn--secondary" href="/consent/export?id=<?= (int)$a['id'] ?>" target="_blank">Exportar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Assinaturas</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Aceite</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($signatures as $s): ?>
                <tr>
                    <td><?= (int)$s['id'] ?></td>
                    <td><?= htmlspecialchars((string)($s['term_acceptance_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$s['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <a class="lc-btn lc-btn--secondary" href="/signatures/file?id=<?= (int)$s['id'] ?>" target="_blank">Abrir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
