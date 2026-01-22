<?php
$title = 'Consentimento';
$patient = $patient ?? null;
$terms = $terms ?? [];
$acceptances = $acceptances ?? [];
$signatures = $signatures ?? [];
ob_start();
?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Consentimento</div>
    <div>
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao paciente</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title"><?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="lc-card__body">
        CPF: <?= isset($patient['cpf_last4']) && $patient['cpf_last4'] ? '***.' . htmlspecialchars((string)$patient['cpf_last4'], ENT_QUOTES, 'UTF-8') : '' ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Aceitar termo</div>

    <form method="get" action="/consent/accept" class="lc-form">
        <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

        <label class="lc-label">Termo</label>
        <select class="lc-select" name="term_id" required>
            <option value="">Selecione</option>
            <?php foreach ($terms as $t): ?>
                <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars((string)$t['procedure_type'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string)$t['title'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit">Continuar</button>
        </div>
    </form>
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
            </tr>
            </thead>
            <tbody>
            <?php foreach ($acceptances as $a): ?>
                <tr>
                    <td><?= (int)$a['id'] ?></td>
                    <td><?= (int)$a['term_id'] ?></td>
                    <td><?= htmlspecialchars((string)$a['procedure_type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$a['accepted_at'], ENT_QUOTES, 'UTF-8') ?></td>
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
