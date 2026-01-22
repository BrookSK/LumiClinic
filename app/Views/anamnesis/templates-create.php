<?php
$title = 'Novo template de anamnese';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
ob_start();
?>
<div class="lc-card">
    <div class="lc-card__title">Novo template</div>

    <?php if ($error): ?>
        <div class="lc-alert lc-alert--danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="lc-card__body">
        Cole abaixo um JSON de campos (lista). Exemplo:
        <pre style="white-space:pre-wrap; color:rgba(244,236,212,0.75);">[
  {"field_key":"allergies","label":"Alergias","field_type":"textarea"},
  {"field_key":"smoker","label":"Fumante","field_type":"checkbox"},
  {"field_key":"blood_type","label":"Tipo sanguíneo","field_type":"select","options":["A+","A-","B+","B-","O+","O-"]}
]</pre>
    </div>

    <form method="post" class="lc-form" action="/anamnesis/templates/create">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

        <label class="lc-label">Nome</label>
        <input class="lc-input" type="text" name="name" required />

        <label class="lc-label">Campos (JSON)</label>
        <textarea class="lc-input" name="fields_json" rows="10"></textarea>

        <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap;">
            <button class="lc-btn lc-btn--primary" type="submit">Criar</button>
            <a class="lc-btn lc-btn--secondary" href="/anamnesis/templates">Voltar</a>
        </div>
    </form>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
