<?php
/** @var array<string,mixed> $sale */
/** @var list<array<string,mixed>> $items */
/** @var list<array<string,mixed>> $payments */
/** @var list<array<string,mixed>> $logs */
/** @var list<array<string,mixed>> $professionals */
/** @var list<array<string,mixed>> $services */
/** @var list<array<string,mixed>> $packages */
/** @var list<array<string,mixed>> $plans */
/** @var string $error */
/** @var bool $is_professional */

$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Venda #' . (int)$sale['id'];

$svcMap = [];
foreach ($services as $s) {
    $svcMap[(int)$s['id']] = $s;
}
$pkgMap = [];
foreach ($packages as $p) {
    $pkgMap[(int)$p['id']] = $p;
}
$planMap = [];
foreach ($plans as $p) {
    $planMap[(int)$p['id']] = $p;
}
$profMap = [];
foreach ($professionals as $p) {
    $profMap[(int)$p['id']] = $p;
}

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-card lc-statusbar lc-statusbar--no_show" style="margin-bottom: 16px;">
        <div class="lc-card__body"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Resumo</div>
    <div class="lc-card__body lc-grid lc-grid--4 lc-gap-grid">
        <div><strong>Status:</strong> <?= htmlspecialchars((string)$sale['status'], ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Paciente:</strong> <?= $sale['patient_id'] === null ? '-' : (int)$sale['patient_id'] ?></div>
        <div><strong>Total líquido:</strong> R$ <?= number_format((float)$sale['total_liquido'], 2, ',', '.') ?></div>
        <div><strong>Criada em:</strong> <?= htmlspecialchars((string)$sale['created_at'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>

<?php if (!isset($is_professional) || !$is_professional): ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Adicionar item</div>
        <div class="lc-card__body">
            <form method="post" action="/finance/sales/items/add" class="lc-form lc-grid lc-gap-grid lc-grid--end" style="grid-template-columns: 1fr 2fr 1fr 1fr 1fr;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />

            <div class="lc-field">
                <label class="lc-label">Tipo</label>
                <select class="lc-select" name="type" id="sale_item_type">
                    <option value="procedure">Procedimento (Serviço)</option>
                    <option value="package">Pacote</option>
                    <option value="subscription">Assinatura</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Item</label>
                <input type="hidden" name="reference_id" id="sale_item_reference_id" value="" />

                <select class="lc-select" id="sale_item_service" data-ref-select>
                    <option value="">Selecione um serviço</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>

                <select class="lc-select" id="sale_item_package" data-ref-select style="display:none;">
                    <option value="">Selecione um pacote</option>
                    <?php foreach ($packages as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>

                <select class="lc-select" id="sale_item_plan" data-ref-select style="display:none;">
                    <option value="">Selecione um plano</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Profissional (opcional)</label>
                <select class="lc-select" name="professional_id">
                    <option value="0">-</option>
                    <?php foreach ($professionals as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Qtd</label>
                <input class="lc-input" type="number" name="quantity" min="1" step="1" value="1" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Valor unit (R$) (0=auto)</label>
                <input class="lc-input" type="text" name="unit_price" value="0" />
            </div>

                <div style="grid-column: 1 / -1;">
                    <button class="lc-btn" type="submit">Adicionar item</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
(function(){
  const typeEl = document.getElementById('sale_item_type');
  const refIdEl = document.getElementById('sale_item_reference_id');
  const svcEl = document.getElementById('sale_item_service');
  const pkgEl = document.getElementById('sale_item_package');
  const planEl = document.getElementById('sale_item_plan');

  if (!typeEl || !refIdEl || !svcEl || !pkgEl || !planEl) return;

  function showFor(type){
    svcEl.style.display = type === 'procedure' ? 'block' : 'none';
    pkgEl.style.display = type === 'package' ? 'block' : 'none';
    planEl.style.display = type === 'subscription' ? 'block' : 'none';
    refIdEl.value = '';
    svcEl.value = '';
    pkgEl.value = '';
    planEl.value = '';
  }

  function sync(){
    const type = typeEl.value;
    if (type === 'procedure') refIdEl.value = svcEl.value;
    if (type === 'package') refIdEl.value = pkgEl.value;
    if (type === 'subscription') refIdEl.value = planEl.value;
  }

  typeEl.addEventListener('change', function(){ showFor(typeEl.value); });
  svcEl.addEventListener('change', sync);
  pkgEl.addEventListener('change', sync);
  planEl.addEventListener('change', sync);

  const form = typeEl.closest('form');
  if (form) {
    form.addEventListener('submit', function(e){
      sync();
      if (!String(refIdEl.value || '').trim()) {
        e.preventDefault();
      }
    });
  }

  showFor(typeEl.value);
})();
</script>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Itens</div>
    <div class="lc-card__body">
        <?php if ($items === []): ?>
            <div class="lc-muted">Sem itens.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Ref</th>
                    <th>Profissional</th>
                    <th>Qtd</th>
                    <th>Unit</th>
                    <th>Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <?php
                        $refName = '';
                        if ((string)$it['type'] === 'procedure') {
                            $rid = (int)$it['reference_id'];
                            $refName = isset($svcMap[$rid]) ? (string)$svcMap[$rid]['name'] : '';
                        }
                        if ((string)$it['type'] === 'package') {
                            $rid = (int)$it['reference_id'];
                            $refName = isset($pkgMap[$rid]) ? (string)$pkgMap[$rid]['name'] : '';
                        }
                        if ((string)$it['type'] === 'subscription') {
                            $rid = (int)$it['reference_id'];
                            $refName = isset($planMap[$rid]) ? (string)$planMap[$rid]['name'] : '';
                        }
                        $pid = $it['professional_id'] === null ? 0 : (int)$it['professional_id'];
                        $pname = $pid > 0 && isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : '-';
                    ?>
                    <tr>
                        <td><?= (int)$it['id'] ?></td>
                        <td><?= htmlspecialchars((string)$it['type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>#<?= (int)$it['reference_id'] ?> <?= $refName !== '' ? ('- ' . htmlspecialchars($refName, ENT_QUOTES, 'UTF-8')) : '' ?></td>
                        <td><?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$it['quantity'] ?></td>
                        <td><?= number_format((float)$it['unit_price'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$it['subtotal'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php if (!isset($is_professional) || !$is_professional): ?>
    <div class="lc-card" style="margin-bottom: 16px;">
        <div class="lc-card__header">Registrar pagamento</div>
        <div class="lc-card__body">
            <form method="post" action="/finance/payments/create" class="lc-form lc-grid lc-gap-grid lc-grid--end" style="grid-template-columns: 1fr 1fr 1fr 1fr 2fr;">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />

            <div class="lc-field">
                <label class="lc-label">Método</label>
                <select class="lc-select" name="method">
                    <option value="pix">PIX</option>
                    <option value="card">Cartão</option>
                    <option value="cash">Dinheiro</option>
                    <option value="boleto">Boleto</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Valor (R$)</label>
                <input class="lc-input" type="text" name="amount" required />
            </div>

            <div class="lc-field">
                <label class="lc-label">Status</label>
                <select class="lc-select" name="status">
                    <option value="pending">Pendente</option>
                    <option value="paid">Pago</option>
                </select>
            </div>

            <div class="lc-field">
                <label class="lc-label">Taxas (R$)</label>
                <input class="lc-input" type="text" name="fees" value="0" />
            </div>

            <div class="lc-field">
                <label class="lc-label">Gateway Ref (opcional)</label>
                <input class="lc-input" type="text" name="gateway_ref" />
            </div>

                <div style="grid-column: 1 / -1;">
                    <button class="lc-btn" type="submit">Adicionar pagamento</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Pagamentos</div>
    <div class="lc-card__body">
        <?php if ($payments === []): ?>
            <div class="lc-muted">Sem pagamentos.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Método</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Taxas</th>
                    <th>Pago em</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= (int)$p['id'] ?></td>
                        <td><?= htmlspecialchars((string)$p['method'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$p['amount'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars((string)$p['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float)$p['fees'], 2, ',', '.') ?></td>
                        <td><?= $p['paid_at'] === null ? '-' : htmlspecialchars((string)$p['paid_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ((!isset($is_professional) || !$is_professional) && (string)$p['status'] !== 'refunded'): ?>
                                <form method="post" action="/finance/payments/refund">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
                                    <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>" />
                                    <button class="lc-btn lc-btn--secondary" type="submit">Estornar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card" style="margin-bottom: 16px;">
    <div class="lc-card__header">Ações</div>
    <div class="lc-card__body lc-flex lc-gap-md lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/finance/sales">Voltar</a>

        <?php if (!isset($is_professional) || !$is_professional): ?>
            <form method="post" action="/finance/sales/cancel">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
                <button class="lc-btn lc-btn--secondary" type="submit">Cancelar venda</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="lc-card">
    <div class="lc-card__header">Logs (imutáveis)</div>
    <div class="lc-card__body">
        <?php if ($logs === []): ?>
            <div class="lc-muted">Sem logs.</div>
        <?php else: ?>
            <table class="lc-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Ação</th>
                    <th>Meta</th>
                    <th>Actor</th>
                    <th>IP</th>
                    <th>Em</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?= (int)$l['id'] ?></td>
                        <td><?= htmlspecialchars((string)$l['action'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><pre style="white-space: pre-wrap; margin:0;"><?= htmlspecialchars((string)$l['meta_json'], ENT_QUOTES, 'UTF-8') ?></pre></td>
                        <td><?= $l['actor_user_id'] === null ? '-' : (int)$l['actor_user_id'] ?></td>
                        <td><?= $l['ip_address'] === null ? '-' : htmlspecialchars((string)$l['ip_address'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$l['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
?>
