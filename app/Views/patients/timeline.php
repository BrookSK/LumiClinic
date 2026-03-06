<?php
/** @var array<string,mixed> $patient */
/** @var list<array<string,mixed>> $items */
/** @var array<string,mixed> $filters */

$title = 'Paciente - Linha do tempo';
$patient = $patient ?? null;
$items = $items ?? [];
$filters = $filters ?? [];

$types = isset($_GET['types']) ? (string)$_GET['types'] : '';
$from = isset($_GET['from']) ? (string)$_GET['from'] : '';
$to = isset($_GET['to']) ? (string)$_GET['to'] : '';

$availableTypes = [
    'appointment' => 'Agendamentos',
    'consultation' => 'Consultas executadas',
    'consultation_attachment' => 'Anexos de consulta',
    'medical_record' => 'Prontuário',
    'medical_image' => 'Imagens',
    'consent_acceptance' => 'Aceites de termo',
    'signature' => 'Assinaturas',
];

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Linha do tempo</div>
        <div class="lc-muted" style="margin-top:6px;">
            <?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar ao perfil</a>
        <a class="lc-btn lc-btn--secondary" href="/patients/timeline/export.csv?patient_id=<?= (int)($patient['id'] ?? 0) ?>&types=<?= urlencode($types) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>" target="_blank">Exportar CSV</a>
    </div>
</div>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__body">
        <form method="get" action="/patients/timeline" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 1fr 120px; align-items:end;">
            <input type="hidden" name="patient_id" value="<?= (int)($patient['id'] ?? 0) ?>" />

            <div class="lc-field">
                <label class="lc-label">Tipos (csv)</label>
                <input class="lc-input" type="text" name="types" value="<?= htmlspecialchars($types, ENT_QUOTES, 'UTF-8') ?>" placeholder="appointment,consultation,medical_record..." />
                <div class="lc-muted" style="margin-top:6px;">
                    Disponíveis: <?= htmlspecialchars(implode(', ', array_keys($availableTypes)), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>

            <div class="lc-field">
                <label class="lc-label">De</label>
                <input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Até</label>
                <input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" />
            </div>

            <button class="lc-btn lc-btn--secondary" type="submit">Filtrar</button>
        </form>
    </div>
</div>

<?php if (!is_array($items) || $items === []): ?>
    <div class="lc-muted">Nenhum evento encontrado.</div>
<?php else: ?>
    <div class="lc-card">
        <div class="lc-card__header">Eventos</div>
        <div class="lc-card__body">
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Título</th>
                        <th>Descrição</th>
                        <th style="width:1%; white-space:nowrap;">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <?php $t = (string)($it['type'] ?? ''); ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($it['occurred_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($availableTypes[$t] ?? $t), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($it['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (isset($it['link']) && (string)$it['link'] !== ''): ?>
                                    <a class="lc-btn lc-btn--secondary" href="<?= htmlspecialchars((string)$it['link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">Abrir</a>
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
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
