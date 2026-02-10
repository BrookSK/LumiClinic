<?php
$title = 'Admin do Sistema';
$items = $items ?? [];
$status = $status ?? null;

$allowed = [
    '' => 'Todos',
    '402' => '402 (Assinatura pendente)',
    '403' => '403 (Acesso negado)',
    '404' => '404 (Página não encontrada)',
    '500' => '500 (Erro interno)',
    '503' => '503 (Indisponível)',
];

$selected = $status !== null ? (string)$status : '';
if (!array_key_exists($selected, $allowed)) {
    $selected = '';
}

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap lc-gap-md" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Logs de erros</div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary" href="/sys/clinics">Clínicas</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Filtro</div>
    <form method="get" class="lc-form" action="/sys/error-logs">
        <label class="lc-label">Tipo de erro</label>
        <select class="lc-input" name="status">
            <?php foreach ($allowed as $k => $label): ?>
                <option value="<?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === $selected ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:10px;">
            <button class="lc-btn lc-btn--primary" type="submit">Aplicar</button>
            <a class="lc-btn lc-btn--secondary" href="/sys/error-logs">Limpar</a>
        </div>
    </form>
</div>

<div class="lc-card">
    <div class="lc-card__title">Ocorrências (últimas)</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Tipo</th>
                <th>Rota</th>
                <th>Clínica</th>
                <th>Usuário</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                $id = (int)($it['id'] ?? 0);
                $st = (int)($it['status_code'] ?? 0);
                $type = trim((string)($it['error_type'] ?? ''));
                $method = trim((string)($it['method'] ?? ''));
                $path = trim((string)($it['path'] ?? ''));
                $clinicId = (int)($it['clinic_id'] ?? 0);
                $userId = (int)($it['user_id'] ?? 0);
                $createdAt = (string)($it['created_at'] ?? '');
                ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= $st ?></td>
                    <td><?= htmlspecialchars($type !== '' ? $type : '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(trim($method . ' ' . $path), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $clinicId > 0 ? $clinicId : '-' ?></td>
                    <td><?= $userId > 0 ? $userId : '-' ?></td>
                    <td><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($id > 0): ?>
                            <a class="lc-btn lc-btn--secondary" href="/sys/error-logs/view?id=<?= $id ?>">Ver</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
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
