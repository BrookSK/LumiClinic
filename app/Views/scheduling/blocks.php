<?php
/** @var list<array<string,mixed>> $professionals */
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Bloqueios';

ob_start();
?>

<div class="lc-card">
    <div class="lc-card__header">Criar bloqueio</div>
    <div class="lc-card__body">
        <form method="post" action="/blocks/create" class="lc-form" style="display:grid; grid-template-columns: 2fr 2fr 2fr 2fr; gap: 12px; align-items:end;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Profissional</label>
                <select class="lc-select" name="professional_id">
                    <option value="0">Clínica inteira</option>
                    <?php foreach ($professionals as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Início</label>
                <input class="lc-input" type="datetime-local" name="start_at" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Fim</label>
                <input class="lc-input" type="datetime-local" name="end_at" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="type">
                    <option value="manual">Manual</option>
                    <option value="holiday">Feriado</option>
                    <option value="maintenance">Manutenção</option>
                </select>
            </div>

            <div class="lc-field" style="grid-column: 1 / -1;">
                <label class="lc-label">Motivo</label>
                <input class="lc-input" type="text" name="reason" />
            </div>

            <div style="grid-column: 1 / -1;">
                <button class="lc-btn" type="submit">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
