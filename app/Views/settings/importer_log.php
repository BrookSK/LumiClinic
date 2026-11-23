<?php
$title = 'Detalhes da importação';
$types = $types ?? [];
$log = $log ?? [];

$logId = (int)($log['id'] ?? 0);
$typeKey = (string)($log['import_type'] ?? '');
$typeLabel = (string)($types[$typeKey]['label'] ?? $typeKey);
$skippedList = $log['skipped_list'] ?? null;
$hasSkippedDetails = is_array($skippedList);
$skippedList = $hasSkippedDetails ? $skippedList : [];
$errorsList = is_array($log['errors_list'] ?? null) ? $log['errors_list'] : [];
$skippedRows = (int)($log['skipped_rows'] ?? 0);
$errorRows = (int)($log['error_rows'] ?? 0);

// Resumo por motivo
$byReason = [];
foreach ($skippedList as $s) {
    $r = (string)($s['reason'] ?? '');
    $byReason[$r] = ($byReason[$r] ?? 0) + 1;
}
arsort($byReason);

// Erros: separa "Linha N: mensagem"
$errorsParsed = [];
foreach ($errorsList as $err) {
    $line = '';
    $msg = (string)$err;
    if (preg_match('/^Linha (\d+):\s*(.*)$/u', $msg, $m)) {
        $line = $m[1];
        $msg = $m[2];
    }
    $errorsParsed[] = ['line' => $line, 'msg' => $msg];
}

$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

ob_start();
?>

<style>
.impl-head{font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px}
.impl-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.impl-section__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px}
.impl-section__desc{font-size:12px;color:rgba(31,41,55,.45);margin-bottom:12px;line-height:1.5}
.impl-meta{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;font-size:13px;color:rgba(31,41,55,.80)}
.impl-meta__label{font-size:11px;color:rgba(31,41,55,.45);font-weight:600}
.impl-counts{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
.impl-count{padding:10px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);min-width:110px}
.impl-count__num{font-weight:800;font-size:20px}
.impl-count__label{font-size:11px;color:rgba(31,41,55,.50)}
.impl-count--ok .impl-count__num{color:rgba(34,197,94,.85)}
.impl-count--skip .impl-count__num{color:rgba(245,158,11,.9)}
.impl-count--err .impl-count__num{color:rgba(239,68,68,.8)}
.impl-table{width:100%;border-collapse:collapse;font-size:12px}
.impl-table th{text-align:left;padding:8px 10px;border-bottom:1px solid rgba(17,24,39,.08);color:rgba(31,41,55,.50);font-weight:600;font-size:11px}
.impl-table td{padding:8px 10px;border-bottom:1px solid rgba(17,24,39,.04);color:rgba(31,41,55,.80);vertical-align:top}
.impl-reason{display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-bottom:1px solid rgba(17,24,39,.04);font-size:13px;color:rgba(31,41,55,.80)}
.impl-notice{padding:14px;border-radius:12px;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.25);font-size:13px;color:rgba(31,41,55,.75);margin-bottom:16px;line-height:1.5}
</style>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
    <a href="/settings/importer" style="color:rgba(99,102,241,.7);text-decoration:none;font-size:13px;">← Importador</a>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
    <div class="impl-head" style="margin-bottom:0;">Detalhes da importação</div>
    <?php if (!empty($skippedList) || !empty($errorsParsed)): ?>
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/settings/importer/log/export.csv?id=<?= $logId ?>">Exportar CSV</a>
    <?php endif; ?>
</div>

<div class="impl-section">
    <div class="impl-meta">
        <div><div class="impl-meta__label">Tipo</div><?= $h($typeLabel) ?></div>
        <div><div class="impl-meta__label">Arquivo</div><span style="word-break:break-all;"><?= $h($log['file_name'] ?? '') ?></span></div>
        <div><div class="impl-meta__label">Data</div><?= $h($log['created_at'] ?? '') ?></div>
        <div><div class="impl-meta__label">Enviado por</div><?= $h((string)($log['created_by_name'] ?? '') !== '' ? $log['created_by_name'] : '—') ?></div>
    </div>
    <div class="impl-counts">
        <div class="impl-count"><div class="impl-count__num"><?= (int)($log['total_rows'] ?? 0) ?></div><div class="impl-count__label">Total de linhas</div></div>
        <div class="impl-count impl-count--ok"><div class="impl-count__num"><?= (int)($log['imported_rows'] ?? 0) ?></div><div class="impl-count__label">Importados</div></div>
        <div class="impl-count impl-count--skip"><div class="impl-count__num"><?= $skippedRows ?></div><div class="impl-count__label">Ignorados</div></div>
        <div class="impl-count impl-count--err"><div class="impl-count__num"><?= $errorRows ?></div><div class="impl-count__label">Erros</div></div>
    </div>
</div>

<?php if (!$hasSkippedDetails && $skippedRows > 0): ?>
    <div class="impl-notice">
        Os detalhes dos ignorados só ficam disponíveis para importações feitas a partir desta atualização. Reimporte o arquivo para ver quem foi ignorado e o motivo.
    </div>
<?php endif; ?>

<?php if (!empty($byReason)): ?>
<div class="impl-section">
    <div class="impl-section__title">Resumo por motivo</div>
    <div class="impl-section__desc">Quantidade de registros ignorados por motivo.</div>
    <?php foreach ($byReason as $reason => $count): ?>
        <div class="impl-reason">
            <span><?= $h($reason) ?></span>
            <strong><?= (int)$count ?></strong>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($skippedList)): ?>
<div class="impl-section">
    <div class="impl-section__title">Registros ignorados</div>
    <div class="impl-section__desc">
        A linha corresponde à numeração da planilha (a linha 1 é o cabeçalho).
        <?php if (count($skippedList) < $skippedRows): ?>
            Exibindo os primeiros <?= count($skippedList) ?> de <?= $skippedRows ?> ignorados.
        <?php endif; ?>
    </div>
    <input type="search" id="implFilter" class="lc-input" placeholder="Filtrar por nome ou motivo..." style="max-width:320px;margin-bottom:10px;" />
    <div style="overflow-x:auto;">
        <table class="impl-table" id="implSkippedTable">
            <thead>
                <tr>
                    <th style="width:70px;">Linha</th>
                    <th>Identificação</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($skippedList as $s): ?>
                <tr>
                    <td><?= (int)($s['line'] ?? 0) ?></td>
                    <td><?= $h($s['name'] ?? '') ?></td>
                    <td><?= $h($s['reason'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="implFilterEmpty" style="display:none;font-size:12px;color:rgba(31,41,55,.45);margin-top:8px;">Nenhum registro encontrado para o filtro.</div>
</div>
<?php endif; ?>

<?php if (!empty($errorsParsed)): ?>
<div class="impl-section">
    <div class="impl-section__title">Erros</div>
    <div class="impl-section__desc">
        Linhas que não puderam ser gravadas por erro no processamento.
        <?php if (count($errorsParsed) < $errorRows): ?>
            Exibindo os primeiros <?= count($errorsParsed) ?> de <?= $errorRows ?> erros.
        <?php endif; ?>
    </div>
    <div style="overflow-x:auto;">
        <table class="impl-table">
            <thead>
                <tr>
                    <th style="width:70px;">Linha</th>
                    <th>Mensagem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($errorsParsed as $e): ?>
                <tr>
                    <td><?= $e['line'] !== '' ? (int)$e['line'] : '—' ?></td>
                    <td style="color:rgba(239,68,68,.75);"><?= $h($e['msg']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($skippedRows === 0 && $errorRows === 0): ?>
    <div class="impl-section" style="font-size:13px;color:rgba(31,41,55,.65);">
        Nenhum registro foi ignorado e não houve erros nesta importação.
    </div>
<?php endif; ?>

<?php if (!empty($skippedList)): ?>
<script>
(function () {
    var input = document.getElementById('implFilter');
    var table = document.getElementById('implSkippedTable');
    var empty = document.getElementById('implFilterEmpty');
    if (!input || !table) return;
    input.addEventListener('input', function () {
        var q = input.value.toLowerCase().trim();
        var visible = 0;
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            var show = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
            tr.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (empty) empty.style.display = visible === 0 ? '' : 'none';
    });
})();
</script>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
