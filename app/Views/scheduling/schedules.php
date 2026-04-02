<?php
/** @var list<array<string,mixed>> $professionals */
/** @var int $professional_id */
/** @var list<array<string,mixed>> $items */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Regras de Agenda';

$weekdayNames = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',0=>'Domingo'];
$weekdayShort = [1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',0=>'Dom'];

$can = function (string $permissionCode): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) return false;
    if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
        if (in_array($permissionCode, $permissions['deny'], true)) return false;
        return in_array($permissionCode, $permissions['allow'], true);
    }
    return in_array($permissionCode, $permissions, true);
};

// Agrupar regras por dia
$byDay = [];
foreach ($items as $it) {
    $d = (int)$it['weekday'];
    $byDay[$d][] = $it;
}

// Nome do profissional selecionado
$profName = '';
foreach ($professionals as $p) {
    if ((int)$p['id'] === (int)$professional_id) {
        $profName = (string)$p['name'];
        break;
    }
}

ob_start();
?>

<style>
.sr-back{display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px}
.sr-back:hover{color:rgba(129,89,1,1)}
.sr-prof-select{padding:16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:18px}
.sr-day{padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:10px}
.sr-day__head{display:flex;align-items:center;justify-content:space-between;gap:10px}
.sr-day__name{font-weight:750;font-size:14px;color:rgba(31,41,55,.90)}
.sr-day__slots{display:flex;gap:8px;flex-wrap:wrap}
.sr-slot{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.08);background:rgba(238,184,16,.06);font-size:13px;font-weight:600;color:rgba(31,41,55,.80)}
.sr-slot__interval{font-size:11px;color:rgba(31,41,55,.45);font-weight:400}
.sr-slot form{margin:0;display:inline}
.sr-slot button.sr-del{background:none;border:none;color:rgba(185,28,28,.50);cursor:pointer;font-size:14px;padding:0 2px}
.sr-slot button.sr-del:hover{color:rgba(185,28,28,1)}
.sr-empty{font-size:13px;color:rgba(31,41,55,.40);font-style:italic}
</style>

<a href="/clinic" class="sr-back">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Regras de agenda</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:600px;line-height:1.5;">
    Defina os dias e horários em que cada profissional atende. Essas regras determinam os slots disponíveis na agenda.
</div>

<!-- Seletor de profissional -->
<div class="sr-prof-select">
    <form method="get" action="/schedule-rules" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <div class="lc-field" style="min-width:280px;flex:1;">
            <label class="lc-label">Selecione o profissional</label>
            <select class="lc-select" name="professional_id" onchange="this.form.submit()">
                <option value="0">Escolha um profissional...</option>
                <?php foreach ($professionals as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$professional_id) ? 'selected' : '' ?>><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ((int)$professional_id > 0): ?>

<!-- Título do profissional -->
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
    <div style="font-weight:750;font-size:16px;color:rgba(31,41,55,.90);">Horários de <?= htmlspecialchars($profName, ENT_QUOTES, 'UTF-8') ?></div>
</div>

<!-- Adicionar regra -->
<?php if ($can('schedule_rules.manage')): ?>
<div id="addRuleForm" style="display:none;margin-bottom:16px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <div style="font-weight:750;font-size:14px;margin-bottom:12px;">Adicionar horário</div>
        <form method="post" action="/schedule-rules/create">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
            <div style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
                <div class="lc-field" style="min-width:160px;">
                    <label class="lc-label">Dia</label>
                    <select class="lc-select" name="weekday" id="ruleWeekdaySelect">
                        <?php foreach ($weekdayNames as $k => $v): ?>
                            <option value="<?= $k ?>"><?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-field" style="min-width:110px;">
                    <label class="lc-label">Início</label>
                    <input class="lc-input" type="time" name="start_time" value="08:00" required />
                </div>
                <div class="lc-field" style="min-width:110px;">
                    <label class="lc-label">Fim</label>
                    <input class="lc-input" type="time" name="end_time" value="18:00" required />
                </div>
                <div class="lc-field" style="min-width:100px;">
                    <label class="lc-label">Intervalo (min)</label>
                    <input class="lc-input" type="number" name="interval_minutes" min="0" step="5" value="30" placeholder="30" />
                </div>
                <div style="padding-bottom:1px;">
                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
                </div>
            </div>
            <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:6px;">Intervalo = duração de cada slot na agenda (ex: 30 min = consultas de 30 em 30 minutos).</div>
        </form>
    </div>
</div>

<div style="margin-bottom:16px;">
    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('addRuleForm');f.style.display=f.style.display==='none'?'block':'none';">+ Adicionar horário</button>
</div>
<?php endif; ?>

<!-- Lista por dia -->
<?php foreach ($weekdayNames as $wd => $wdName): ?>
    <?php
    $dayItems = $byDay[$wd] ?? [];
    $isActive = $dayItems !== [];
    ?>
    <div class="sr-day" style="<?= $isActive ? '' : 'opacity:.65;' ?>">
        <div class="sr-day__head">
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="sr-day__name"><?= htmlspecialchars($wdName, ENT_QUOTES, 'UTF-8') ?></span>
                <span style="display:inline-flex;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;background:<?= $isActive ? 'rgba(22,163,74,.12)' : 'rgba(185,28,28,.08)' ?>;color:<?= $isActive ? '#16a34a' : '#b91c1c' ?>;border:1px solid <?= $isActive ? 'rgba(22,163,74,.22)' : 'rgba(185,28,28,.16)' ?>;"><?= $isActive ? 'Atende' : 'Não atende' ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <?php if ($isActive): ?>
                    <div class="sr-day__slots">
                        <?php foreach ($dayItems as $it): ?>
                            <span class="sr-slot">
                                <?= htmlspecialchars(substr((string)$it['start_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(substr((string)$it['end_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($it['interval_minutes'] !== null): ?>
                                    <span class="sr-slot__interval">(<?= (int)$it['interval_minutes'] ?>min)</span>
                                <?php endif; ?>
                                <?php if ($can('schedule_rules.manage')): ?>
                                    <form method="post" action="/schedule-rules/delete" onsubmit="return confirm('Excluir?');">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                        <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
                                        <button type="submit" class="sr-del" title="Excluir">✕</button>
                                    </form>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($can('schedule_rules.manage')): ?>
                <div style="display:flex;gap:4px;">
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="openRuleFor(<?= $wd ?>)" title="Adicionar horário" style="padding:6px 10px;font-size:12px;">
                        ✏️
                    </button>
                    <?php if ($isActive): ?>
                        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="markRuleClosed(<?= $wd ?>, '<?= htmlspecialchars($wdName, ENT_QUOTES, 'UTF-8') ?>')" title="Marcar como não atende" style="padding:6px 10px;font-size:12px;color:rgba(185,28,28,.60);">
                            🚫
                        </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php if ($can('schedule_rules.manage')): ?>
<form id="markRuleClosedForm" method="post" action="/schedule-rules/delete-day" style="display:none;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="professional_id" value="<?= (int)$professional_id ?>" />
    <input type="hidden" name="weekday" id="markRuleClosedWeekday" value="" />
</form>
<?php endif; ?>

<script>
function openRuleFor(weekday) {
    var f = document.getElementById('addRuleForm');
    if (!f) return;
    f.style.display = 'block';
    var sel = document.getElementById('ruleWeekdaySelect');
    if (sel) sel.value = weekday;
    f.scrollIntoView({behavior: 'smooth', block: 'start'});
}
function markRuleClosed(weekday, name) {
    if (!confirm('Remover todos os horários de ' + name + '? O dia ficará como "Não atende".')) return;
    var f = document.getElementById('markRuleClosedForm');
    var w = document.getElementById('markRuleClosedWeekday');
    if (!f || !w) return;
    w.value = weekday;
    f.submit();
}
</script>

<?php else: ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">👆</div>
        <div style="font-size:14px;">Selecione um profissional acima para ver e editar os horários dele.</div>
    </div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
