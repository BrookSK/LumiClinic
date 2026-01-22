<?php
/** @var array<string,mixed> $appointment */
/** @var list<array<string,mixed>> $logs */
$title = 'Logs do agendamento';
$appointmentId = (int)($appointment['id'] ?? 0);
ob_start();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:10px; flex-wrap:wrap;">
    <div class="lc-badge lc-badge--gold">Logs</div>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="lc-btn lc-btn--secondary" href="/schedule">Voltar</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__title">Agendamento #<?= (int)$appointmentId ?></div>
    <div class="lc-card__body">
        Início: <?= htmlspecialchars((string)($appointment['start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br />
        Fim: <?= htmlspecialchars((string)($appointment['end_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br />
        Status: <?= htmlspecialchars((string)($appointment['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Histórico (imutável)</div>

    <?php if (($logs ?? []) === []): ?>
        <div class="lc-card__body"><div class="lc-muted">Sem logs.</div></div>
    <?php else: ?>
        <div class="lc-table-wrap">
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Ação</th>
                    <th>Em</th>
                    <th>Usuário</th>
                    <th>IP</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?= (int)$l['id'] ?></td>
                        <td><?= htmlspecialchars((string)$l['action'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$l['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($l['user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($l['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
