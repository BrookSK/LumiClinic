<?php
$title = 'Admin - Clínicas piloto';
$csrf = $_SESSION['_csrf'] ?? '';

$settings = isset($settings) && is_array($settings) ? $settings : ['percent' => 0, 'duration' => 0, 'unit' => 'months', 'configured' => false];
$schemaReady = !empty($schema_ready);
$clinics = isset($clinics) && is_array($clinics) ? $clinics : [];
$success = $success ?? null;
$error = $error ?? null;

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$fmtDt = function ($v): string {
    $s = trim((string)($v ?? ''));
    if ($s === '') return '—';
    try { return (new \DateTimeImmutable($s))->format('d/m/Y H:i'); } catch (\Throwable $ex) { return $s; }
};

$percentVal = (float)$settings['percent'] > 0 ? rtrim(rtrim(number_format((float)$settings['percent'], 2, '.', ''), '0'), '.') : '';
$durationVal = (int)$settings['duration'] > 0 ? (string)(int)$settings['duration'] : '';
$unitVal = (string)$settings['unit'];
$units = ['hours' => 'Horas', 'days' => 'Dias', 'months' => 'Meses', 'years' => 'Anos'];

$statusMap = [
    'active' => ['Ativo', '#16a34a'],
    'expired' => ['Vencido', '#6b7280'],
    'forfeited' => ['Perdido (trocou de plano)', '#b91c1c'],
    'removed' => ['Removido do programa', '#6b7280'],
];

ob_start();
?>

<style>
.pl-card{padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px}
.pl-title{font-weight:750;font-size:14px;color:rgba(31,41,55,.90);margin-bottom:4px}
.pl-desc{font-size:12px;color:rgba(31,41,55,.50);margin-bottom:14px;line-height:1.5}
.pl-grid{display:grid;grid-template-columns:180px 140px 180px;gap:12px;align-items:end}
.pl-rules{margin:0;padding-left:18px;font-size:13px;color:rgba(31,41,55,.70);line-height:1.7}
.pl-tag{display:inline-flex;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
.pl-preview{margin-top:12px;padding:10px 12px;border-radius:10px;background:rgba(99,102,241,.05);border:1px solid rgba(99,102,241,.14);font-size:13px;color:rgba(31,41,55,.75)}
@media(max-width:640px){.pl-grid{grid-template-columns:1fr 1fr}.pl-grid .pl-percent{grid-column:1 / -1}}
</style>

<div style="margin-bottom:18px;">
    <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">Desconto para clínicas piloto</div>
    <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Defina a porcentagem e por quanto tempo o desconto vale. Para marcar uma clínica, abra Clínicas → Editar e marque “Clínica piloto”.</div>
</div>

<?php if ($success): ?><div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= $e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= $e($error) ?></div><?php endif; ?>

<?php if (!$schemaReady): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;">
        O banco de dados ainda não tem as colunas do programa piloto. Abra <a href="/sys/settings/server" style="font-weight:700;">Servidor → Migrações do banco</a> e execute as pendentes antes de marcar clínicas.
    </div>
<?php endif; ?>

<div class="pl-card">
    <div class="pl-title">Condição do desconto</div>
    <div class="pl-desc">Use horas para testar (ex.: 1 hora). A condição é copiada para a clínica no momento em que ela é marcada como piloto: mudar aqui não altera clínicas que já estão no programa.</div>

    <form method="post" action="/sys/settings/pilot" class="lc-form" id="pilotForm">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>" />
        <div class="pl-grid">
            <div class="lc-field pl-percent">
                <label class="lc-label" for="plPercent">Desconto (%)</label>
                <input class="lc-input" type="number" id="plPercent" name="discount_percent" min="0.01" max="99.99" step="0.01" value="<?= $e($percentVal) ?>" placeholder="Ex.: 20" required />
            </div>
            <div class="lc-field">
                <label class="lc-label" for="plDuration">Duração</label>
                <input class="lc-input" type="number" id="plDuration" name="duration_value" min="1" step="1" value="<?= $e($durationVal) ?>" placeholder="Ex.: 3" required />
            </div>
            <div class="lc-field">
                <label class="lc-label" for="plUnit">Período em</label>
                <select class="lc-select" id="plUnit" name="duration_unit">
                    <?php foreach ($units as $k => $lbl): ?>
                        <option value="<?= $e($k) ?>" <?= $unitVal === $k ? 'selected' : '' ?>><?= $e($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="pl-preview" id="plPreview"></div>
        <div style="display:flex;justify-content:flex-end;margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        </div>
    </form>
</div>

<div class="pl-card">
    <div class="pl-title">Como o desconto funciona</div>
    <ul class="pl-rules">
        <li>O período começa quando a clínica é marcada como piloto e termina sozinho ao final da duração.</li>
        <li>O desconto vale para o primeiro plano contratado: o plano pago atual ou, se a clínica ainda estiver no teste, o primeiro plano pago que ela assinar.</li>
        <li>Se a clínica mudar de plano, perde o benefício. Ele não é transferido para o novo plano nem volta se ela retornar ao plano anterior.</li>
        <li>A mensagem de condição exclusiva aparece em “Minha assinatura” só para clínicas piloto e só durante o período.</li>
        <li>O valor cobrado no Asaas é atualizado ao marcar ou desmarcar a clínica e, quando o desconto acaba, pelo cron do servidor.</li>
    </ul>
</div>

<div class="pl-card">
    <div class="pl-title">Clínicas no programa</div>
    <?php if ($clinics === []): ?>
        <div class="pl-desc" style="margin-bottom:0;">Nenhuma clínica foi marcada como piloto ainda.</div>
    <?php else: ?>
        <div class="lc-table-wrap">
            <table class="lc-table">
                <thead><tr><th>Clínica</th><th>Desconto</th><th>Período</th><th>Plano com desconto</th><th>Situação</th></tr></thead>
                <tbody>
                <?php foreach ($clinics as $c): ?>
                    <?php
                    $st = (string)($c['status'] ?? '');
                    [$stLbl, $stClr] = $statusMap[$st] ?? ['Sem condição registrada', '#6b7280'];
                    ?>
                    <tr>
                        <td><a href="/sys/clinics/edit?id=<?= (int)$c['clinic_id'] ?>" style="font-weight:650;"><?= $e($c['name']) ?></a></td>
                        <td style="white-space:nowrap;"><?= isset($c['percent_label']) ? $e($c['percent_label']) . ' por ' . $e($c['duration_label'] ?? '') : '—' ?></td>
                        <td style="font-size:12px;white-space:nowrap;"><?= isset($c['starts_at']) ? $e($fmtDt($c['starts_at'])) . ' até ' . $e($fmtDt($c['ends_at'])) : '—' ?></td>
                        <td style="font-size:12px;"><?= isset($c['status']) ? $e($c['benefit_plan_name'] ?? 'Primeiro plano pago contratado') : '—' ?></td>
                        <td><span class="pl-tag" style="background:<?= $stClr ?>18;color:<?= $stClr ?>;border:1px solid <?= $stClr ?>30"><?= $e($stLbl) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var p = document.getElementById('plPercent'), d = document.getElementById('plDuration'), u = document.getElementById('plUnit'), out = document.getElementById('plPreview');
    var names = {hours: ['hora', 'horas'], days: ['dia', 'dias'], months: ['mês', 'meses'], years: ['ano', 'anos']};
    function upd() {
        var pv = parseFloat(String(p.value).replace(',', '.')), dv = parseInt(d.value, 10);
        if (!(pv > 0) || !(dv > 0)) { out.textContent = 'Preencha a porcentagem e a duração.'; return; }
        var n = names[u.value] || ['', ''];
        var pTxt = String(Math.round(pv * 100) / 100).replace('.', ',');
        out.textContent = 'Clínicas marcadas como piloto a partir de agora terão ' + pTxt + '% de desconto por ' + dv + ' ' + (dv === 1 ? n[0] : n[1]) + '.';
    }
    [p, d, u].forEach(function (el) { el.addEventListener('input', upd); el.addEventListener('change', upd); });
    upd();
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
