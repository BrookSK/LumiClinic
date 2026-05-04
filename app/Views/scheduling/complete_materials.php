<?php

/** @var array<string,mixed> $appointment */
/** @var array<string,mixed> $service */
/** @var list<array<string,mixed>> $defaults */
/** @var list<array<string,mixed>> $materials */
/** @var array<int,string> $used_qty */
/** @var string|null $date */
/** @var string|null $view */
/** @var int|null $professional_id */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Finalizar sessão - Materiais';

$serviceName = htmlspecialchars((string)($service['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$patientName = htmlspecialchars((string)($appointment['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$startAt = (string)($appointment['start_at'] ?? '');
$startFmt = $startAt !== '' ? date('d/m/Y H:i', strtotime($startAt)) : '';
$returnDate = (string)($date !== null && $date !== '' ? $date : substr($startAt, 0, 10));

ob_start();
?>

<style>
.cm-header { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:20px; }
.cm-title { font-weight:850; font-size:20px; color:rgba(31,41,55,.96); }
.cm-sub { font-size:13px; color:rgba(31,41,55,.50); margin-top:3px; }
.cm-cols { display:grid; grid-template-columns:1fr 1fr; gap:18px; align-items:start; }
@media (max-width:768px) { .cm-cols { grid-template-columns:1fr; } }
.cm-panel { border-radius:14px; border:1px solid rgba(17,24,39,.08); background:var(--lc-surface,#fff); box-shadow:0 2px 12px rgba(17,24,39,.06); overflow:hidden; }
.cm-panel-head { padding:14px 18px; border-bottom:1px solid rgba(17,24,39,.06); font-weight:750; font-size:14px; color:rgba(31,41,55,.9); display:flex; align-items:center; gap:8px; }
.cm-panel-body { padding:16px 18px; }
.cm-mat-row { display:grid; grid-template-columns:1fr 120px; gap:10px; align-items:center; padding:10px 0; border-bottom:1px solid rgba(17,24,39,.04); }
.cm-mat-row:last-child { border-bottom:none; }
.cm-mat-name { font-weight:600; font-size:13px; color:rgba(31,41,55,.9); }
.cm-mat-unit { font-size:11px; color:rgba(31,41,55,.45); }
.cm-extra-row { display:grid; grid-template-columns:1fr 100px 36px; gap:8px; align-items:center; margin-bottom:8px; }
.cm-btn-add { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; border:1px dashed rgba(99,102,241,.3); background:rgba(99,102,241,.04); color:#6366f1; font-size:12px; font-weight:700; cursor:pointer; transition:all .15s; }
.cm-btn-add:hover { background:rgba(99,102,241,.1); border-color:rgba(99,102,241,.5); }
.cm-btn-remove { width:32px; height:32px; border-radius:8px; border:1px solid rgba(239,68,68,.2); background:rgba(239,68,68,.04); color:#dc2626; font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .15s; }
.cm-btn-remove:hover { background:rgba(239,68,68,.12); }
.cm-info { display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:12px; border:1px solid rgba(238,184,16,.22); background:rgba(253,229,159,.08); margin-bottom:18px; }
.cm-info-icon { font-size:22px; flex-shrink:0; }
.cm-info-text { font-size:13px; color:rgba(31,41,55,.7); line-height:1.5; }
.cm-info-text strong { color:rgba(31,41,55,.9); }
</style>

<!-- Header -->
<div class="cm-header">
    <div>
        <div class="cm-title">Finalizar sessao</div>
        <div class="cm-sub">Confirme os materiais usados e registre uma observacao.</div>
    </div>
    <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/schedule?date=<?= urlencode($returnDate) ?>">Voltar</a>
</div>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Info bar -->
<div class="cm-info">
    <span class="cm-info-icon">🩺</span>
    <div class="cm-info-text">
        <strong><?= $serviceName ?></strong>
        <?php if ($patientName !== ''): ?> &middot; <?= $patientName ?><?php endif; ?>
        <?php if ($startFmt !== ''): ?> &middot; <?= htmlspecialchars($startFmt, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
    </div>
</div>

<form method="post" action="/schedule/complete-materials" class="lc-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="id" value="<?= (int)$appointment['id'] ?>" />
    <input type="hidden" name="date" value="<?= htmlspecialchars($returnDate, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="view" value="<?= htmlspecialchars((string)($view ?? 'day'), ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="professional_id" value="<?= (int)($professional_id ?? 0) ?>" />

    <div class="cm-cols">

        <!-- LEFT: Materiais padrao -->
        <div class="cm-panel">
            <div class="cm-panel-head">📦 Materiais padrao do servico</div>
            <div class="cm-panel-body">
                <?php if ($defaults === []): ?>
                    <div style="text-align:center;padding:20px;color:rgba(31,41,55,.4);font-size:13px;">
                        Nenhum material padrao configurado para este servico.
                    </div>
                <?php else: ?>
                    <?php foreach ($defaults as $d): ?>
                        <?php
                        $mid = (int)$d['material_id'];
                        $rawQty = (float)$d['quantity_per_session'];
                        $displayQty = ($rawQty == (int)$rawQty) ? (string)(int)$rawQty : number_format($rawQty, 2, ',', '.');
                        if (isset($used_qty[$mid])) {
                            $uq = (float)$used_qty[$mid];
                            $inputVal = ($uq == (int)$uq) ? (string)(int)$uq : (string)$uq;
                        } else {
                            $inputVal = ($rawQty == (int)$rawQty) ? (string)(int)$rawQty : (string)$rawQty;
                        }
                        ?>
                        <div class="cm-mat-row">
                            <div>
                                <div class="cm-mat-name"><?= htmlspecialchars((string)$d['material_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="cm-mat-unit"><?= htmlspecialchars((string)$d['material_unit'], ENT_QUOTES, 'UTF-8') ?> &middot; padrao: <?= $displayQty ?></div>
                            </div>
                            <input class="lc-input" type="text" name="qty[<?= $mid ?>]" value="<?= htmlspecialchars($inputVal, ENT_QUOTES, 'UTF-8') ?>" style="text-align:center;" />
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: Materiais extras + Observacao -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- Materiais extras -->
            <div class="cm-panel">
                <div class="cm-panel-head">➕ Materiais extras</div>
                <div class="cm-panel-body">
                    <div style="font-size:12px;color:rgba(31,41,55,.45);margin-bottom:12px;">Adicione materiais usados fora do padrao.</div>

                    <div id="extras-container">
                        <div class="cm-extra-row" data-extra-row>
                            <select class="lc-select" name="extra_material_id[]" style="font-size:13px;">
                                <option value="">Selecione...</option>
                                <?php foreach ($materials as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$m['unit'], ENT_QUOTES, 'UTF-8') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <input class="lc-input" type="text" name="extra_qty[]" placeholder="Qtd" style="text-align:center;" />
                            <button type="button" class="cm-btn-remove" onclick="removeExtraRow(this)" title="Remover">&times;</button>
                        </div>
                    </div>

                    <button type="button" class="cm-btn-add" onclick="addExtraRow()">+ Adicionar material</button>
                </div>
            </div>

            <!-- Observacao -->
            <div class="cm-panel">
                <div class="cm-panel-head">📝 Observacao</div>
                <div class="cm-panel-body">
                    <textarea class="lc-input" name="note" rows="3" required placeholder="Descreva o que foi realizado..." style="resize:vertical;font-size:13px;"></textarea>
                </div>
            </div>

            <!-- Submit -->
            <button class="lc-btn lc-btn--primary" type="submit" style="width:100%;padding:12px;font-size:15px;font-weight:800;">
                ✓ Finalizar sessao
            </button>
        </div>

    </div>
</form>

<script>
function addExtraRow() {
    var container = document.getElementById('extras-container');
    var first = container.querySelector('[data-extra-row]');
    var clone = first.cloneNode(true);
    clone.querySelector('select').value = '';
    clone.querySelector('input').value = '';
    container.appendChild(clone);
}

function removeExtraRow(btn) {
    var container = document.getElementById('extras-container');
    var rows = container.querySelectorAll('[data-extra-row]');
    if (rows.length <= 1) {
        // Don't remove the last row, just clear it
        rows[0].querySelector('select').value = '';
        rows[0].querySelector('input').value = '';
        return;
    }
    btn.closest('[data-extra-row]').remove();
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
