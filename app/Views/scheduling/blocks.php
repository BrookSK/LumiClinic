<?php
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>>|null $blocks */
/** @var string|null $from */
/** @var string|null $to */
/** @var int|null $filter_professional_id */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Bloqueios';

$blocks = $blocks ?? [];
$from = (string)($from ?? date('Y-m-d'));
$to = (string)($to ?? date('Y-m-d'));
$filterProfessionalId = (int)($filter_professional_id ?? 0);

$profMap = [];
foreach ($professionals as $p) {
    $profMap[(int)$p['id']] = (string)($p['name'] ?? '');
}

ob_start();
?>

<div class="lc-card">
    <div class="lc-card__header">Criar bloqueio</div>
    <div class="lc-card__body">
        <form method="post" action="/blocks/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 2fr 2fr 2fr 2fr; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Profissional</label>
                <select class="lc-select" name="professional_id">
                    <option value="0">Clínica inteira</option>
                    <?php foreach ($professionals as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Início</label>
                <input class="lc-input" type="datetime-local" name="start_at" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Fim</label>
                <input class="lc-input" type="datetime-local" name="end_at" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="type">
                    <option value="manual">Manual</option>
                    <option value="holiday">Feriado</option>
                    <option value="maintenance">Manutenção</option>
                </select>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Motivo</label>
                <input class="lc-input" type="text" name="reason" />
            </div>

            <div style="grid-column: 1 / -1;">
                <button class="lc-btn" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div class="lc-card" style="margin-top:16px;">
    <div class="lc-card__header">Bloqueios cadastrados</div>
    <div class="lc-card__body">
        <form method="get" action="/blocks" class="lc-form lc-flex lc-gap-md lc-flex--wrap" style="align-items:end; margin-bottom:12px;">
            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars((string)$from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars((string)$to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div class="lc-field">
                <label class="lc-label">Profissional</label>
                <select class="lc-select" name="professional_id">
                    <option value="0" <?= $filterProfessionalId === 0 ? 'selected' : '' ?>>Todos</option>
                    <?php foreach ($professionals as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $filterProfessionalId ? 'selected' : '' ?>><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="lc-btn" type="submit">Filtrar</button>
        </form>

        <?php if (!is_array($blocks) || $blocks === []): ?>
            <div class="lc-muted">Nenhum bloqueio encontrado no período.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Profissional</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($blocks as $b): ?>
                        <?php $pid = isset($b['professional_id']) ? (int)$b['professional_id'] : 0; ?>
                        <tr>
                            <td><?= (int)($b['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($pid > 0 ? (($profMap[$pid] ?? '') !== '' ? (string)$profMap[$pid] : ('#' . $pid)) : 'Clínica inteira', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($b['start_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($b['end_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($b['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($b['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
