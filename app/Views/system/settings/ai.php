<?php
$title = 'Admin - IA';
$csrf = $_SESSION['_csrf'] ?? '';
$key_set = isset($key_set) ? (bool)$key_set : false;
$success = isset($success) ? (string)$success : '';
$error   = isset($error)   ? (string)$error   : '';

/** @var array<string,mixed> $wallet */
$wallet = isset($wallet) && is_array($wallet) ? $wallet : [];
/** @var list<array<string,mixed>> $wallet_transactions */
$wallet_transactions = isset($wallet_transactions) && is_array($wallet_transactions) ? $wallet_transactions : [];
/** @var array<string,mixed> $billing_settings */
$billing_settings = isset($billing_settings) && is_array($billing_settings) ? $billing_settings : [];
/** @var array<string,mixed> $superadmin_profile */
$superadmin_profile = isset($superadmin_profile) && is_array($superadmin_profile) ? $superadmin_profile : [];

$balance     = number_format((float)($wallet['balance_brl'] ?? 0), 2, ',', '.');
$cardLast4   = trim((string)($wallet['asaas_card_last4'] ?? ''));
$cardToken   = trim((string)($wallet['asaas_card_token'] ?? ''));
$hasCard     = $cardToken !== '';
$autoEnabled = (bool)($wallet['auto_recharge_enabled'] ?? false);
$threshold   = number_format((float)($wallet['auto_recharge_threshold_brl'] ?? 10.00), 2, '.', '');
$rechargeAmt = number_format((float)($wallet['auto_recharge_amount_brl'] ?? 50.00), 2, '.', '');

$saName    = htmlspecialchars((string)($superadmin_profile['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$saEmail   = htmlspecialchars((string)($superadmin_profile['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$saCpf     = htmlspecialchars((string)($superadmin_profile['doc_number'] ?? ''), ENT_QUOTES, 'UTF-8');
$saPhone   = htmlspecialchars((string)($superadmin_profile['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$saPostal  = htmlspecialchars(preg_replace('/\D+/', '', (string)($superadmin_profile['postal_code'] ?? '')), ENT_QUOTES, 'UTF-8');
$saAddrNum = htmlspecialchars((string)($superadmin_profile['address_number'] ?? ''), ENT_QUOTES, 'UTF-8');

// Determine active tab from URL fragment (default: wallet)
$activeTab = 'wallet';

$typeLabels = [
    'debit'          => 'Débito',
    'credit'         => 'Crédito',
    'charge_pending' => 'Cobrança pendente',
    'manual_credit'  => 'Crédito manual',
];

ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.96);">IA — Configurações</div>
        <div style="font-size:13px;color:rgba(31,41,55,.50);margin-top:2px;">Gerencie a Carteira de IA ou configure uma chave OpenAI própria.</div>
    </div>
</div>

<?php if ($success !== ''): ?>
<div class="lc-alert lc-alert--success" style="margin-bottom:14px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
<div class="lc-alert lc-alert--danger" style="margin-bottom:14px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Tab navigation -->
<div style="display:flex;gap:0;border-bottom:2px solid rgba(17,24,39,.08);margin-bottom:24px;" id="ai-tabs">
    <button type="button" onclick="switchTab('wallet')" id="tab-btn-wallet"
        style="padding:10px 20px;font-size:14px;font-weight:700;border:none;background:none;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;color:rgba(31,41,55,.7);">
        💳 Carteira de IA
    </button>
    <button type="button" onclick="switchTab('ownkey')" id="tab-btn-ownkey"
        style="padding:10px 20px;font-size:14px;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;color:rgba(31,41,55,.5);">
        ⚙️ Chave própria
    </button>
</div>

<!-- ===== TAB: Carteira de IA ===== -->
<div id="tab-wallet">

    <!-- Balance display -->
    <div style="display:flex;align-items:center;gap:16px;padding:16px 20px;border-radius:14px;border:1px solid rgba(22,163,74,.22);background:rgba(22,163,74,.05);margin-bottom:20px;max-width:600px;">
        <span style="font-size:28px;">💰</span>
        <div>
            <div style="font-size:12px;color:rgba(31,41,55,.5);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Saldo atual</div>
            <div style="font-size:26px;font-weight:850;color:#16a34a;">R$ <?= $balance ?></div>
        </div>
        <?php if ($hasCard): ?>
        <form method="post" action="/sys/settings/ai/wallet/recharge" style="margin-left:auto;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit"
                onclick="return confirm('Recarregar R$ <?= htmlspecialchars($rechargeAmt, ENT_QUOTES, 'UTF-8') ?> agora?')">
                Recarregar agora
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if ($hasCard): ?>
    <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;border:1px solid rgba(99,102,241,.2);background:rgba(99,102,241,.05);margin-bottom:16px;max-width:600px;">
        <span>💳</span>
        <span style="font-size:13px;font-weight:700;color:rgba(31,41,55,.8);">Cartão registrado: ****<?= htmlspecialchars($cardLast4, ENT_QUOTES, 'UTF-8') ?></span>
        <span style="font-size:12px;color:rgba(31,41,55,.4);margin-left:8px;">Para substituir, preencha os dados abaixo.</span>
    </div>
    <?php endif; ?>

    <!-- Card + recharge config form -->
    <div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:600px;margin-bottom:20px;">
        <form method="post" action="/sys/settings/ai/wallet">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div style="font-weight:700;font-size:15px;color:rgba(31,41,55,.9);margin-bottom:14px;">
                <?= $hasCard ? '🔄 Substituir cartão' : '💳 Registrar cartão de crédito' ?>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="lc-field" style="grid-column:1/-1;">
                    <label class="lc-label">Nome no cartão</label>
                    <input class="lc-input" type="text" name="holder_name" value="<?= $saName ?>" placeholder="Nome completo" autocomplete="cc-name" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">E-mail</label>
                    <input class="lc-input" type="email" name="email" value="<?= $saEmail ?>" placeholder="email@exemplo.com" autocomplete="email" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">CPF</label>
                    <input class="lc-input" type="text" name="cpf" value="<?= $saCpf ?>" placeholder="000.000.000-00" autocomplete="off" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Telefone</label>
                    <input class="lc-input" type="text" name="phone" value="<?= $saPhone ?>" placeholder="(11) 99999-9999" autocomplete="tel" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">CEP</label>
                    <input class="lc-input" type="text" name="postal_code" value="<?= $saPostal ?>" placeholder="00000-000" maxlength="9" autocomplete="postal-code" />
                    <div style="font-size:11px;color:rgba(31,41,55,.4);margin-top:3px;">Obrigatório pela Asaas para tokenizar o cartão</div>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Número do endereço</label>
                    <input class="lc-input" type="text" name="address_number" value="<?= $saAddrNum ?>" placeholder="123" maxlength="20" />
                </div>
                <div class="lc-field" style="grid-column:1/-1;">
                    <label class="lc-label">Número do cartão</label>
                    <input class="lc-input" type="text" name="card_number" placeholder="0000 0000 0000 0000" autocomplete="cc-number" maxlength="19" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Mês de validade</label>
                    <input class="lc-input" type="text" name="expiry_month" placeholder="MM" maxlength="2" autocomplete="cc-exp-month" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">Ano de validade</label>
                    <input class="lc-input" type="text" name="expiry_year" placeholder="AAAA" maxlength="4" autocomplete="cc-exp-year" />
                </div>
                <div class="lc-field">
                    <label class="lc-label">CVV</label>
                    <input class="lc-input" type="password" name="ccv" placeholder="000" maxlength="4" autocomplete="cc-csc" />
                </div>
            </div>

            <div style="border-top:1px solid rgba(17,24,39,.07);margin:18px 0 14px;"></div>
            <div style="font-weight:700;font-size:14px;color:rgba(31,41,55,.8);margin-bottom:12px;">⚡ Recarga automática</div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="lc-field" style="grid-column:1/-1;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                        <input type="checkbox" name="auto_recharge_enabled" value="1" <?= $autoEnabled ? 'checked' : '' ?> style="width:16px;height:16px;" />
                        Ativar recarga automática quando o saldo ficar abaixo do limite
                    </label>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Saldo mínimo (R$)</label>
                    <input class="lc-input" type="number" name="auto_recharge_threshold_brl" value="<?= $threshold ?>" min="1" step="0.01" placeholder="10.00" />
                    <div style="font-size:11px;color:rgba(31,41,55,.4);margin-top:3px;">Recarrega quando o saldo cair abaixo deste valor</div>
                </div>
                <div class="lc-field">
                    <label class="lc-label">Valor da recarga (R$)</label>
                    <input class="lc-input" type="number" name="auto_recharge_amount_brl" value="<?= $rechargeAmt ?>" min="1" step="0.01" placeholder="50.00" />
                    <div style="font-size:11px;color:rgba(31,41,55,.4);margin-top:3px;">Valor cobrado no cartão a cada recarga</div>
                </div>
            </div>

            <div style="margin-top:16px;">
                <button class="lc-btn lc-btn--primary" type="submit">Salvar configurações</button>
            </div>
        </form>
    </div>

    <!-- Transaction history -->
    <?php if (!empty($wallet_transactions)): ?>
    <div style="max-width:800px;">
        <div style="font-weight:700;font-size:14px;color:rgba(31,41,55,.8);margin-bottom:10px;">📋 Histórico de transações</div>
        <div style="overflow-x:auto;border-radius:12px;border:1px solid rgba(17,24,39,.08);">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:rgba(17,24,39,.03);">
                        <th style="padding:10px 12px;text-align:left;font-weight:700;color:rgba(31,41,55,.6);border-bottom:1px solid rgba(17,24,39,.07);">Data</th>
                        <th style="padding:10px 12px;text-align:left;font-weight:700;color:rgba(31,41,55,.6);border-bottom:1px solid rgba(17,24,39,.07);">Tipo</th>
                        <th style="padding:10px 12px;text-align:right;font-weight:700;color:rgba(31,41,55,.6);border-bottom:1px solid rgba(17,24,39,.07);">Valor</th>
                        <th style="padding:10px 12px;text-align:left;font-weight:700;color:rgba(31,41,55,.6);border-bottom:1px solid rgba(17,24,39,.07);">Descrição</th>
                        <th style="padding:10px 12px;text-align:right;font-weight:700;color:rgba(31,41,55,.6);border-bottom:1px solid rgba(17,24,39,.07);">Saldo após</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wallet_transactions as $tx): ?>
                    <?php
                        $txType = (string)($tx['type'] ?? '');
                        $txAmt  = (float)($tx['amount_brl'] ?? 0);
                        $isDebit = $txType === 'debit';
                        $amtFormatted = ($isDebit ? '−' : '+') . 'R$ ' . number_format($txAmt, 2, ',', '.');
                        $amtColor = $isDebit ? '#dc2626' : '#16a34a';
                        $typeLabel = $typeLabels[$txType] ?? $txType;
                    ?>
                    <tr style="border-bottom:1px solid rgba(17,24,39,.05);">
                        <td style="padding:9px 12px;color:rgba(31,41,55,.6);"><?= htmlspecialchars((string)($tx['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:9px 12px;"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:9px 12px;text-align:right;font-weight:700;color:<?= $amtColor ?>;"><?= $amtFormatted ?></td>
                        <td style="padding:9px 12px;color:rgba(31,41,55,.7);"><?= htmlspecialchars((string)($tx['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:9px 12px;text-align:right;color:rgba(31,41,55,.6);">R$ <?= number_format((float)($tx['balance_after_brl'] ?? 0), 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div style="font-size:13px;color:rgba(31,41,55,.4);padding:12px 0;">Nenhuma transação registrada ainda.</div>
    <?php endif; ?>

</div><!-- #tab-wallet -->

<!-- ===== TAB: Chave própria ===== -->
<div id="tab-ownkey" style="display:none;">

    <div style="padding:12px 14px;border-radius:10px;border:1px solid rgba(99,102,241,.2);background:rgba(99,102,241,.05);font-size:13px;color:rgba(31,41,55,.65);margin-bottom:16px;max-width:600px;">
        ⚙️ <strong>Opção avançada</strong> — recomendamos usar a Carteira de IA para uma experiência mais simples.
    </div>

    <?php if ($key_set): ?>
    <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;border:1px solid rgba(234,179,8,.3);background:rgba(253,224,71,.08);font-size:13px;color:rgba(31,41,55,.75);margin-bottom:16px;max-width:600px;">
        ⚠️ <strong>A chave própria está configurada e tem prioridade sobre a Carteira de IA.</strong>
        Para usar a Carteira de IA, remova a chave própria abaixo.
    </div>
    <?php endif; ?>

    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid <?= $key_set ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;background:<?= $key_set ? 'rgba(22,163,74,.06)' : 'rgba(107,114,128,.04)' ?>;margin-bottom:16px;max-width:600px;">
        <span style="font-size:16px;"><?= $key_set ? '✅' : '⚠️' ?></span>
        <span style="font-weight:700;font-size:13px;color:<?= $key_set ? '#16a34a' : '#6b7280' ?>;"><?= $key_set ? 'Chave global configurada' : 'Sem chave global' ?></span>
    </div>

    <div style="padding:20px;border-radius:14px;border:1px solid rgba(17,24,39,.08);background:var(--lc-surface);box-shadow:0 4px 16px rgba(17,24,39,.06);max-width:600px;margin-bottom:16px;">
        <form method="post" action="/sys/settings/ai">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

            <div class="lc-field">
                <label class="lc-label">Chave da API OpenAI</label>
                <input class="lc-input" type="password" name="openai_api_key" placeholder="<?= $key_set ? 'Já configurada (deixe vazio para manter)' : 'sk-...' ?>" autocomplete="off" />
                <div style="font-size:11px;color:rgba(31,41,55,.40);margin-top:4px;">Quando configurada, todas as clínicas usam esta chave automaticamente. As clínicas não precisam configurar a própria.</div>
            </div>

            <?php if ($key_set): ?>
            <div class="lc-field">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(31,41,55,.55);cursor:pointer;">
                    <input type="checkbox" name="clear_key" value="1" style="width:16px;height:16px;" />
                    Remover chave global (clínicas voltam a usar a própria ou a Carteira de IA)
                </label>
            </div>
            <?php endif; ?>

            <div style="margin-top:14px;"><button class="lc-btn lc-btn--primary" type="submit">Salvar</button></div>
        </form>
    </div>

    <div style="padding:14px 16px;border-radius:12px;border:1px solid rgba(238,184,16,.22);background:rgba(253,229,159,.10);font-size:13px;color:rgba(31,41,55,.70);line-height:1.5;max-width:600px;">
        A IA é usada para: transcrição de áudio no prontuário (Whisper) e geração automática de feriados. O limite de transcrição por clínica é configurado no plano (campo "Limite de transcrição").
    </div>

</div><!-- #tab-ownkey -->

<script>
function switchTab(tab) {
    document.getElementById('tab-wallet').style.display = tab === 'wallet' ? '' : 'none';
    document.getElementById('tab-ownkey').style.display = tab === 'ownkey' ? '' : 'none';

    var btnWallet = document.getElementById('tab-btn-wallet');
    var btnOwnkey = document.getElementById('tab-btn-ownkey');

    if (tab === 'wallet') {
        btnWallet.style.borderBottomColor = '#6366f1';
        btnWallet.style.color = 'rgba(31,41,55,.95)';
        btnOwnkey.style.borderBottomColor = 'transparent';
        btnOwnkey.style.color = 'rgba(31,41,55,.5)';
    } else {
        btnOwnkey.style.borderBottomColor = '#6366f1';
        btnOwnkey.style.color = 'rgba(31,41,55,.95)';
        btnWallet.style.borderBottomColor = 'transparent';
        btnWallet.style.color = 'rgba(31,41,55,.7)';
    }
}

// Activate tab based on URL hash
(function() {
    var hash = window.location.hash;
    if (hash === '#ownkey') {
        switchTab('ownkey');
    } else {
        switchTab('wallet');
    }
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
