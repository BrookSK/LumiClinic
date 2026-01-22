<?php
$title = 'Novo paciente';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$professionals = $professionals ?? [];
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Cadastro</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/patients/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" required />

        <div class="lc-grid">
            <div>
                <label class="lc-label">E-mail</label>
                <input class="lc-input" type="email" name="email" />
            </div>
            <div>
                <label class="lc-label">Telefone</label>
                <input class="lc-input" type="text" name="phone" />
            </div>
        </div>

        <div class="lc-grid">
            <div>
                <label class="lc-label">Data de nascimento</label>
                <input class="lc-input" type="date" name="birth_date" />
            </div>
            <div>
                <label class="lc-label">Sexo</label>
                <select class="lc-select" name="sex">
                    <option value="">Selecione</option>
                    <option value="female">Feminino</option>
                    <option value="male">Masculino</option>
                    <option value="other">Outro</option>
                </select>
            </div>
        </div>

        <label class="lc-label">CPF (armazenado criptografado)</label>
        <input class="lc-input" type="text" name="cpf" />

        <label class="lc-label">Profissional de referência</label>
        <select class="lc-select" name="reference_professional_id">
            <option value="">Nenhum</option>
            <?php foreach ($professionals as $pr): ?>
                <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars((string)$pr['name'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <label class="lc-label">Endereço</label>
        <textarea class="lc-input" name="address" rows="3"></textarea>

        <label class="lc-label">Observações</label>
        <textarea class="lc-input" name="notes" rows="4"></textarea>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
            <a class="lc-btn lc-btn--secondary" href="/patients">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
