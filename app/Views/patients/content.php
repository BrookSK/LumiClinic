<?php
$title = 'Conteúdos do paciente';
$csrf = $_SESSION['_csrf'] ?? '';
$error = $error ?? null;
$contents = $contents ?? [];
ob_start();
?>
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:14px; gap:10px;">
    <div class="lc-badge lc-badge--primary">Conteúdos</div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/patients">Pacientes</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom:14px;">
    <div class="lc-card__title">Cadastrar conteúdo (link)</div>
    <div class="lc-card__body">
        <form method="post" action="/patients/content/create" class="lc-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <label class="lc-label">Tipo</label>
            <select class="lc-select" name="type">
                <option value="link">Link</option>
                <option value="pdf">PDF (estrutura)</option>
                <option value="video">Vídeo</option>
            </select>

            <label class="lc-label">Título</label>
            <input class="lc-input" type="text" name="title" required />

            <label class="lc-label">Descrição (opcional)</label>
            <input class="lc-input" type="text" name="description" />

            <label class="lc-label">URL (opcional)</label>
            <input class="lc-input" type="text" name="url" />

            <label class="lc-label">Procedimento (opcional)</label>
            <input class="lc-input" type="text" name="procedure_type" />

            <label class="lc-label">Público-alvo (opcional)</label>
            <input class="lc-input" type="text" name="audience" />

            <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
        </form>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__title">Conteúdos ativos</div>
    <div class="lc-card__body">
        <?php if (!is_array($contents) || $contents === []): ?>
            <div>Nenhum conteúdo.</div>
        <?php else: ?>
            <div class="lc-table-wrap">
                <table class="lc-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Título</th>
                        <th>URL</th>
                        <th>Conceder ao paciente</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($contents as $c): ?>
                        <tr>
                            <td><?= (int)($c['id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($c['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($c['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" action="/patients/content/grant" class="lc-form lc-flex" style="gap:8px; align-items:flex-end;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="content_id" value="<?= (int)($c['id'] ?? 0) ?>" />
                                    <?php $cid = (int)($c['id'] ?? 0); ?>
                                    <input class="lc-input" type="text" data-patient-search="<?= $cid ?>" placeholder="Buscar paciente" autocomplete="off" required />
                                    <input type="hidden" name="patient_id" data-patient-id="<?= $cid ?>" value="" />
                                    <div class="lc-autocomplete" data-patient-results="<?= $cid ?>" style="display:none;"></div>
                                    <button class="lc-btn lc-btn--secondary" type="submit">Conceder</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
  function hide(el){ if (!el) return; el.style.display='none'; el.innerHTML=''; }
  async function search(q){
    const url = `/patients/search-json?q=${encodeURIComponent(q)}&limit=10`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return [];
    const data = await res.json();
    return (data && data.items) ? data.items : [];
  }

  const inputs = document.querySelectorAll('[data-patient-search]');
  inputs.forEach(function(searchEl){
    const key = searchEl.getAttribute('data-patient-search');
    const idEl = document.querySelector('[data-patient-id="' + key + '"]');
    const resultsEl = document.querySelector('[data-patient-results="' + key + '"]');
    if (!idEl || !resultsEl) return;

    let t = null;
    searchEl.addEventListener('input', function(){
      idEl.value = '';
      const q = (searchEl.value || '').trim();
      hide(resultsEl);
      if (t) window.clearTimeout(t);
      if (q.length < 2) return;
      t = window.setTimeout(async function(){
        let items = [];
        try { items = await search(q); } catch(e) { items = []; }
        if (!Array.isArray(items) || items.length === 0) { hide(resultsEl); return; }
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
            hide(resultsEl);
          });
          resultsEl.appendChild(row);
        }
        resultsEl.style.display = 'block';
      }, 250);
    });

    const form = searchEl.closest('form');
    if (form) {
      form.addEventListener('submit', function(e){
        if (!String(idEl.value || '').trim()) {
          e.preventDefault();
        }
      });
    }

    searchEl.addEventListener('blur', function(){ window.setTimeout(function(){ hide(resultsEl); }, 150); });
  });
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
