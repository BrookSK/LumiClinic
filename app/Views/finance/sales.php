<?php
/** @var list<array<string,mixed>> $sales */
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>> $services */
/** @var list<array<string,mixed>> $packages */
/** @var list<array<string,mixed>> $plans */
/** @var string $error */
/** @var int $created */
/** @var bool $is_professional */
/** @var int $page */
/** @var int $per_page */
/** @var bool $has_next */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Financeiro - Vendas';

$page = isset($page) ? (int)$page : 1;
$perPage = isset($per_page) ? (int)$per_page : 50;
$hasNext = isset($has_next) ? (bool)$has_next : false;

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom: 16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (!isset($is_professional) || !$is_professional): ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Nova venda</div>
        <div class="lc-card__body">
            <form method="post" action="/finance/sales/create" class="lc-form lc-grid lc-gap-grid lc-grid--end" style="grid-template-columns: 1fr 1fr 1fr 2fr;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="lc-field">
                    <label class="lc-label">Paciente (opcional)</label>
                    <input class="lc-input" type="text" id="sale_patient_search" placeholder="Buscar por nome, e-mail ou telefone" autocomplete="off" />
                    <input type="hidden" name="patient_id" id="sale_patient_id" value="" />
                    <div class="lc-autocomplete" id="sale_patient_results" style="display:none;"></div>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Origem</label>
                    <select class="lc-select" name="origin">
                        <option value="reception">Recepção</option>
                        <option value="online">Online</option>
                        <option value="system">Sistema</option>
                    </select>
                </div>

                <div class="lc-field">
                    <label class="lc-label">Desconto (R$)</label>
                    <input class="lc-input" type="text" name="desconto" value="0" />
                </div>

                <div class="lc-field">
                    <label class="lc-label">Observações</label>
                    <input class="lc-input" type="text" name="notes" />
                </div>

                <div style="grid-column: 1 / -1;">
                    <button class="lc-btn" type="submit">Criar venda</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
(function(){
  const searchEl = document.getElementById('sale_patient_search');
  const idEl = document.getElementById('sale_patient_id');
  const resultsEl = document.getElementById('sale_patient_results');
  if (!searchEl || !idEl || !resultsEl) return;

  function hide(){ resultsEl.style.display='none'; resultsEl.innerHTML=''; }
  function clear(){ idEl.value=''; }
  async function search(q){
    const url = `/patients/search-json?q=${encodeURIComponent(q)}&limit=10`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return [];
    const data = await res.json();
    return (data && data.items) ? data.items : [];
  }

  let t = null;
  searchEl.addEventListener('input', function(){
    clear();
    const q = (searchEl.value || '').trim();
    hide();
    if (t) window.clearTimeout(t);
    if (q.length < 2) return;
    t = window.setTimeout(async function(){
      let items = [];
      try { items = await search(q); } catch(e) { items = []; }
      if (!Array.isArray(items) || items.length === 0) { hide(); return; }
      resultsEl.innerHTML = '';
      for (const it of items) {
        const row = document.createElement('button');
        row.type = 'button';
        row.className = 'lc-autocomplete__item';
        const name = (it.name || '').toString();
        const meta = [it.phone, it.email].filter(Boolean).join(' · ');
        row.innerHTML = `<div class="lc-autocomplete__name"></div><div class="lc-autocomplete__meta"></div>`;
        const nameEl = row.querySelector('.lc-autocomplete__name');
        const metaEl = row.querySelector('.lc-autocomplete__meta');
        if (nameEl) nameEl.textContent = name;
        if (metaEl) metaEl.textContent = meta;
        row.addEventListener('click', function(){
          idEl.value = String(it.id || '');
          searchEl.value = name;
          hide();
        });
        resultsEl.appendChild(row);
      }
      resultsEl.style.display = 'block';
    }, 250);
  });

  searchEl.addEventListener('blur', function(){ window.setTimeout(hide, 150); });
})();
</script>

<div class="lc-card">
    <div class="lc-card__header">Vendas</div>
    <div class="lc-card__body">
        <?php if ($sales === []): ?>
            <div class="lc-muted">Nenhuma venda.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Paciente</th>
                    <th>Total bruto</th>
                    <th>Desconto</th>
                    <th>Total líquido</th>
                    <th>Criada em</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($sales as $s): ?>
                    <tr>
                        <td><?= (int)$s['id'] ?></td>
                        <td><?= htmlspecialchars((string)$s['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $s['patient_id'] === null ? '-' : (int)$s['patient_id'] ?></td>
                        <td><?= number_format((float)$s['total_bruto'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$s['desconto'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$s['total_liquido'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)$s['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a class="lc-btn lc-btn--secondary" href="/finance/sales/view?id=<?= (int)$s['id'] ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="lc-flex lc-flex--between lc-flex--wrap lc-gap-sm" style="margin-top:12px;">
            <div class="lc-muted">Página <?= (int)$page ?></div>
            <div class="lc-flex lc-gap-sm">
                <?php if ($page > 1): ?>
                    <a class="lc-btn lc-btn--secondary" href="/finance/sales?per_page=<?= (int)$perPage ?>&page=<?= (int)($page - 1) ?>">Anterior</a>
                <?php endif; ?>
                <?php if ($hasNext): ?>
                    <a class="lc-btn lc-btn--secondary" href="/finance/sales?per_page=<?= (int)$perPage ?>&page=<?= (int)($page + 1) ?>">Próxima</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
