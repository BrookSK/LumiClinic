<?php
/** @var array<string,mixed> $row */
/** @var list<array<string,mixed>> $users */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Agenda de Marketing - Editar';

$row = $row ?? null;
$users = $users ?? [];

$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

$id = (int)($row['id'] ?? 0);
$entryDate = (string)($row['entry_date'] ?? '');
$contentType = (string)($row['content_type'] ?? 'post');
$status = (string)($row['status'] ?? 'planned');
$titleValue = (string)($row['title'] ?? '');
$notes = (string)($row['notes'] ?? '');
$assignedUserId = (int)($row['assigned_user_id'] ?? 0);

$month = '';
if ($entryDate !== '' && strlen($entryDate) >= 7) {
    $month = substr($entryDate, 0, 7) . '-01';
}

ob_start();
?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Editar item</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/marketing/calendar<?= $month !== '' ? ('?month=' . urlencode($month)) : '' ?>">Voltar</a>
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
    <div class="lc-card__header">Cadastro</div>
    <div class="lc-card__body">
        <form method="post" action="/marketing/calendar/update" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= $id ?>" />
            <?php if ($month !== ''): ?>
                <input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES, 'UTF-8') ?>" />
            <?php endif; ?>

            <div class="lc-grid lc-gap-grid" style="grid-template-columns: 180px 1fr; align-items:end;">
                <div class="lc-field">
                    <label class="lc-label">Dia</label>
                    <input class="lc-input" type="date" name="entry_date" value="<?= htmlspecialchars($entryDate, ENT_QUOTES, 'UTF-8') ?>" required />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Título</label>
                    <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars($titleValue, ENT_QUOTES, 'UTF-8') ?>" required />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Tipo</label>
                    <select class="lc-select" name="content_type">
                        <?php foreach (['post'=>'Post','story'=>'Story','reel'=>'Reel','video'=>'Vídeo','email'=>'Email','blog'=>'Blog','ad'=>'Anúncio','other'=>'Outro'] as $k=>$lbl): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $contentType === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Status</label>
                    <select class="lc-select" name="status">
                        <?php foreach (['planned'=>'Planejado','produced'=>'Produzido','posted'=>'Postado','cancelled'=>'Cancelado'] as $k=>$lbl): ?>
                            <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $status === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lc-field" style="grid-column: 1 / -1;">
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
                            <option value="<?= $uid ?>" <?= $assignedUserId === $uid ? 'selected' : '' ?>><?= htmlspecialchars($lbl !== '' ? $lbl : ('Usuário #' . $uid), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lc-field" style="grid-column: 1 / -1;">
                    <label class="lc-label">Notas (opcional)</label>
                    <textarea class="lc-input" name="notes" rows="4"><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/marketing/calendar<?= $month !== '' ? ('?month=' . urlencode($month)) : '' ?>">Voltar</a>
            </div>
        </form>

        <form method="post" action="/marketing/calendar/delete" style="margin-top:12px;" onsubmit="return confirm('Excluir item?');">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= $id ?>" />
            <?php if ($month !== ''): ?>
                <input type="hidden" name="month" value="<?= htmlspecialchars($month, ENT_QUOTES, 'UTF-8') ?>" />
            <?php endif; ?>
            <button class="lc-btn lc-btn--danger" type="submit">Excluir</button>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
