<?php
/** @var string $from */
/** @var string $to */
/** @var int $professional_id */
/** @var list<array<string,mixed>> $by_professional */
/** @var list<array<string,mixed>> $by_service */
/** @var float $ticket_medio */
/** @var int $appointments */
/** @var int $paid_sales */
/** @var float $conversion_rate */
/** @var float $recurring_revenue */
/** @var list<array<string,mixed>> $professionals */
/** @var bool $is_professional */

$title = 'Financeiro - Relatórios';

$profMap = [];
foreach ($professionals as $p) {
    $profMap[(int)$p['id']] = $p;
}

ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/finance/reports" class="lc-form" style="display:flex; gap: 12px; flex-wrap: wrap; align-items:end;">
            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <?php if (!$is_professional): ?>
                <div class="lc-field">
                    <label class="lc-label">Profissional</label>
                    <select class="lc-select" name="professional_id">
                        <option value="0">Todos</option>
                        <?php foreach ($professionals as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professional_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
            <?php endif; ?>

            <button class="lc-btn" type="submit">Gerar</button>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Ticket médio (vendas)</div>
    <div class="lc-card__body">R$ <?= number_format((float)$ticket_medio, 2, ',', '.') ?></div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Conversão (agenda → venda paga)</div>
    <div class="lc-card__body" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
        <div><strong>Agendamentos:</strong> <?= (int)$appointments ?></div>
        <div><strong>Vendas pagas:</strong> <?= (int)$paid_sales ?></div>
        <div><strong>Taxa:</strong> <?= number_format(((float)$conversion_rate) * 100.0, 2, ',', '.') ?>%</div>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Receita recorrente (assinaturas)</div>
    <div class="lc-card__body">R$ <?= number_format((float)$recurring_revenue, 2, ',', '.') ?></div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Receita por profissional (pagamentos pagos)</div>
    <div class="lc-card__body">
        <?php if ($by_professional === []): ?>
            <div class="lc-muted">Sem dados.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Profissional</th>
                    <th>Receita</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_professional as $r): ?>
                    <?php
                        $pid = $r['professional_id'] === null ? 0 : (int)$r['professional_id'];
                        $name = $pid > 0 && isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : '-';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= number_format((float)$r['revenue'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Receita por serviço (procedimentos)</div>
    <div class="lc-card__body">
        <?php if ($by_service === []): ?>
            <div class="lc-muted">Sem dados.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Service ID</th>
                    <th>Receita</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_service as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['service_id'] ?></td>
                        <td>R$ <?= number_format((float)$r['revenue'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
