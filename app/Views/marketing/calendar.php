<?php
/** @var list<array<string,mixed>> $rows */
/** @var list<array<string,mixed>> $users */
/** @var string $month */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Agenda de Marketing';

$rows = $rows ?? [];
$users = $users ?? [];
$month = $month ?? (new \DateTimeImmutable('first day of this month'))->format('Y-m-01');

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$monthDt = \DateTimeImmutable::createFromFormat('Y-m-d', $month);
if ($monthDt === false) {
    $monthDt = new \DateTimeImmutable('first day of this month');
}

$first = $monthDt->modify('first day of this month');
$last = $monthDt->modify('last day of this month');

$prev = $first->modify('-1 month')->format('Y-m-01');
$next = $first->modify('+1 month')->format('Y-m-01');

$byDay = [];
foreach ($rows as $r) {
    $d = (string)($r['entry_date'] ?? '');
    if ($d === '') continue;
    if (!isset($byDay[$d])) $byDay[$d] = [];
    $byDay[$d][] = $r;
}

$startDow = (int)$first->format('N'); // 1..7
$daysInMonth = (int)$last->format('j');

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div>
        <div class="lc-badge lc-badge--primary">Agenda de Marketing</div>
        <div class="lc-muted" style="margin-top:6px;">
            <?= htmlspecialchars($first->format('m/Y'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/marketing/calendar?month=<?= urlencode($prev) ?>">Mês anterior</a>
        <a class="lc-btn lc-btn--secondary" href="/marketing/calendar?month=<?= urlencode($next) ?>">Próximo mês</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="lc-alert lc-alert--success" style="margin-bottom:12px;">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Novo item</div>
    <div class="lc-card__body">
        <form method="post" action="/marketing/calendar/create" class="lc-form lc-grid lc-gap-grid" style="grid-template-columns: 160px 1fr 160px 160px 1fr 140px; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="month" value="<?= htmlspecialchars((string)$month, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Dia</label>
                <input class="lc-input" type="date" name="entry_date" value="<?= htmlspecialchars($first->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Título</label>
                <input class="lc-input" type="text" name="title" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="content_type">
                    <option value="post">Post</option>
                    <option value="story">Story</option>
                    <option value="reel">Reel</option>
                    <option value="video">Vídeo</option>
                    <option value="email">Email</option>
                    <option value="blog">Blog</option>
                    <option value="ad">Anúncio</option>
                    <option value="other">Outro</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="planned">Planejado</option>
                    <option value="produced">Produzido</option>
                    <option value="posted">Postado</option>
                    <option value="cancelled">Cancelado</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Responsável (opcional)</label>
                <select class="lc-select" name="assigned_user_id">
                    <option value="">(opcional)</option>
                    <?php foreach ($users as $u): ?>
                        <?php $uid = (int)($u['id'] ?? 0); if ($uid <= 0) continue; ?>
                        <?php
                            $nm = trim((string)($u['name'] ?? ''));
                            $em = trim((string)($u['email'] ?? ''));
                            $lbl = $nm;
                            if ($em !== '') {
                                $lbl = $lbl !== '' ? ($lbl . ' (' . $em . ')') : $em;
                            }
                        ?>
                        <option value="<?= $uid ?>"><?= htmlspecialchars($lbl !== '' ? $lbl : ('Usuário #' . $uid), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="lc-btn lc-btn--secondary" type="submit">Criar</button>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Notas (opcional)</label>
                <textarea class="lc-input" name="notes" rows="2"></textarea>
            </div>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Mês</div>
    <div class="lc-card__body">
        <div class="lc-grid lc-gap-grid" style="grid-template-columns: repeat(7, 1fr);">
            <?php foreach (['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'] as $wd): ?>
                <div class="lc-muted" style="font-weight:600; padding:6px 8px;"><?= htmlspecialchars($wd, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>

            <?php
                $cell = 1;
                for ($i = 1; $i < $startDow; $i++) {
                    $cell++;
                    echo '<div class="lc-card" style="min-height:120px; padding:8px; opacity:.35;"></div>';
                }

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $d = $first->setDate((int)$first->format('Y'), (int)$first->format('m'), $day)->format('Y-m-d');
                    $items = $byDay[$d] ?? [];
                    $has = is_array($items) && $items !== [];

                    echo '<div class="lc-card" style="min-height:120px; padding:8px; border: 1px solid var(--lc-border, #e6e6e6);">';
                    echo '<div class="lc-flex lc-flex--between" style="margin-bottom:6px;">';
                    echo '<div style="font-weight:700;">' . $day . '</div>';
                    echo '<div class="lc-muted" style="font-size:12px;">' . ($has ? count($items) . ' item(s)' : '') . '</div>';
                    echo '</div>';

                    if (!$has) {
                        echo '<div class="lc-muted" style="font-size:12px;">(vazio)</div>';
                    } else {
                        echo '<div class="lc-flex lc-gap-sm lc-flex--wrap">';
                        foreach ($items as $it) {
                            $id = (int)($it['id'] ?? 0);
                            $st = (string)($it['status'] ?? 'planned');
                            $ttl = trim((string)($it['title'] ?? ''));
                            if ($ttl === '') $ttl = 'Item #' . $id;

                            $badge = 'lc-badge lc-badge--secondary';
                            if ($st === 'posted') $badge = 'lc-badge lc-badge--success';
                            if ($st === 'cancelled') $badge = 'lc-badge lc-badge--danger';

                            echo '<a class="' . $badge . '" style="text-decoration:none;" href="/marketing/calendar/edit?id=' . $id . '">' . htmlspecialchars($ttl, ENT_QUOTES, 'UTF-8') . '</a>';
                        }
                        echo '</div>';
                    }

                    echo '</div>';
                    $cell++;
                }

                while ((($cell - 1) % 7) !== 0) {
                    $cell++;
                    echo '<div class="lc-card" style="min-height:120px; padding:8px; opacity:.35;"></div>';
                }
            ?>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
