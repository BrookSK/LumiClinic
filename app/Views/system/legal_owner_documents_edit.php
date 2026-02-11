<?php
$title = 'Admin do Sistema';
$csrf = $_SESSION['_csrf'] ?? '';
$doc = $doc ?? null;
$clinics = $clinics ?? [];
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Termo do Owner</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/sys/legal-owner-documents">Voltar</a>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Cadastro</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger" style="margin-top:10px;">
            <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success" style="margin-top:10px;">
            <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/sys/legal-owner-documents/save">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($doc['id'] ?? 0) ?>" />

        <label class="lc-label">Aplicação</label>
        <select class="lc-select" name="clinic_id">
            <?php $dcid = $doc['clinic_id'] ?? null; ?>
            <option value="0" <?= $dcid === null ? 'selected' : '' ?>>Global (todas as clínicas)</option>
            <?php foreach ($clinics as $c): ?>
                <?php $cid = (int)($c['id'] ?? 0); ?>
                <option value="<?= $cid ?>" <?= ($dcid !== null && (int)$dcid === $cid) ? 'selected' : '' ?>>Clínica #<?= $cid ?> - <?= htmlspecialchars((string)($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Título</label>
        <input class="lc-input" type="text" name="title" value="<?= htmlspecialchars((string)($doc['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <label class="lc-label">Texto</label>
        <textarea class="lc-input" name="body" rows="12" required><?= htmlspecialchars((string)($doc['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <label class="lc-label">Obrigatório</label>
        <select class="lc-input" name="is_required">
            <?php $req = (int)($doc['is_required'] ?? 0) === 1; ?>
            <option value="0" <?= !$req ? 'selected' : '' ?>>Não</option>
            <option value="1" <?= $req ? 'selected' : '' ?>>Sim</option>
        </select>

        <label class="lc-label">Status</label>
        <select class="lc-input" name="status">
            <?php $st = (string)($doc['status'] ?? 'active'); ?>
            <option value="active" <?= $st === 'active' ? 'selected' : '' ?>>Ativo</option>
            <option value="disabled" <?= $st === 'disabled' ? 'selected' : '' ?>>Inativo</option>
        </select>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/sys/legal-owner-documents">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
