<?php
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Financeiro - Editar centro de custo';
$row = $row ?? null;
$error = $error ?? ($_GET['error'] ?? null);
$success = $success ?? ($_GET['success'] ?? null);

ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Editar centro de custo</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Voltar</a>
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

<div class="lc-card">
    <div class="lc-card__header">Cadastro</div>
    <div class="lc-card__body">
        <form method="post" action="/finance/cost-centers/update" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="id" value="<?= (int)($row['id'] ?? 0) ?>" />

            <label class="lc-label">Nome</label>
            <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

            <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                <a class="lc-btn lc-btn--secondary" href="/finance/cost-centers">Voltar</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
