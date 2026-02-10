<?php
$title = 'Editar paciente';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$patient = $patient ?? null;
$professionals = $professionals ?? [];
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Edição</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/patients/edit">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
        <input type="hidden" name="id" value="<?= (int)($patient['id'] ?? 0) ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" value="<?= htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required />

        <div class="lc-grid">
            <div>
                <label class="lc-label">E-mail</label>
                <input class="lc-input" type="email" name="email" value="<?= htmlspecialchars((string)($patient['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="phone" value="<?= htmlspecialchars((string)($patient['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
        </div>

        <div class="lc-grid">
            <div>
                <label class="lc-label">Data de nascimento</label>
                <input class="lc-input" type="date" name="birth_date" value="<?= htmlspecialchars((string)($patient['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <div>
                <label class="lc-label">Sexo</label>
                <?php $sex = (string)($patient['sex'] ?? ''); ?>
                <select class="lc-select" name="sex">
                    <option value="" <?= $sex === '' ? 'selected' : '' ?>>Selecione</option>
                    <option value="female" <?= $sex === 'female' ? 'selected' : '' ?>>Feminino</option>
                    <option value="male" <?= $sex === 'male' ? 'selected' : '' ?>>Masculino</option>
                    <option value="other" <?= $sex === 'other' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>
        </div>

        <label class="lc-label">CPF</label>
        <input class="lc-input" type="text" name="cpf" value="<?= htmlspecialchars((string)($patient['cpf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Profissional de referência</label>
        <?php $currentRef = (int)($patient['reference_professional_id'] ?? 0); ?>
        <select class="lc-select" name="reference_professional_id">
            <option value="" <?= $currentRef === 0 ? 'selected' : '' ?>>Nenhum</option>
            <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $currentRef ? 'selected' : '' ?>><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Endereço</label>
        <textarea class="lc-input" name="address" rows="3"><?= htmlspecialchars((string)($patient['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <label class="lc-label">Observações</label>
        <textarea class="lc-input" name="notes" rows="4"><?= htmlspecialchars((string)($patient['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        <label class="lc-label">Status</label>
        <?php $status = (string)($patient['status'] ?? 'active'); ?>
        <select class="lc-select" name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
            <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Desativado</option>
        </select>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
            <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)($patient['id'] ?? 0) ?>">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
