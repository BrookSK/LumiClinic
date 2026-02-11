<?php
$title = 'Editar documento legal (Equipe)';
$csrf = $_SESSION['_csrf'] ?? '';
$doc = $doc ?? null;
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Documento legal (Sistema)</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="lc-alert lc-alert--success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/settings/legal-documents/save">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($doc['id'] ?? 0) ?>" />

        <label class="lc-label">Papel alvo (opcional)</label>
        <input class="lc-input" type="text" name="target_role_code" value="<?= htmlspecialchars((string)($doc['target_role_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: professional, reception, admin, owner" />

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
            <a class="lc-btn lc-btn--secondary" href="/settings/legal-documents">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
