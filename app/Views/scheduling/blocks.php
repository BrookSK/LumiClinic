<?php
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>>|null $blocks */
/** @var string|null $from */
/** @var string|null $to */
/** @var int|null $filter_professional_id */
$error = $error ?? null;
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Bloqueios de Agenda';
$blocks = $blocks ?? [];
$from = (string)($from ?? date('Y-m-d'));
$to = (string)($to ?? date('Y-m-d', strtotime('+30 days')));
$filterProfessionalId = (int)($filter_professional_id ?? 0);
$can = function (string $pc): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $p = $_SESSION['permissions'] ?? [];
    if (!is_array($p)) return false;
    if (isset($p['allow'],$p['deny'])&&is_array($p['allow'])&&is_array($p['deny'])) {
        if (in_array($pc,$p['deny'],true)) return false;
        return in_array($pc,$p['allow'],true);
    }
    return in_array($pc,$p,true);
};
$profMap = [];
foreach ($professionals as $p) $profMap[(int)$p['id']] = (string)($p['name'] ?? '');
$typeLabel = ['manual'=>'Manual','holiday'=>'Feriado','maintenance'=>'Manutenção'];
ob_start();
?>

<a href="/clinic" style="display:inline-flex;align-items:center;gap:6px;color:rgba(31,41,55,.60);font-weight:650;font-size:13px;text-decoration:none;margin-bottom:16px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
    Voltar
</a>

<div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);margin-bottom:6px;">Bloqueios de agenda</div>
<div style="font-size:13px;color:rgba(31,41,55,.50);margin-bottom:18px;max-width:600px;line-height:1.5;">
    Bloqueie períodos na agenda para férias, manutenção ou qualquer motivo. Pode ser para a clínica inteira ou para um profissional específico.
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($can('blocks.manage')): ?>
<div id="addBlockForm" style="display:none;margin-bottom:16px;">
    <div style="padding:18px;border-radius:14px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.08);">
        <div style="font-weight:750;font-size:14px;margin-bottom:12px;">Novo bloqueio</div>
        <form method="post" action="/blocks/create">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;align-items:end;">
                <div class="lc-field"><label class="lc-label">Quem</label>
                    <select class="lc-select" name="professional_id"><option value="0">Clínica inteira</option>
                    <?php foreach ($professionals as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                    </select></div>
                <div class="lc-field"><label class="lc-label">Início</label><input class="lc-input" type="datetime-local" name="start_at" required /></div>
                <div class="lc-field"><label class="lc-label">Fim</label><input class="lc-input" type="datetime-local" name="end_at" required /></div>
                <div class="lc-field"><label class="lc-label">Tipo</label>
                    <select class="lc-select" name="type"><option value="manual">Manual</option><option value="holiday">Feriado</option><option value="maintenance">Manutenção</option></select></div>
            </div>
            <div class="lc-field" style="margin-top:4px;"><label class="lc-label">Motivo</label><input class="lc-input" type="text" name="reason" required placeholder="Ex: Férias do Dr. João..." /></div>
            <div style="margin-top:12px;"><button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Criar bloqueio</button></div>
        </form>
    </div>
</div>
<div style="margin-bottom:16px;"><button type="button" class="lc-btn lc-btn--primary lc-btn--sm" onclick="var f=document.getElementById('addBlockForm');f.style.display=f.style.display==='none'?'block':'none';">+ Novo bloqueio</button></div>
<?php endif; ?>

<!-- Filtros -->
<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);margin-bottom:16px;">
    <form method="get" action="/blocks" style="display:grid;grid-template-columns:160px 160px 200px auto;gap:12px;align-items:end;">
        <div class="lc-field"><label class="lc-label">De</label><input class="lc-input" type="date" name="from" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>" /></div>
        <div class="lc-field"><label class="lc-label">Até</label><input class="lc-input" type="date" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" /></div>
        <div class="lc-field"><label class="lc-label">Profissional</label>
            <select class="lc-select" name="professional_id"><option value="0" <?= $filterProfessionalId === 0 ? 'selected' : '' ?>>Todos</option>
            <?php foreach ($professionals as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $filterProfessionalId ? 'selected' : '' ?>><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
            </select></div>
        <button class="lc-btn lc-btn--secondary lc-btn--sm" type="submit">Filtrar</button>
    </form>
</div>

<?php if (!is_array($blocks) || $blocks === []): ?>
    <div style="text-align:center;padding:40px 20px;color:rgba(31,41,55,.45);">
        <div style="font-size:32px;margin-bottom:8px;">✅</div>
        <div style="font-size:14px;">Nenhum bloqueio no período.</div>
    </div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($blocks as $b): ?>
        <?php
        $pid = isset($b['professional_id']) ? (int)$b['professional_id'] : 0;
        $pName = $pid > 0 ? ($profMap[$pid] ?? '#'.$pid) : 'Clínica inteira';
        $type = (string)($b['type'] ?? 'manual');
        $tLbl = $typeLabel[$type] ?? $type;
        $start = (string)($b['start_at'] ?? '');
        $end = (string)($b['end_at'] ?? '');
        $sFmt = $start !== '' ? date('d/m/Y H:i', strtotime($start)) : '—';
        $eFmt = $end !== '' ? date('d/m/Y H:i', strtotime($end)) : '—';
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 2px 8px rgba(17,24,39,.04);flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <span style="font-weight:700;font-size:13px;color:rgba(31,41,55,.90);"><?= htmlspecialchars($pName, ENT_QUOTES, 'UTF-8') ?></span>
                <span style="font-size:12px;color:rgba(31,41,55,.55);"><?= htmlspecialchars($sFmt, ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars($eFmt, ENT_QUOTES, 'UTF-8') ?></span>
                <span style="display:inline-flex;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;background:rgba(107,114,128,.10);color:#6b7280;border:1px solid rgba(107,114,128,.18);"><?= htmlspecialchars($tLbl, ENT_QUOTES, 'UTF-8') ?></span>
                <span style="font-size:12px;color:rgba(31,41,55,.50);"><?= htmlspecialchars((string)($b['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
