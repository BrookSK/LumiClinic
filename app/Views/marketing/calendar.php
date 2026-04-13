<?php
$csrf    = $_SESSION['_csrf'] ?? '';
$title   = 'Agenda de Marketing';
$rows    = $rows ?? [];
$users   = $users ?? [];
$month   = $month ?? (new \DateTimeImmutable('first day of this month'))->format('Y-m-01');
$error   = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$monthDt = \DateTimeImmutable::createFromFormat('Y-m-d', $month);
if ($monthDt === false) $monthDt = new \DateTimeImmutable('first day of this month');

$first = $monthDt->modify('first day of this month');
$last  = $monthDt->modify('last day of this month');
$prev  = $first->modify('-1 month')->format('Y-m-01');
$next  = $first->modify('+1 month')->format('Y-m-01');

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

$byDay = [];
foreach ($rows as $r) {
    $d = (string)($r['entry_date'] ?? '');
    if ($d !== '') $byDay[$d][] = $r;
}

$startDow    = (int)$first->format('N');
$daysInMonth = (int)$last->format('j');
$today       = date('Y-m-d');

$monthNames = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
$monthLabel = ($monthNames[(int)$first->format('n')] ?? '') . ' ' . $first->format('Y');

$statusLabel = ['planned'=>'Planejado','produced'=>'Produzido','posted'=>'Postado','cancelled'=>'Cancelado'];
$statusColor = ['planned'=>'#6b7280','produced'=>'#eeb810','posted'=>'#16a34a','cancelled'=>'#b91c1c'];

$totalItems = count($rows);

ob_start();
?>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Cabeçalho -->
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:20px;"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="lc-muted" style="font-size:13px; margin-top:2px;"><?= $totalItems ?> conteúdo<?= $totalItems !== 1 ? 's' : '' ?> planejado<?= $totalItems !== 1 ? 's' : '' ?></div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/marketing/calendar?month=<?= urlencode($prev) ?>">← Anterior</a>
        <a class="lc-btn lc-btn--secondary" href="/marketing/calendar?month=<?= urlencode($next) ?>">Próximo →</a>
        <?php if ($can('marketing.calendar.manage')): ?>
            <button type="button" class="lc-btn lc-btn--primary" onclick="toggleForm('form-new')">+ Novo conteúdo</button>
        <?php endif; ?>
    </div>
</div>

<!-- Formulário novo (oculto) -->
<?php if ($can('marketing.calendar.manage')): ?>
<div id="form-new" style="display:none; margin-bottom:16px;">
    <div class="lc-card">
        <div class="lc-card__header" style="font-weight:700;">Novo conteúdo</div>
        <div class="lc-card__body">
            <form method="post" action="/marketing/calendar/create" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 2fr 1fr; align-items:end;">
                    <div class="lc-field"><label class="lc-label">Data</label><input class="lc-input" type="date" name="entry_date" value="<?= htmlspecialchars($first->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required /></div>
                    <div class="lc-field"><label class="lc-label">Título</label><input class="lc-input" type="text" name="title" required placeholder="Ex: Post sobre Botox, Reel de resultados..." /></div>
                    <div class="lc-field"><label class="lc-label">Tipo</label>
                        <select class="lc-select" name="content_type">
                            <option value="post">Post</option><option value="story">Story</option><option value="reel">Reel</option>
                            <option value="video">Vídeo</option><option value="email">Email</option><option value="ad">Anúncio</option><option value="other">Outro</option>
                        </select>
                    </div>
                </div>
                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr 80px; align-items:end; margin-top:10px;">
                    <div class="lc-field"><label class="lc-label">Status</label>
                        <select class="lc-select" name="status">
                            <option value="planned">Planejado</option><option value="produced">Produzido</option><option value="posted">Postado</option>
                        </select>
                    </div>
                    <div class="lc-field"><label class="lc-label">Responsável (opcional)</label>
                        <select class="lc-select" name="assigned_user_id">
                            <option value="">(opcional)</option>
                            <?php foreach ($users as $u): ?>
                                <?php $uid = (int)($u['id'] ?? 0); if ($uid <= 0) continue; ?>
                                <option value="<?= $uid ?>"><?= htmlspecialchars(trim((string)($u['name'] ?? $u['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lc-field"><label class="lc-label">Cor</label><input class="lc-input" type="color" name="color" value="#eeb810" /></div>
                </div>
                <div class="lc-field" style="margin-top:10px;"><label class="lc-label">Notas (opcional)</label><textarea class="lc-input" name="notes" rows="2" placeholder="Ideias, referências..."></textarea></div>
                <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
                    <button type="button" class="lc-btn lc-btn--secondary" onclick="toggleForm('form-new')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Calendário -->
<div class="lc-card">
    <div class="lc-card__body" style="padding:12px;">
        <div style="display:grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap:6px;">
            <!-- Cabeçalho dos dias -->
            <?php foreach (['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'] as $wd): ?>
                <div style="text-align:center; font-weight:700; font-size:12px; color:#6b7280; padding:6px 0;"><?= $wd ?></div>
            <?php endforeach; ?>

            <!-- Dias vazios antes -->
            <?php for ($i = 1; $i < $startDow; $i++): ?>
                <div style="min-height:100px; background:rgba(0,0,0,.02); border-radius:8px;"></div>
            <?php endfor; ?>

            <!-- Dias do mês -->
            <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                <?php
                $d = $first->setDate((int)$first->format('Y'), (int)$first->format('m'), $day)->format('Y-m-d');
                $dayItems = $byDay[$d] ?? [];
                $isToday = $d === $today;
                ?>
                <div style="min-height:100px; padding:8px; border-radius:8px; border:<?= $isToday ? '2px solid #eeb810' : '1px solid rgba(0,0,0,.06)' ?>; background:<?= $isToday ? 'rgba(238,184,16,.04)' : 'transparent' ?>;">
                    <div style="font-weight:<?= $isToday ? '800' : '600' ?>; font-size:13px; color:<?= $isToday ? '#815901' : '#374151' ?>; margin-bottom:6px;">
                        <?= $day ?>
                    </div>

                    <?php if (empty($dayItems)): ?>
                        <div class="lc-muted" style="font-size:11px;">&nbsp;</div>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <?php foreach ($dayItems as $it): ?>
                                <?php
                                $id  = (int)($it['id'] ?? 0);
                                $st  = (string)($it['status'] ?? 'planned');
                                $ttl = trim((string)($it['title'] ?? ''));
                                if ($ttl === '') $ttl = 'Item';
                                $color = trim((string)($it['color'] ?? ''));
                                $color = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : ($statusColor[$st] ?? '#6b7280');
                                $hex = ltrim($color, '#');
                                $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
                                $txt = (0.2126*$r + 0.7152*$g + 0.0722*$b) < 140 ? '#fff' : '#111';
                                ?>
                                <?php if ($can('marketing.calendar.manage')): ?>
                                    <a href="/marketing/calendar/edit?id=<?= $id ?>" style="display:block; padding:3px 6px; border-radius:4px; font-size:11px; font-weight:600; text-decoration:none; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; background:<?= $color ?>; color:<?= $txt ?>;">
                                        <?= htmlspecialchars($ttl, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    <span style="display:block; padding:3px 6px; border-radius:4px; font-size:11px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; background:<?= $color ?>; color:<?= $txt ?>;">
                                        <?= htmlspecialchars($ttl, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>

            <!-- Dias vazios depois -->
            <?php
            $totalCells = ($startDow - 1) + $daysInMonth;
            $remaining = (7 - ($totalCells % 7)) % 7;
            for ($i = 0; $i < $remaining; $i++):
            ?>
                <div style="min-height:100px; background:rgba(0,0,0,.02); border-radius:8px;"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
