<?php
$title = 'Relatório de Follow-up';
$patients = $patients ?? [];
$days = isset($days) ? (int)$days : 180;
$stats = $stats ?? ['total' => 0, 'with_phone' => 0, 'with_wa' => 0, 'never_came' => 0];
ob_start();
?>

<style>
.fu-header { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.fu-header__title { font-weight:850; font-size:20px; color:rgba(31,41,55,.94); }
.fu-header__sub { font-size:13px; color:rgba(31,41,55,.45); margin-top:2px; }

.fu-stats { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:20px; }
@media(max-width:700px){ .fu-stats{grid-template-columns:1fr 1fr;} }

.fu-stat {
    padding:18px 16px;
    border-radius:14px;
    text-align:center;
    border:1px solid rgba(17,24,39,.06);
    box-shadow:0 2px 10px rgba(17,24,39,.03);
}
.fu-stat__value { font-size:28px; font-weight:900; line-height:1; margin-bottom:4px; }
.fu-stat__label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.4px; }

.fu-stat--total { background:linear-gradient(135deg,rgba(238,184,16,.08),rgba(238,184,16,.02)); border-color:rgba(238,184,16,.15); }
.fu-stat--total .fu-stat__value { color:#815901; }
.fu-stat--total .fu-stat__label { color:rgba(129,89,1,.55); }

.fu-stat--never { background:linear-gradient(135deg,rgba(239,68,68,.06),rgba(239,68,68,.02)); border-color:rgba(239,68,68,.12); }
.fu-stat--never .fu-stat__value { color:#dc2626; }
.fu-stat--never .fu-stat__label { color:rgba(239,68,68,.6); }

.fu-stat--wa { background:linear-gradient(135deg,rgba(16,185,129,.06),rgba(16,185,129,.02)); border-color:rgba(16,185,129,.12); }
.fu-stat--wa .fu-stat__value { color:#059669; }
.fu-stat--wa .fu-stat__label { color:rgba(16,185,129,.6); }

.fu-stat--phone { background:linear-gradient(135deg,rgba(99,102,241,.06),rgba(99,102,241,.02)); border-color:rgba(99,102,241,.12); }
.fu-stat--phone .fu-stat__value { color:#4338ca; }
.fu-stat--phone .fu-stat__label { color:rgba(99,102,241,.6); }

.fu-filter {
    display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap;
    padding:16px 18px; border-radius:14px;
    background:var(--lc-surface); border:1px solid rgba(17,24,39,.06);
    box-shadow:0 2px 10px rgba(17,24,39,.03);
    margin-bottom:18px;
}

.fu-table-wrap {
    padding:0; border-radius:14px;
    background:var(--lc-surface); border:1px solid rgba(17,24,39,.06);
    box-shadow:0 2px 10px rgba(17,24,39,.03);
    overflow:hidden;
}
.fu-table-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 18px; border-bottom:1px solid rgba(17,24,39,.06);
}
.fu-table-header__title { font-weight:800; font-size:14px; color:rgba(31,41,55,.85); }

.fu-tag {
    display:inline-flex; align-items:center; padding:3px 10px;
    border-radius:8px; font-size:11px; font-weight:700; white-space:nowrap;
}
.fu-tag--critical { background:rgba(239,68,68,.08); color:#dc2626; }
.fu-tag--warning  { background:rgba(245,158,11,.10); color:#92400e; }
.fu-tag--info     { background:rgba(99,102,241,.08); color:#4338ca; }
.fu-tag--ok       { background:rgba(16,185,129,.08); color:#059669; }
.fu-tag--muted    { background:rgba(107,114,128,.08); color:#6b7280; }

.fu-empty {
    text-align:center; padding:40px 20px; color:rgba(31,41,55,.35); font-size:14px;
}
</style>

<!-- Header -->
<div class="fu-header">
    <div>
        <div class="fu-header__title">Relatório de Follow-up</div>
        <div class="fu-header__sub">Pacientes que não retornaram e precisam de contato.</div>
    </div>
    <div class="lc-flex lc-gap-sm">
        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/reports/follow-up/export.pdf?days=<?= (int)$days ?>" target="_blank">📄 Exportar PDF</a>
    </div>
</div>

<!-- Stats -->
<div class="fu-stats">
    <div class="fu-stat fu-stat--total">
        <div class="fu-stat__value"><?= (int)$stats['total'] ?></div>
        <div class="fu-stat__label">Total de pacientes</div>
    </div>
    <div class="fu-stat fu-stat--never">
        <div class="fu-stat__value"><?= (int)$stats['never_came'] ?></div>
        <div class="fu-stat__label">Nunca vieram</div>
    </div>
    <div class="fu-stat fu-stat--wa">
        <div class="fu-stat__value"><?= (int)$stats['with_wa'] ?></div>
        <div class="fu-stat__label">Com WhatsApp</div>
    </div>
    <div class="fu-stat fu-stat--phone">
        <div class="fu-stat__value"><?= (int)$stats['with_phone'] ?></div>
        <div class="fu-stat__label">Com telefone</div>
    </div>
</div>

<!-- Filter -->
<div class="fu-filter">
    <form method="get" action="/reports/follow-up" class="lc-flex lc-gap-sm lc-flex--wrap" style="align-items:flex-end;width:100%;">
        <div class="lc-field" style="min-width:180px;">
            <label class="lc-label">Sem consulta há mais de</label>
            <select class="lc-select" name="days">
                <?php foreach ([60, 90, 120, 180, 270, 365, 540, 730] as $d): ?>
                    <option value="<?= $d ?>" <?= $d === $days ? 'selected' : '' ?>><?= $d ?> dias</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Filtrar</button>
    </form>
</div>

<!-- Table -->
<div class="fu-table-wrap">
    <div class="fu-table-header">
        <div class="fu-table-header__title">Pacientes sem retorno há mais de <?= (int)$days ?> dias</div>
        <span class="fu-tag fu-tag--muted"><?= count($patients) ?> resultado(s)</span>
    </div>

    <?php if (empty($patients)): ?>
        <div class="fu-empty">
            <div style="font-size:32px;margin-bottom:8px;">✅</div>
            Nenhum paciente encontrado para este critério. Todos em dia!
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="lc-table" style="margin:0;">
                <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th>Paciente</th>
                    <th>Última consulta</th>
                    <th>Dias sem retorno</th>
                    <th>Telefone</th>
                    <th>WhatsApp</th>
                    <th style="width:80px"></th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 0; foreach ($patients as $p): $i++; ?>
                    <?php
                        $lastAt = trim((string)($p['last_appointment_at'] ?? ''));
                        $phone = trim((string)($p['phone'] ?? ''));
                        $waOptIn = (int)($p['whatsapp_opt_in'] ?? 0);

                        $lastFormatted = '<span class="fu-tag fu-tag--critical">Nunca</span>';
                        $daysSince = '—';
                        $urgencyClass = 'fu-tag--critical';

                        if ($lastAt !== '') {
                            $ts = strtotime($lastAt);
                            $lastFormatted = date('d/m/Y', $ts);
                            $diff = (int)round((time() - $ts) / 86400);
                            $daysSince = (string)$diff;
                            if ($diff > 365) { $urgencyClass = 'fu-tag--critical'; }
                            elseif ($diff > 180) { $urgencyClass = 'fu-tag--warning'; }
                            else { $urgencyClass = 'fu-tag--info'; }
                        }
                    ?>
                    <tr>
                        <td style="color:rgba(31,41,55,.35);font-size:12px;"><?= $i ?></td>
                        <td><strong><?= htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= $lastFormatted ?></td>
                        <td><span class="fu-tag <?= $urgencyClass ?>"><?= $daysSince ?> dias</span></td>
                        <td><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($waOptIn): ?>
                                <span class="fu-tag fu-tag--ok">Sim</span>
                            <?php else: ?>
                                <span class="fu-tag fu-tag--muted">Não</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/patients/view?id=<?= (int)$p['id'] ?>">Ver</a>
                        </td>
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
