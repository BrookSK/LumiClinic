<?php
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Orçamentos';

$page       = isset($page) ? (int)$page : 1;
$perPage    = isset($per_page) ? (int)$per_page : 50;
$hasNext    = isset($has_next) ? (bool)$has_next : false;
$patientId  = isset($patient_id) && (int)$patient_id > 0 ? (int)$patient_id : null;
$selectedPatient = isset($selected_patient) && is_array($selected_patient) ? $selected_patient : null;

$can = function (string $p): bool {
    if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) return true;
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) return false;
    if (isset($perms['allow'], $perms['deny'])) {
        if (in_array($p, $perms['deny'], true)) return false;
        return in_array($p, $perms['allow'], true);
    }
    return in_array($p, $perms, true);
};

$budgetStatusLabel = [
    'draft'    => 'Rascunho',
    'sent'     => 'Enviado',
    'approved' => 'Aprovado',
    'standby'  => 'Em espera',
    'rejected' => 'Recusado',
];
$budgetStatusBadge = [
    'draft'    => 'lc-badge--secondary',
    'sent'     => 'lc-badge--primary',
    'approved' => 'lc-badge--success',
    'standby'  => 'lc-badge--secondary',
    'rejected' => 'lc-badge--danger',
];

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div style="font-weight:800; font-size:18px;">Orçamentos</div>
        <?php if ($selectedPatient !== null): ?>
            <div class="lc-muted" style="font-size:13px; margin-top:2px;">
                <?= htmlspecialchars((string)($selectedPatient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                <a class="lc-muted" style="margin-left:8px; font-size:12px;" href="/finance/sales">Ver todos</a>
            </div>
        <?php endif; ?>
    </div>
    <?php if ((!isset($is_professional) || !$is_professional) && $can('finance.sales.create')): ?>
        <button class="lc-btn lc-btn--primary" type="button" onclick="document.getElementById('newBudgetForm').style.display = document.getElementById('newBudgetForm').style.display === 'none' ? 'block' : 'none'">
            + Novo orçamento
        </button>
    <?php endif; ?>
</div>

<!-- Formulário de criação — oculto por padrão, abre ao clicar -->
<?php if ((!isset($is_professional) || !$is_professional) && $can('finance.sales.create')): ?>
<div id="newBudgetForm" style="display:<?= $selectedPatient !== null ? 'block' : 'none' ?>; margin-bottom:16px;">
    <div class="lc-card">
        <div class="lc-card__header">Novo orçamento</div>
        <div class="lc-card__body">
            <form method="post" action="/finance/sales/create" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="origin" value="reception" />
                <input type="hidden" name="desconto" value="0" />

                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
                    <div class="lc-field" style="position:relative;">
                        <label class="lc-label">Paciente</label>
                        <input class="lc-input" type="text" id="sale_patient_search"
                            placeholder="Buscar por nome, e-mail ou telefone"
                            autocomplete="off"
                            value="<?= htmlspecialchars((string)($selectedPatient['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            required />
                        <input type="hidden" name="patient_id" id="sale_patient_id" value="<?= (int)($selectedPatient['id'] ?? 0) ?>" />
                        <div class="lc-autocomplete" id="sale_patient_results" style="display:none;"></div>
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Observações (opcional)</label>
                        <input class="lc-input" type="text" name="notes" placeholder="Ex: Pacote verão, desconto indicação..." />
                    </div>
                </div>

                <div class="lc-flex lc-gap-sm" style="margin-top:12px;">
                    <button class="lc-btn lc-btn--primary" type="submit">Criar orçamento</button>
                    <button type="button" class="lc-btn lc-btn--secondary" onclick="document.getElementById('newBudgetForm').style.display='none'">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    var searchEl = document.getElementById('sale_patient_search');
    var idEl = document.getElementById('sale_patient_id');
    var resultsEl = document.getElementById('sale_patient_results');
    if (!searchEl || !idEl || !resultsEl) return;

    function hide(){ resultsEl.style.display='none'; resultsEl.innerHTML=''; }

    var t = null;
    searchEl.addEventListener('input', function(){
        idEl.value = '';
        var q = (searchEl.value||'').trim();
        hide();
        if (t) clearTimeout(t);
        if (q.length < 2) return;
        t = setTimeout(async function(){
            var res = await fetch('/finance/sales/patients/search-json?q='+encodeURIComponent(q)+'&limit=10',{headers:{'Accept':'application/json'}});
            if (!res.ok) return;
            var data = await res.json();
            var items = (data&&data.items)?data.items:[];
            if (!items.length) { hide(); return; }
            resultsEl.innerHTML = '';
            items.forEach(function(it){
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'lc-autocomplete__item';
                btn.innerHTML = '<div class="lc-autocomplete__name"></div><div class="lc-autocomplete__meta"></div>';
                btn.querySelector('.lc-autocomplete__name').textContent = it.name||'';
                btn.querySelector('.lc-autocomplete__meta').textContent = [it.phone,it.email].filter(Boolean).join(' · ');
                btn.addEventListener('click', function(){
                    searchEl.value = it.name||'';
                    idEl.value = String(it.id||'');
                    hide();
                });
                resultsEl.appendChild(btn);
            });
            resultsEl.style.display = 'block';
        }, 250);
    });
    searchEl.addEventListener('blur', function(){ setTimeout(hide, 150); });

    var form = searchEl.closest('form');
    if (form) {
        form.addEventListener('submit', function(e){
            if (!String(idEl.value||'').trim()) {
                e.preventDefault();
                alert('Selecione um paciente da lista.');
            }
        });
    }
})();
</script>
<?php endif; ?>

<!-- Lista de orçamentos -->
<div class="lc-card">
    <div class="lc-card__body" style="padding:0;">
        <?php if (empty($sales)): ?>
            <div class="lc-muted" style="padding:24px; text-align:center;">Nenhum orçamento encontrado.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Paciente</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sales as $s): ?>
                    <?php
                    $bs = (string)($s['budget_status'] ?? 'draft');
                    $createdAt = (string)($s['created_at'] ?? '');
                    $dateFmt = '';
                    try { $dateFmt = (new \DateTimeImmutable($createdAt))->format('d/m/Y'); } catch (\Throwable $e) { $dateFmt = $createdAt; }
                    ?>
                    <tr>
                        <td style="white-space:nowrap; font-size:13px;"><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-weight:600;">
                            <?= htmlspecialchars((string)($s['patient_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <span class="lc-badge <?= $budgetStatusBadge[$bs] ?? 'lc-badge--secondary' ?>">
                                <?= htmlspecialchars($budgetStatusLabel[$bs] ?? $bs, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td style="font-weight:600;">
                            R$ <?= number_format((float)$s['total_liquido'], 2, ',', '.') ?>
                            <?php if ((float)$s['desconto'] > 0): ?>
                                <span class="lc-muted" style="font-size:12px; font-weight:400;">(-R$ <?= number_format((float)$s['desconto'], 2, ',', '.') ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/sales/view?id=<?= (int)$s['id'] ?>">Abrir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="padding:12px 16px;">
                <div class="lc-muted" style="font-size:12px;">Página <?= (int)$page ?></div>
                <div class="lc-flex lc-gap-sm">
                    <?php if ($page > 1): ?>
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/sales?per_page=<?= (int)$perPage ?>&page=<?= (int)($page-1) ?><?= $patientId !== null ? ('&patient_id='.(int)$patientId) : '' ?>">Anterior</a>
                    <?php endif; ?>
                    <?php if ($hasNext): ?>
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/finance/sales?per_page=<?= (int)$perPage ?>&page=<?= (int)($page+1) ?><?= $patientId !== null ? ('&patient_id='.(int)$patientId) : '' ?>">Próxima</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
