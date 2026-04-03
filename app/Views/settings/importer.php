<?php
$title = 'Importador Clinicorp';
$csrf = $_SESSION['_csrf'] ?? '';
$types = $types ?? [];
$history = $history ?? [];
$result = $result ?? null;
$selectedType = $selected_type ?? '';

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) {
        if (in_array($p, $perms['deny'], true)) return false;
        return in_array($p, $perms['allow'], true);
    }
    return in_array($p, $perms, true);
};

// Group types
$groups = [];
foreach ($types as $key => $t) {
    $groups[$t['group']][$key] = $t;
}

ob_start();
?>

<style>
.imp-head{font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px}
.imp-sub{font-size:13px;color:rgba(31,41,55,.50);margin-bottom:20px;line-height:1.6}
.imp-section{padding:18px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.imp-section__title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px}
.imp-section__desc{font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;line-height:1.5}
.imp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px}
.imp-card{padding:14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:rgba(0,0,0,.01);cursor:pointer;transition:all 160ms ease;position:relative}
.imp-card:hover{border-color:rgba(99,102,241,.3);background:rgba(99,102,241,.03)}
.imp-card.selected{border-color:rgba(99,102,241,.5);background:rgba(99,102,241,.06)}
.imp-card__icon{font-size:22px;margin-bottom:6px}
.imp-card__label{font-weight:700;font-size:13px;color:rgba(31,41,55,.90)}
.imp-card__desc{font-size:11px;color:rgba(31,41,55,.45);margin-top:3px;line-height:1.4}
.imp-card__cols{font-size:10px;color:rgba(99,102,241,.7);margin-top:6px;line-height:1.3}
.imp-card__ignored{font-size:10px;color:rgba(239,68,68,.6);margin-top:2px}
.imp-card__badge{position:absolute;top:8px;right:8px;font-size:9px;padding:2px 7px;border-radius:6px;font-weight:700}
.imp-card__badge--ok{background:rgba(34,197,94,.1);color:rgba(34,197,94,.8)}
.imp-card__badge--wip{background:rgba(245,158,11,.1);color:rgba(245,158,11,.8)}
.imp-upload{margin-top:18px;padding:20px;border:2px dashed rgba(99,102,241,.25);border-radius:14px;text-align:center;display:none}
.imp-upload.visible{display:block}
.imp-result{padding:16px;border-radius:12px;margin-bottom:16px}
.imp-result--ok{background:rgba(34,197,94,.06);border:1px solid rgba(34,197,94,.2)}
.imp-result--err{background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2)}
.imp-hist-table{width:100%;border-collapse:collapse;font-size:12px}
.imp-hist-table th{text-align:left;padding:8px 10px;border-bottom:1px solid rgba(17,24,39,.08);color:rgba(31,41,55,.50);font-weight:600;font-size:11px}
.imp-hist-table td{padding:8px 10px;border-bottom:1px solid rgba(17,24,39,.04);color:rgba(31,41,55,.80)}
.imp-tutorial{padding:16px;border-radius:12px;background:rgba(99,102,241,.04);border:1px solid rgba(99,102,241,.12);margin-bottom:16px}
.imp-tutorial__title{font-weight:700;font-size:13px;color:rgba(99,102,241,.8);margin-bottom:8px}
.imp-tutorial ol{margin:0;padding-left:20px;font-size:12px;color:rgba(31,41,55,.65);line-height:1.8}
</style>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
    <a href="/settings" style="color:rgba(99,102,241,.7);text-decoration:none;font-size:13px;">← Configurações</a>
</div>

<div class="imp-head">Importador Clinicorp</div>
<div class="imp-sub">
    Importe os dados exportados do Clinicorp.com para o sistema. Selecione o tipo de dado, faça upload do arquivo .xlsx e o sistema cuida do resto.
</div>

<!-- Tutorial -->
<div class="imp-tutorial">
    <div class="imp-tutorial__title">📖 Como exportar dados do Clinicorp</div>
    <ol>
        <li>Acesse o sistema da Clinicorp.com e clique nos <strong>três pontinhos</strong> para abrir o menu lateral.</li>
        <li>Vá até <strong>Relatórios e Indicadores</strong>.</li>
        <li>Escolha a seção desejada (Agendamentos, Estoque, Financeiro, Pacientes ou Tratamentos).</li>
        <li>Selecione o relatório específico (ex: Geral, Contas a Pagar, etc.).</li>
        <li>Configure os filtros (período, clínica, profissional, etc.) e clique em <strong>Listar</strong>.</li>
        <li>Clique no <strong>ícone de download</strong> (📥) acima da tabela para exportar o .xlsx.</li>
        <li>Salve o arquivo e faça o upload aqui no importador correspondente.</li>
    </ol>
</div>

<?php if ($result !== null): ?>
    <?php $hasErrors = !empty($result['errors']); ?>
    <div class="imp-result <?= $hasErrors && $result['imported'] === 0 ? 'imp-result--err' : 'imp-result--ok' ?>">
        <div style="font-weight:700;font-size:14px;margin-bottom:6px;">
            <?= $hasErrors && $result['imported'] === 0 ? '❌ Erro na importação' : '✅ Importação concluída' ?>
        </div>
        <div style="font-size:13px;color:rgba(31,41,55,.70);">
            <strong><?= (int)$result['imported'] ?></strong> registros importados
            <?php if ($result['skipped'] > 0): ?> · <strong><?= (int)$result['skipped'] ?></strong> ignorados<?php endif; ?>
            <?php if ($hasErrors): ?> · <strong><?= count($result['errors']) ?></strong> erros<?php endif; ?>
        </div>
        <?php if ($hasErrors): ?>
            <details style="margin-top:8px;">
                <summary style="font-size:11px;color:rgba(239,68,68,.7);cursor:pointer;">Ver erros</summary>
                <ul style="font-size:11px;color:rgba(239,68,68,.6);margin-top:4px;padding-left:18px;">
                    <?php foreach (array_slice($result['errors'], 0, 20) as $err): ?>
                        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Import types grouped -->
<?php foreach ($groups as $groupName => $groupTypes): ?>
<div class="imp-section">
    <div class="imp-section__title"><?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="imp-grid">
        <?php foreach ($groupTypes as $key => $t): ?>
            <?php $isWip = ($t['status'] ?? '') === 'construction'; ?>
            <div class="imp-card <?= $selectedType === $key ? 'selected' : '' ?>"
                 data-type="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                 data-wip="<?= $isWip ? '1' : '0' ?>"
                 onclick="selectImportType(this)">
                <span class="imp-card__badge <?= $isWip ? 'imp-card__badge--wip' : 'imp-card__badge--ok' ?>">
                    <?= $isWip ? '🚧 Em breve' : '✓ Disponível' ?>
                </span>
                <div class="imp-card__icon"><?= $t['icon'] ?></div>
                <div class="imp-card__label"><?= htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="imp-card__desc"><?= htmlspecialchars($t['desc'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="imp-card__cols">Colunas: <?= htmlspecialchars($t['clinicorp_cols'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php if (!empty($t['ignored'])): ?>
                    <div class="imp-card__ignored">⚠ Ignorados: <?= htmlspecialchars($t['ignored'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Upload area -->
<div class="imp-upload" id="uploadArea">
    <form method="post" action="/settings/importer/upload" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="import_type" id="importTypeInput" value="" />

        <div style="font-size:14px;font-weight:700;color:rgba(31,41,55,.85);margin-bottom:8px;">
            📤 Upload do arquivo <span id="uploadTypeLabel" style="color:rgba(99,102,241,.8);"></span>
        </div>
        <div style="font-size:12px;color:rgba(31,41,55,.45);margin-bottom:14px;">
            Selecione o arquivo .xlsx exportado do Clinicorp para este tipo de dado.
        </div>

        <div style="display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;">
            <input type="file" name="xlsx_file" accept=".xlsx" required class="lc-input" style="max-width:320px;" />
            <button type="submit" class="lc-btn lc-btn--primary">Importar</button>
        </div>
    </form>
</div>

<!-- History -->
<?php if (!empty($history)): ?>
<div class="imp-section" style="margin-top:20px;">
    <div class="imp-section__title">Histórico de importações</div>
    <div class="imp-section__desc">Últimas importações realizadas nesta clínica.</div>
    <div style="overflow-x:auto;">
        <table class="imp-hist-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Arquivo</th>
                    <th>Total</th>
                    <th>Importados</th>
                    <th>Ignorados</th>
                    <th>Erros</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($types[$h['import_type']]['label'] ?? $h['import_type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($h['file_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$h['total_rows'] ?></td>
                    <td style="color:rgba(34,197,94,.8);font-weight:600;"><?= (int)$h['imported_rows'] ?></td>
                    <td><?= (int)$h['skipped_rows'] ?></td>
                    <td style="<?= (int)$h['error_rows'] > 0 ? 'color:rgba(239,68,68,.7);font-weight:600;' : '' ?>"><?= (int)$h['error_rows'] ?></td>
                    <td><?= htmlspecialchars($h['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function selectImportType(el) {
    if (el.dataset.wip === '1') return;

    document.querySelectorAll('.imp-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');

    const type = el.dataset.type;
    const label = el.querySelector('.imp-card__label').textContent;

    document.getElementById('importTypeInput').value = type;
    document.getElementById('uploadTypeLabel').textContent = label;
    document.getElementById('uploadArea').classList.add('visible');
    document.getElementById('uploadArea').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

<?php if ($selectedType): ?>
document.addEventListener('DOMContentLoaded', function() {
    const card = document.querySelector('[data-type="<?= htmlspecialchars($selectedType, ENT_QUOTES, 'UTF-8') ?>"]');
    if (card) selectImportType(card);
});
<?php endif; ?>
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
