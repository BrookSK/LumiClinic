<?php
$csrf = $_SESSION['_csrf'] ?? '';
$title = 'Orçamento #' . (int)$sale['id'];

$svcMap  = []; foreach ($services  as $s) { $svcMap[(int)$s['id']]  = $s; }
$pkgMap  = []; foreach ($packages  as $p) { $pkgMap[(int)$p['id']]  = $p; }
$planMap = []; foreach ($plans     as $p) { $planMap[(int)$p['id']] = $p; }
$profMap = []; foreach ($professionals as $p) { $profMap[(int)$p['id']] = $p; }

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
    'draft'     => 'Rascunho',
    'sent'      => 'Enviado ao paciente',
    'approved'  => 'Aprovado',
    'standby'   => 'Em espera',
    'rejected'  => 'Recusado',
    'completed' => 'Concluído',
];
$budgetStatusBadge = [
    'draft'     => 'lc-badge--secondary',
    'sent'      => 'lc-badge--primary',
    'approved'  => 'lc-badge--success',
    'standby'   => 'lc-badge--secondary',
    'rejected'  => 'lc-badge--danger',
    'completed' => 'lc-badge--success',
];
$paymentMethodLabel = [
    'pix'         => 'PIX',
    'card'        => 'Cartão',
    'credit_card' => 'Cartão de Crédito',
    'debit_card'  => 'Cartão de Débito',
    'cash'        => 'Dinheiro',
    'boleto'      => 'Boleto',
];
$paymentStatusLabel = [
    'pending'  => 'Pendente',
    'paid'     => 'Pago',
    'refunded' => 'Estornado',
];

$bs = (string)($sale['budget_status'] ?? 'draft');
$patientName = (string)($sale['patient_name'] ?? ('#' . (int)($sale['patient_id'] ?? 0)));
$createdAt = (string)($sale['created_at'] ?? '');
$dateFmt = '';
try { $dateFmt = (new \DateTimeImmutable($createdAt))->format('d/m/Y'); } catch (\Throwable $e) { $dateFmt = $createdAt; }

$isCancelled = (string)$sale['status'] === 'cancelled';
$isPaid = (string)$sale['status'] === 'paid';
$isLocked = $isCancelled || $isPaid;
$isProfessional = isset($is_professional) && (bool)$is_professional;

// Build service price map for JS
$svcPriceMap = [];
foreach ($services as $s) {
    $svcPriceMap[(int)$s['id']] = isset($s['price_cents']) && $s['price_cents'] !== null ? ((int)$s['price_cents'] / 100) : 0;
}

// Calculate payment summary
$totalPaid = 0.0;
$totalPending = 0.0;
$countPaid = 0;
$countPending = 0;
$countTotal = count($payments);
foreach ($payments as $pp) {
    $pStatus = (string)($pp['status'] ?? '');
    $pAmt = (float)($pp['amount'] ?? 0);
    if ($pStatus === 'paid') { $totalPaid += $pAmt; $countPaid++; }
    elseif ($pStatus === 'pending') { $totalPending += $pAmt; $countPending++; }
}
$remaining = max(0, (float)$sale['total_liquido'] - $totalPaid);
$hasInstallments = $countTotal > 1;
$allPaid = $countPending === 0 && $countPaid > 0 && $remaining < 0.01;

ob_start();
?>

<?php if (isset($error) && $error !== ''): ?>
    <div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Cabeçalho -->
<div class="lc-flex lc-flex--between lc-flex--center lc-flex--wrap" style="margin-bottom:16px; gap:10px;">
    <div>
        <div class="lc-flex lc-gap-sm" style="align-items:center; flex-wrap:wrap;">
            <div style="font-weight:800; font-size:18px;">Orçamento #<?= (int)$sale['id'] ?></div>
            <span class="lc-badge <?= $budgetStatusBadge[$bs] ?? 'lc-badge--secondary' ?>">
                <?= htmlspecialchars($budgetStatusLabel[$bs] ?? $bs, ENT_QUOTES, 'UTF-8') ?>
            </span>
            <?php if ($isPaid && $bs !== 'completed'): ?>
                <span class="lc-badge lc-badge--success">💰 Pago</span>
            <?php elseif ($countPaid > 0 && $countPending > 0): ?>
                <span class="lc-badge lc-badge--primary">📋 Pagamento em andamento (<?= $countPaid ?>/<?= $countTotal ?>)</span>
            <?php endif; ?>
            <?php if ($isCancelled): ?>
                <span class="lc-badge lc-badge--danger">Cancelado</span>
            <?php endif; ?>
        </div>
        <div class="lc-muted" style="font-size:13px; margin-top:4px;">
            <?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?>
            <?php if ($dateFmt !== ''): ?>
                <span style="margin:0 6px;">·</span><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
            <?php if (($sale['notes'] ?? '') !== ''): ?>
                <span style="margin:0 6px;">·</span><?= htmlspecialchars((string)$sale['notes'], ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="lc-flex lc-gap-sm lc-flex--wrap">
        <a class="lc-btn lc-btn--secondary" href="/finance/sales/print?id=<?= (int)$sale['id'] ?>" target="_blank">🖨️ Imprimir</a>
        <?php
        $patPhone = trim((string)($sale['patient_phone'] ?? ''));
        $patEmail = trim((string)($sale['patient_email'] ?? ''));
        ?>
        <form method="post" action="/finance/sales/send" style="margin:0;display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
            <input type="hidden" name="send_via" value="whatsapp" />
            <button type="submit" class="lc-btn lc-btn--secondary" <?= $patPhone === '' ? 'disabled title="Paciente sem telefone cadastrado"' : '' ?>>📱 Enviar WhatsApp</button>
        </form>
        <form method="post" action="/finance/sales/send" style="margin:0;display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
            <input type="hidden" name="send_via" value="email" />
            <button type="submit" class="lc-btn lc-btn--secondary" <?= $patEmail === '' ? 'disabled title="Paciente sem e-mail cadastrado"' : '' ?>>📧 Enviar E-mail</button>
        </form>
        <?php if ($sale['patient_id'] !== null): ?>
            <a class="lc-btn lc-btn--secondary" href="/patients/view?id=<?= (int)$sale['patient_id'] ?>">Ver paciente</a>
        <?php endif; ?>
        <a class="lc-btn lc-btn--secondary" href="/finance/sales<?= $sale['patient_id'] !== null ? ('?patient_id='.(int)$sale['patient_id']) : '' ?>">Voltar</a>
    </div>
</div>

<div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr;">

    <!-- Itens do orçamento -->
    <div class="lc-card" style="margin:0;">
        <div class="lc-flex lc-flex--between lc-flex--center" style="padding:12px 16px; border-bottom:1px solid rgba(0,0,0,.06);">
            <div style="font-weight:700;">Serviços / Itens</div>
            <?php if (!$isProfessional && $can('finance.sales.update') && !$isLocked): ?>
                <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-add-item')">+ Adicionar</button>
            <?php endif; ?>
        </div>

        <!-- Formulário adicionar item (oculto) -->
        <?php if (!$isProfessional && $can('finance.sales.update') && !$isLocked): ?>
        <div id="form-add-item" style="display:none; padding:12px 16px; border-bottom:1px solid rgba(0,0,0,.06); background:rgba(0,0,0,.02);">
            <form method="post" action="/finance/sales/items/add" class="lc-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
                <div class="lc-grid lc-gap-grid" style="grid-template-columns: 2fr 1fr 80px; align-items:end;">
                    <div class="lc-field">
                        <label class="lc-label">Serviço</label>
                        <select class="lc-select" name="reference_id" id="add_item_svc" required onchange="autoFillPrice()">
                            <option value="">Selecione</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" data-price="<?= isset($s['price_cents']) && $s['price_cents'] !== null ? number_format((int)$s['price_cents'] / 100, 2, '.', '') : '0' ?>"><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="type" value="procedure" />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Valor unit. (R$)</label>
                        <input class="lc-input" type="text" name="unit_price" id="add_item_price" value="0" />
                    </div>
                    <div class="lc-field">
                        <label class="lc-label">Qtd</label>
                        <input class="lc-input" type="number" name="quantity" min="1" value="1" />
                    </div>
                </div>
                <div class="lc-field" style="margin-top:8px;">
                    <label class="lc-label">Profissional (opcional)</label>
                    <select class="lc-select" name="professional_id">
                        <option value="0">-</option>
                        <?php foreach ($professionals as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lc-flex lc-gap-sm" style="margin-top:8px;">
                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Adicionar</button>
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-add-item')">Cancelar</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div style="padding:12px 16px;">
            <?php if (empty($items)): ?>
                <div class="lc-muted" style="font-size:13px;">Nenhum item adicionado ainda.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($items as $it): ?>
                    <?php
                    $refName = '';
                    $t = (string)$it['type'];
                    $rid = (int)$it['reference_id'];
                    if ($t === 'procedure' && isset($svcMap[$rid])) $refName = (string)$svcMap[$rid]['name'];
                    elseif ($t === 'package' && isset($pkgMap[$rid])) $refName = (string)$pkgMap[$rid]['name'];
                    elseif ($t === 'subscription' && isset($planMap[$rid])) $refName = (string)$planMap[$rid]['name'];
                    $pid = (int)($it['professional_id'] ?? 0);
                    $pname = $pid > 0 && isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : '';
                    ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid rgba(0,0,0,.05);">
                        <div>
                            <div style="font-weight:600; font-size:13px;"><?= htmlspecialchars($refName !== '' ? $refName : ('#'.$rid), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="lc-muted" style="font-size:12px;">
                                Qtd: <?= (int)$it['quantity'] ?>
                                <?php if ($pname !== ''): ?> · <?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="text-align:right; font-weight:700; font-size:14px;">
                                R$ <?= number_format((float)$it['subtotal'], 2, ',', '.') ?>
                            </div>
                            <?php if (!$isProfessional && $can('finance.sales.update') && !$isLocked): ?>
                                <form method="post" action="/finance/sales/items/remove" onsubmit="return confirm('Remover este item?');" style="margin:0;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
                                    <input type="hidden" name="item_id" value="<?= (int)$it['id'] ?>" />
                                    <button type="submit" class="lc-btn lc-btn--danger lc-btn--sm" style="padding:2px 8px;font-size:11px;" title="Remover item">✕</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

                <!-- Totais -->
                <div style="margin-top:12px; padding-top:10px; border-top:2px solid rgba(0,0,0,.08);">
                    <?php if ((float)$sale['desconto'] > 0): ?>
                        <div class="lc-flex lc-flex--between" style="font-size:13px; color:#6b7280; margin-bottom:4px;">
                            <span>Subtotal</span>
                            <span>R$ <?= number_format((float)$sale['total_bruto'], 2, ',', '.') ?></span>
                        </div>
                        <div class="lc-flex lc-flex--between" style="font-size:13px; color:#16a34a; margin-bottom:4px;">
                            <span>Desconto</span>
                            <span>-R$ <?= number_format((float)$sale['desconto'], 2, ',', '.') ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="lc-flex lc-flex--between" style="font-weight:800; font-size:16px;">
                        <span>Total</span>
                        <span>R$ <?= number_format((float)$sale['total_liquido'], 2, ',', '.') ?></span>
                    </div>
                    <?php if ($totalPaid > 0 || $countPending > 0): ?>
                        <div class="lc-flex lc-flex--between" style="font-size:13px; color:#16a34a; margin-top:4px;">
                            <span>Pago (<?= $countPaid ?>/<?= $countTotal ?>)</span>
                            <span>R$ <?= number_format($totalPaid, 2, ',', '.') ?></span>
                        </div>
                        <?php if ($countPending > 0): ?>
                        <div class="lc-flex lc-flex--between" style="font-size:13px; color:#eeb810; margin-top:2px;">
                            <span>Pendente (<?= $countPending ?> parcela<?= $countPending > 1 ? 's' : '' ?>)</span>
                            <span>R$ <?= number_format($totalPending, 2, ',', '.') ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($remaining > 0.01): ?>
                        <div class="lc-flex lc-flex--between" style="font-size:13px; color:#b91c1c; margin-top:2px;">
                            <span>Restante</span>
                            <span>R$ <?= number_format($remaining, 2, ',', '.') ?></span>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Coluna direita: status + pagamentos + ações -->
    <div style="display:flex; flex-direction:column; gap:14px;">

        <!-- Status do orçamento -->
        <?php if (!$isProfessional && $can('finance.sales.update') && !$isLocked): ?>
        <div class="lc-card" style="margin:0;">
            <div class="lc-card__header" style="font-weight:700;">Status do orçamento</div>
            <div class="lc-card__body">
                <form method="post" action="/finance/sales/budget-status" class="lc-flex lc-gap-sm" style="align-items:center;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
                    <select class="lc-select" name="budget_status" style="flex:1;">
                        <?php foreach ($budgetStatusLabel as $k => $lbl): ?>
                            <option value="<?= $k ?>" <?= $bs === $k ? 'selected' : '' ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
                </form>

                <!-- Desconto -->
                <div style="margin-top:12px; padding-top:10px; border-top:1px solid rgba(0,0,0,.06);">
                    <div class="lc-label" style="margin-bottom:6px;">Desconto</div>
                    <div class="lc-flex lc-gap-sm" style="align-items:center;">
                        <input class="lc-input" type="text" id="discount_val" value="<?= number_format((float)$sale['desconto'], 2, ',', '.') ?>" style="max-width:100px;" />
                        <select class="lc-select" id="discount_type" style="max-width:80px;">
                            <option value="fixed">R$</option>
                            <option value="percent">%</option>
                        </select>
                        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="applyDiscount()">Aplicar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isPaid): ?>
        <div class="lc-alert lc-alert--success" style="margin:0;">💰 Orçamento pago integralmente. Não é possível editar.</div>
        <?php endif; ?>

        <!-- Pagamentos -->
        <div class="lc-card" style="margin:0;">
            <div class="lc-flex lc-flex--between lc-flex--center" style="padding:12px 16px; border-bottom:1px solid rgba(0,0,0,.06);">
                <div style="font-weight:700;">Pagamentos</div>
                <?php if (!$isProfessional && $can('finance.payments.create') && !$isLocked): ?>
                    <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-payment')">+ Registrar</button>
                <?php endif; ?>
            </div>

            <?php if (!$isProfessional && $can('finance.payments.create') && !$isLocked): ?>
            <div id="form-payment" style="display:none; padding:12px 16px; border-bottom:1px solid rgba(0,0,0,.06); background:rgba(0,0,0,.02);">
                <form method="post" action="/finance/payments/create" class="lc-form">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
                    <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
                        <div class="lc-field">
                            <label class="lc-label">Método</label>
                            <select class="lc-select" name="method" id="pay_method" onchange="toggleCardFields()">
                                <option value="pix">PIX</option>
                                <option value="card">Cartão</option>
                                <option value="cash">Dinheiro</option>
                                <option value="boleto">Boleto</option>
                            </select>
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Valor total (R$)</label>
                            <input class="lc-input" type="text" name="amount" required value="<?= number_format($remaining, 2, ',', '.') ?>" id="pay_amount" />
                            <div id="installment_hint" style="display:none;font-size:11px;color:#6b7280;margin-top:3px;"></div>
                        </div>
                    </div>
                    <!-- Card type fields (hidden by default) -->
                    <div id="card_fields" style="display:none;margin-top:8px;">
                        <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;">
                            <div class="lc-field">
                                <label class="lc-label">Tipo do cartão</label>
                                <select class="lc-select" name="card_type" id="card_type" onchange="toggleInstallments()">
                                    <option value="credit">Crédito</option>
                                    <option value="debit">Débito</option>
                                </select>
                            </div>
                            <div class="lc-field" id="installments_field">
                                <label class="lc-label">Parcelas</label>
                                <select class="lc-select" name="installments">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?>x</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="lc-grid lc-gap-grid" style="grid-template-columns: 1fr 1fr; align-items:end;margin-top:8px;">
                        <div class="lc-field">
                            <label class="lc-label">Data do pagamento</label>
                            <input class="lc-input" type="date" name="paid_at" value="<?= date('Y-m-d') ?>" />
                        </div>
                        <div class="lc-field">
                            <label class="lc-label">Status</label>
                            <select class="lc-select" name="status">
                                <option value="paid">Pago</option>
                                <option value="pending">Pendente</option>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="fees" value="0" />
                    <div class="lc-flex lc-gap-sm" style="margin-top:8px;">
                        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
                        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('form-payment')">Cancelar</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div style="padding:12px 16px;">
                <?php if (empty($payments)): ?>
                    <div class="lc-muted" style="font-size:13px;">Nenhum pagamento registrado.</div>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($payments as $p): ?>
                        <?php
                        $pm = (string)($p['method'] ?? '');
                        $ps = (string)($p['status'] ?? '');
                        $paidAt = (string)($p['paid_at'] ?? '');
                        $paidFmt = '';
                        $paidDateVal = '';
                        try {
                            if ($paidAt !== '') {
                                $dtObj = new \DateTimeImmutable($paidAt);
                                $paidFmt = $dtObj->format('d/m/Y');
                                $paidDateVal = $dtObj->format('Y-m-d');
                            }
                        } catch (\Throwable $e) {}
                        $gRef = trim((string)($p['gateway_ref'] ?? ''));
                        $pId = (int)$p['id'];
                        ?>
                        <div style="padding:6px 0; border-bottom:1px solid rgba(0,0,0,.05);">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <div style="font-size:13px; font-weight:600;">
                                        <?= htmlspecialchars($paymentMethodLabel[$pm] ?? $pm, ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($gRef !== ''): ?><span class="lc-muted" style="font-size:11px;margin-left:4px;">(<?= htmlspecialchars($gRef, ENT_QUOTES, 'UTF-8') ?>)</span><?php endif; ?>
                                        <span class="lc-badge <?= $ps === 'paid' ? 'lc-badge--success' : ($ps === 'refunded' ? 'lc-badge--danger' : 'lc-badge--secondary') ?>" style="font-size:11px; margin-left:4px;">
                                            <?= htmlspecialchars($paymentStatusLabel[$ps] ?? $ps, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                    <?php if ($paidFmt !== ''): ?>
                                        <div class="lc-muted" style="font-size:12px;"><?= htmlspecialchars($paidFmt, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="font-weight:700;">R$ <?= number_format((float)$p['amount'], 2, ',', '.') ?></div>
                                    <?php if (!$isProfessional && $can('finance.payments.create') && !$isCancelled): ?>
                                        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" style="padding:2px 6px;font-size:10px;" onclick="toggleForm('edit-pay-<?= $pId ?>')" title="Editar">✏️</button>
                                        <form method="post" action="/finance/payments/delete" onsubmit="return confirm('Remover este pagamento?');" style="margin:0;">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                            <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
                                            <input type="hidden" name="payment_id" value="<?= $pId ?>" />
                                            <button type="submit" class="lc-btn lc-btn--danger lc-btn--sm" style="padding:2px 6px;font-size:10px;" title="Remover">✕</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Inline edit form (hidden) -->
                            <?php if (!$isProfessional && $can('finance.payments.create') && !$isCancelled): ?>
                            <div id="edit-pay-<?= $pId ?>" style="display:none;margin-top:8px;padding:10px;background:rgba(0,0,0,.02);border-radius:8px;">
                                <form method="post" action="/finance/payments/update" class="lc-form">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                                    <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
                                    <input type="hidden" name="payment_id" value="<?= $pId ?>" />
                                    <div class="lc-grid lc-gap-grid" style="grid-template-columns:1fr 1fr;align-items:end;">
                                        <div class="lc-field">
                                            <label class="lc-label">Método</label>
                                            <select class="lc-select" name="method">
                                                <option value="pix" <?= $pm === 'pix' ? 'selected' : '' ?>>PIX</option>
                                                <option value="credit_card" <?= $pm === 'credit_card' ? 'selected' : '' ?>>Cartão Crédito</option>
                                                <option value="debit_card" <?= $pm === 'debit_card' ? 'selected' : '' ?>>Cartão Débito</option>
                                                <option value="cash" <?= $pm === 'cash' ? 'selected' : '' ?>>Dinheiro</option>
                                                <option value="boleto" <?= $pm === 'boleto' ? 'selected' : '' ?>>Boleto</option>
                                            </select>
                                        </div>
                                        <div class="lc-field">
                                            <label class="lc-label">Valor (R$)</label>
                                            <input class="lc-input" type="text" name="amount" value="<?= number_format((float)$p['amount'], 2, ',', '.') ?>" required />
                                        </div>
                                    </div>
                                    <div class="lc-grid lc-gap-grid" style="grid-template-columns:1fr 1fr;align-items:end;margin-top:6px;">
                                        <div class="lc-field">
                                            <label class="lc-label">Data</label>
                                            <input class="lc-input" type="date" name="paid_at" value="<?= htmlspecialchars($paidDateVal, ENT_QUOTES, 'UTF-8') ?>" />
                                        </div>
                                        <div class="lc-field">
                                            <label class="lc-label">Status</label>
                                            <select class="lc-select" name="status">
                                                <option value="paid" <?= $ps === 'paid' ? 'selected' : '' ?>>Pago</option>
                                                <option value="pending" <?= $ps === 'pending' ? 'selected' : '' ?>>Pendente</option>
                                                <option value="refunded" <?= $ps === 'refunded' ? 'selected' : '' ?>>Estornado</option>
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" name="fees" value="<?= number_format((float)($p['fees'] ?? 0), 2, '.', '') ?>" />
                                    <div class="lc-flex lc-gap-sm" style="margin-top:8px;">
                                        <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar</button>
                                        <button type="button" class="lc-btn lc-btn--secondary lc-btn--sm" onclick="toggleForm('edit-pay-<?= $pId ?>')">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cancelar orçamento -->
        <?php if (!$isProfessional && $can('finance.sales.cancel') && !$isLocked): ?>
        <div class="lc-card" style="margin:0;">
            <div class="lc-card__body">
                <details style="margin:0;">
                    <summary class="lc-muted" style="font-size:12px; cursor:pointer;">Cancelar orçamento</summary>
                    <div style="margin-top:8px;">
                        <form method="post" action="/finance/sales/cancel" onsubmit="return confirm('Tem certeza que deseja cancelar este orçamento?');">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                            <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>" />
                            <button class="lc-btn lc-btn--danger lc-btn--sm" type="submit">Confirmar cancelamento</button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Planejamento de sessões (se houver) -->
<?php if (!empty($procedures)): ?>
<div class="lc-card" style="margin-top:14px;">
    <div class="lc-card__header" style="font-weight:700;">Planejamento de sessões</div>
    <div class="lc-card__body" style="padding:0;">
        <table class="lc-table">
            <thead><tr><th>Serviço</th><th>Profissional</th><th>Sessões</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($procedures as $pp): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($pp['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($pp['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($pp['used_sessions'] ?? 0) ?> / <?= (int)($pp['total_sessions'] ?? 0) ?></td>
                    <td><?= htmlspecialchars((string)($pp['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?php if ($can('scheduling.read') && $sale['patient_id'] !== null): ?>
                        <a class="lc-btn lc-btn--secondary lc-btn--sm" href="/schedule">Agendar</a>
                    <?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Logs técnicos (ocultos) -->
<?php if (!empty($logs)): ?>
<details style="margin-top:10px;">
    <summary class="lc-muted" style="font-size:12px; cursor:pointer; padding:6px 0;">Histórico de alterações</summary>
    <div class="lc-card" style="margin-top:8px;">
        <div class="lc-card__body" style="padding:0;">
            <table class="lc-table" style="font-size:12px;">
                <thead><tr><th>Data</th><th>Ação</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                    <?php
                    $logAt = (string)($l['created_at'] ?? '');
                    $logFmt = '';
                    try { $logFmt = (new \DateTimeImmutable($logAt))->format('d/m/Y H:i'); } catch (\Throwable $e) { $logFmt = $logAt; }
                    ?>
                    <tr>
                        <td style="white-space:nowrap;"><?= htmlspecialchars($logFmt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$l['action'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</details>
<?php endif; ?>

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
    if (el.style.display === 'block') {
        var first = el.querySelector('input:not([type=hidden]),select');
        if (first) first.focus();
    }
}

function autoFillPrice() {
    var sel = document.getElementById('add_item_svc');
    var priceInput = document.getElementById('add_item_price');
    if (!sel || !priceInput) return;
    var opt = sel.options[sel.selectedIndex];
    if (!opt || sel.value === '') return;
    var price = parseFloat(opt.dataset.price || '0');
    if (price > 0) {
        priceInput.value = price.toFixed(2).replace('.', ',');
    }
}

function toggleCardFields() {
    var method = document.getElementById('pay_method');
    var cardFields = document.getElementById('card_fields');
    if (!method || !cardFields) return;
    cardFields.style.display = method.value === 'card' ? 'block' : 'none';
    updateInstallmentHint();
}

function toggleInstallments() {
    var cardType = document.getElementById('card_type');
    var installField = document.getElementById('installments_field');
    if (!cardType || !installField) return;
    installField.style.display = cardType.value === 'credit' ? 'block' : 'none';
    updateInstallmentHint();
}

function updateInstallmentHint() {
    var hint = document.getElementById('installment_hint');
    var method = document.getElementById('pay_method');
    var cardType = document.getElementById('card_type');
    var installSel = document.querySelector('#installments_field select');
    var amountInput = document.getElementById('pay_amount');
    if (!hint || !method || !amountInput) return;

    if (method.value !== 'card' || !cardType || cardType.value !== 'credit' || !installSel) {
        hint.style.display = 'none';
        return;
    }

    var n = parseInt(installSel.value) || 1;
    if (n <= 1) { hint.style.display = 'none'; return; }

    var total = parseFloat((amountInput.value || '0').replace(/\./g, '').replace(',', '.')) || 0;
    if (total <= 0) { hint.style.display = 'none'; return; }

    var parcela = (total / n).toFixed(2).replace('.', ',');
    hint.textContent = n + 'x de R$ ' + parcela + ' — primeira parcela com status selecionado, demais como pendente';
    hint.style.display = 'block';
}

function applyDiscount() {
    var val = parseFloat((document.getElementById('discount_val').value||'0').replace(',','.')) || 0;
    var type = document.getElementById('discount_type').value;
    var total = <?= (float)$sale['total_bruto'] ?>;
    var final_val = type === 'percent' ? (total * val / 100) : val;
    if (final_val < 0) final_val = 0;
    var f = document.createElement('form');
    f.method = 'post';
    f.action = '/finance/sales/budget-status';
    f.innerHTML = '<input name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"/>'
        + '<input name="sale_id" value="<?= (int)$sale['id'] ?>"/>'
        + '<input name="budget_status" value="<?= htmlspecialchars($bs, ENT_QUOTES, 'UTF-8') ?>"/>'
        + '<input name="desconto" value="'+final_val.toFixed(2)+'"/>';
    document.body.appendChild(f);
    f.submit();
}

// Attach listeners for installment hint updates
(function(){
    var installSel = document.querySelector('#installments_field select');
    var amountInput = document.getElementById('pay_amount');
    if (installSel) installSel.addEventListener('change', updateInstallmentHint);
    if (amountInput) amountInput.addEventListener('input', updateInstallmentHint);
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
