<?php
$title = 'Admin do Sistema';
$items = $items ?? [];
$status = $status ?? null;
$csrf = $_SESSION['_csrf'] ?? '';

$allowed = [
    '' => 'Todos',
    'pending' => 'Pendente',
    'processing' => 'Em processamento',
    'done' => 'Concluído',
    'dead' => 'Falhou',
];

$queueLabel = [
    '' => 'Padrão',
    'default' => 'Padrão',
    'mail' => 'E-mails',
    'emails' => 'E-mails',
    'billing' => 'Cobrança',
    'reports' => 'Relatórios',
];

$jobTypeLabel = [
    'test.noop' => 'Teste simples',
    'test.throw' => 'Teste com erro',
];

$selected = $status !== null ? (string)$status : '';
if (!array_key_exists($selected, $allowed)) {
    $selected = '';
}

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:14px;">
    <div class="lc-badge lc-badge--primary">Fila de tarefas</div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Testes</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <form method="post" action="/sys/queue-jobs/enqueue-test" style="display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="job_type" value="test.noop" />
            <?php if ($selected !== ''): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') ?>" />
            <?php endif; ?>
            <button class="lc-btn lc-btn--secondary" type="submit">Enfileirar teste (noop)</button>
        </form>

        <form method="post" action="/sys/queue-jobs/enqueue-test" style="display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="job_type" value="test.throw" />
            <?php if ($selected !== ''): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') ?>" />
            <?php endif; ?>
            <button class="lc-btn lc-btn--secondary" type="submit">Enfileirar teste (erro)</button>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Filtro</div>
    <form method="get" class="lc-form" action="/sys/queue-jobs">
        <label class="lc-label">Status</label>
        <select class="lc-input" name="status">
            <?php foreach ($allowed as $k => $label): ?>
                <option value="<?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === $selected ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:10px;">
            <button class="lc-btn lc-btn--primary" type="submit">Aplicar</button>
            <a class="lc-btn lc-btn--secondary" href="/sys/queue-jobs">Limpar</a>
        </div>
    </form>
</div>

<div class="lc-card">
    <div class="lc-card__title">Tarefas (últimas)</div>

    <div class="lc-table-wrap">
        <table class="lc-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Clínica</th>
                <th>Fila</th>
                <th>Tarefa</th>
                <th>Status</th>
                <th>Tentativas</th>
                <th>Executar em</th>
                <th>Em execução</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                $itStatus = (string)($it['status'] ?? '');
                $isDead = $itStatus === 'dead';
                ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td><?= htmlspecialchars((string)($it['clinic_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <?php
                    $queueCode = (string)($it['queue'] ?? '');
                    $jobCode = (string)($it['job_type'] ?? '');
                    $lockedBy = trim((string)($it['locked_by'] ?? ''));

                    $queueHuman = $queueLabel[$queueCode] ?? 'Padrão';
                    $jobHuman = $jobTypeLabel[$jobCode] ?? '';
                    if ($jobHuman === '') {
                        $jobHuman = 'Tarefa do sistema';
                        if (str_starts_with($jobCode, 'mail.')) {
                            $jobHuman = 'Envio de e-mail';
                        } elseif (str_starts_with($jobCode, 'billing.')) {
                            $jobHuman = 'Cobrança';
                        } elseif (str_starts_with($jobCode, 'reports.')) {
                            $jobHuman = 'Relatório';
                        }
                    }
                    ?>
                    <td><?= htmlspecialchars((string)$queueHuman, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$jobHuman, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($allowed[$itStatus] ?? $itStatus), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($it['attempts'] ?? 0) ?>/<?= (int)($it['max_attempts'] ?? 0) ?></td>
                    <td><?= htmlspecialchars((string)($it['run_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $lockedBy !== '' ? 'Sim' : 'Não' ?></td>
                    <td>
                        <?php if ($isDead): ?>
                            <form method="post" action="/sys/queue-jobs/retry" style="display:inline;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                <input type="hidden" name="job_id" value="<?= (int)$it['id'] ?>" />
                                <?php if ($selected !== ''): ?>
                                    <input type="hidden" name="status" value="<?= htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') ?>" />
                                <?php endif; ?>
                                <button class="lc-btn lc-btn--secondary" type="submit">Reprocessar</button>
                            </form>
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
