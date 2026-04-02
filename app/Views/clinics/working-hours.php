<?php
$title = 'Horários de Funcionamento';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$items = $items ?? [];

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

$weekdayLabels = [1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',0=>'Domingo'];

$byDay = [];
foreach ($items as $it) {
    $d = (int)$it['weekday'];
    $byDay[$d][] = $it;
}

ob_start();
?>

<style>
.wh-back{display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px}
.wh-back:hover{color:rgba(129,89,1,1)}
.wh-day{padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:10px}
.wh-day--closed{opacity:.65}
.wh-day__head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.wh-day__left{display:flex;align-items:center;gap:10px}
.wh-day__name{font-weight:750;font-size:14px;color:rgba(31,41,55,.90)}
.wh-badge{display:inline-flex;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase}
.wh-badge--open{background:rgba(22,163,74,.12);color:#16a34a;border:1px solid rgba(22,163,74,.22)}
.wh-badge--closed{background:rgba(185,28,28,.08);color:#b91c1c;border:1px solid rgba(185,28,28,.16)}
.wh-day__right{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.wh-slots{display:flex;gap:8px;flex-wrap:wrap}
.wh-slot{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:10px;border:1px solid rgba(17,24,39,.08);background:rgba(238,184,16,.06);font-size:13px;font-weight:600;color:rgba(31,41,55,.80)}
.wh-slot form{margin:0;display:inline}
.wh-slot button.wh-del{background:none;border:none;color:rgba(185,28,28,.50);cursor:pointer;font-size:14px;padding:0 2px}
.wh-slot button.wh-del:hover{color:rgba(185,28,28,1)}
.wh-actions{display:flex;gap:4px}
.wh-btn-icon{padding:6px 10px;font-size:12px;cursor:pointer}
</style>

<a href="/clinic" class="wh-back">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar para clínica
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Horários de funcionamento</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:600px;line-height:1.5;">
    Defina os dias e horários em que a clínica atende. Dias sem horário ficam marcados como "Fechado".
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Formulário adicionar -->
<?php if ($can('clinics.update')): ?>
<div id="addHourForm" style="display:none;margin-bottom:16px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <div style="font-weight:750;font-size:14px;margin-bottom:10px;">Adicionar horário</div>
        <form method="post" action="/clinic/working-hours">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
                <div class="lc-field" style="min-width:160px;">
                    <label class="lc-label">Dia</label>
                    <select class="lc-select" name="weekday" id="whWeekdaySelect" required>
                        <?php foreach ($weekdayLabels as $k => $v): ?>
                            <option value="<?= (int)$k ?>"><?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?></option>
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
                <div style="padding-bottom:1px;">
                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div style="margin-bottom:16px;">
    <button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('addHourForm');f.style.display=f.style.display==='none'?'block':'none';">+ Adicionar horário</button>
</div>
<?php endif; ?>

<!-- Lista por dia -->
<?php foreach ($weekdayLabels as $wd => $wdName): ?>
    <?php
    $dayItems = $byDay[$wd] ?? [];
    $isOpen = $dayItems !== [];
    ?>
    <div class="wh-day <?= $isOpen ? '' : 'wh-day--closed' ?>">
        <div class="wh-day__head">
            <div class="wh-day__left">
                <span class="wh-day__name"><?= htmlspecialchars($wdName, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="wh-badge <?= $isOpen ? 'wh-badge--open' : 'wh-badge--closed' ?>"><?= $isOpen ? 'Aberto' : 'Fechado' ?></span>
            </div>
            <div class="wh-day__right">
                <?php if ($isOpen): ?>
                    <div class="wh-slots">
                        <?php foreach ($dayItems as $it): ?>
                            <span class="wh-slot">
                                <?= htmlspecialchars(substr((string)$it['start_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(substr((string)$it['end_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($can('clinics.update')): ?>
                                    <form method="post" action="/clinic/working-hours/delete">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>" />
                                        <button type="submit" class="wh-del" title="Remover horário" onclick="return confirm('Remover este horário?');">✕</button>
                                    </form>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($can('clinics.update')): ?>
                <div class="wh-actions">
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm wh-btn-icon" onclick="openAddFor(<?= $wd ?>)" title="Adicionar horário para <?= htmlspecialchars($wdName, ENT_QUOTES, 'UTF-8') ?>">
                        ✏️
                    </button>
                    <?php if ($isOpen): ?>
                        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm wh-btn-icon" onclick="markClosed(<?= $wd ?>, '<?= htmlspecialchars($wdName, ENT_QUOTES, 'UTF-8') ?>')" title="Marcar como fechado" style="color:rgba(185,28,28,.60);">
                            🚫
                        </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php if ($can('clinics.update')): ?>
<!-- Form oculto para deletar todos os horários de um dia -->
<form id="markClosedForm" method="post" action="/clinic/working-hours/delete-day" style="display:none;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
    <input type="hidden" name="weekday" id="markClosedWeekday" value="" />
</form>
<?php endif; ?>

<script>
function openAddFor(weekday) {
    var f = document.getElementById('addHourForm');
    if (!f) return;
    f.style.display = 'block';
    var sel = document.getElementById('whWeekdaySelect');
    if (sel) sel.value = weekday;
    f.scrollIntoView({behavior: 'smooth', block: 'start'});
}
function markClosed(weekday, name) {
    if (!confirm('Remover todos os horários de ' + name + '? O dia ficará marcado como fechado.')) return;
    var f = document.getElementById('markClosedForm');
    var w = document.getElementById('markClosedWeekday');
    if (!f || !w) return;
    w.value = weekday;
    f.submit();
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
