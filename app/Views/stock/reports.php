<?php

/** @var string $from */
/** @var string $to */
/** @var array<string,mixed> $summary */
/** @var list<array<string,mixed>> $by_material */
/** @var list<array<string,mixed>> $by_service */
/** @var list<array<string,mixed>> $by_professional */

$title = 'Relatórios de Estoque e Custos';
ob_start();
?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Filtros</div>
    <div class="lc-card__body">
        <form method="get" action="/stock/reports" class="lc-form" style="display:flex; gap: 12px; flex-wrap: wrap; align-items:end;">
            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <button class="lc-btn" type="submit">Atualizar</button>
                <a class="lc-btn lc-btn--secondary" href="/stock/alerts">Alertas</a>
                <a class="lc-btn lc-btn--secondary" href="/stock/movements">Movimentações</a>
            </div>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Resumo do período</div>
    <div class="lc-card__body">
        <div style="display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px;">
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Custo total saídas: <strong>R$ <?= number_format((float)($summary['total_exit_cost'] ?? 0), 2, ',', '.') ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Custo perdas: <strong>R$ <?= number_format((float)($summary['total_loss_cost'] ?? 0), 2, ',', '.') ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Custo vencimento: <strong>R$ <?= number_format((float)($summary['total_expiration_cost'] ?? 0), 2, ',', '.') ?></strong></div></div>
            <div class="lc-card" style="margin:0;"><div class="lc-card__body">Custo consumo (sessões): <strong>R$ <?= number_format((float)($summary['total_session_cost'] ?? 0), 2, ',', '.') ?></strong></div></div>
        </div>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Consumo por material</div>
    <div class="lc-card__body">
        <?php if ($by_material === []): ?>
            <div class="lc-muted">Sem dados no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Material</th>
                    <th>Qtd saída</th>
                    <th>Custo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_material as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['material_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$it['qty'], 3, ',', '.') ?> <?= htmlspecialchars((string)$it['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>R$ <?= number_format((float)$it['cost'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Consumo por serviço</div>
    <div class="lc-card__body">
        <?php if ($by_service === []): ?>
            <div class="lc-muted">Sem dados no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Serviço</th>
                    <th>Qtd sessões</th>
                    <th>Custo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_service as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$it['sessions'] ?></td>
                        <td>R$ <?= number_format((float)$it['cost'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Consumo por profissional</div>
    <div class="lc-card__body">
        <?php if ($by_professional === []): ?>
            <div class="lc-muted">Sem dados no período.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Profissional</th>
                    <th>Qtd sessões</th>
                    <th>Custo</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($by_professional as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$it['professional_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$it['sessions'] ?></td>
                        <td>R$ <?= number_format((float)$it['cost'], 2, ',', '.') ?></td>
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
