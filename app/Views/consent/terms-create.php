<?php
$title = 'Novo termo';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$procedureTypes = $procedure_types ?? [];
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Novo termo</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="lc-form" action="/consent-terms/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Procedimento</label>
        <select class="lc-select" name="procedure_type" id="consentProcedureTypeSelect" required>
            <option value="">Selecione</option>
            <?php foreach ($procedureTypes as $pt): ?>
                <?php $ptv = trim((string)$pt); ?>
                <?php if ($ptv === '') { continue; } ?>
                <option value="<?= htmlspecialchars($ptv, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($ptv, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
            <option value="__custom__">Outro (digitar)</option>
        </select>

        <div id="consentProcedureTypeCustomWrap" style="display:none; margin-top:10px;">
            <label class="lc-label">Outro procedimento</label>
            <input class="lc-input" type="text" id="consentProcedureTypeCustom" placeholder="Digite o procedimento" />
        </div>

        <label class="lc-label">Título</label>
        <input class="lc-input" type="text" name="title" required />

        <label class="lc-label">Corpo do termo</label>
        <textarea class="lc-input" name="body" rows="12" required></textarea>

        <div class="lc-flex lc-gap-sm lc-flex--wrap" style="margin-top:14px;">
            <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
            <a class="lc-btn lc-btn--secondary" href="/consent-terms">Voltar</a>
        </div>
    </form>
</div>

<script>
    (function () {
        var sel = document.getElementById('consentProcedureTypeSelect');
        var wrap = document.getElementById('consentProcedureTypeCustomWrap');
        var custom = document.getElementById('consentProcedureTypeCustom');
        if (!sel || !wrap || !custom) return;

        function sync() {
            var isCustom = sel.value === '__custom__';
            wrap.style.display = isCustom ? 'block' : 'none';
            if (isCustom) {
                sel.removeAttribute('name');
                custom.setAttribute('name', 'procedure_type');
                custom.setAttribute('required', 'required');
            } else {
                custom.removeAttribute('name');
                custom.removeAttribute('required');
                sel.setAttribute('name', 'procedure_type');
            }
        }

        sel.addEventListener('change', sync);
        sync();
    })();
</script>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
