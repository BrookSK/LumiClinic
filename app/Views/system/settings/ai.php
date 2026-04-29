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

$typeLabels = [
    'debit'          => 'Débito',
    'credit'         => 'Crédito',
    'charge_pending' => 'Cobrança pendente',
    'manual_credit'  => 'Crédito manual',
];

ob_start();
?>

<style>
.ai-page-header { margin-bottom: 24px; }
.ai-page-header h1 { font-weight: 850; font-size: 20px; color: rgba(31,41,55,.96); margin: 0 0 4px; }
.ai-page-header p  { font-size: 13px; color: rgba(31,41,55,.50); margin: 0; }

/* Tab strip */
.ai-tabs { display: flex; border-bottom: 2px solid rgba(17,24,39,.08); margin-bottom: 28px; gap: 0; }
.ai-tab-btn {
    padding: 10px 22px; font-size: 14px; font-weight: 600;
    border: none; background: none; cursor: pointer;
    border-bottom: 3px solid transparent; margin-bottom: -2px;
    color: rgba(31,41,55,.5); transition: color .15s;
}
.ai-tab-btn.active { border-bottom-color: #6366f1; color: rgba(31,41,55,.95); font-weight: 700; }

/* Two-column layout */
.ai-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 960px; margin-bottom: 24px; }
@media (max-width: 720px) { .ai-cols { grid-template-columns: 1fr; } }

/* Panel card */
.ai-panel {
    border-radius: 16px;
    border: 1px solid rgba(17,24,39,.09);
    background: var(--lc-surface, #fff);
    box-shadow: 0 2px 12px rgba(17,24,39,.06);
    overflow: hidden;
}
.ai-panel-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid rgba(17,24,39,.07);
}
.ai-panel-header-title { font-size: 13px; font-weight: 800; color: rgba(31,41,55,.9); margin-bottom: 3px; }
.ai-panel-header-sub   { font-size: 12px; color: rgba(31,41,55,.5); line-height: 1.5; }
.ai-panel-body { padding: 18px 20px; }

/* Balance card */
.ai-balance-card {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 18px; border-radius: 12px;
    border: 1px solid rgba(22,163,74,.22);
    background: linear-gradient(135deg, rgba(22,163,74,.07) 0%, rgba(22,163,74,.03) 100%);
    margin-bottom: 16px;
}
.ai-balance-amount { font-size: 28px; font-weight: 850; color: #16a34a; line-height: 1; }
.ai-balance-label  { font-size: 11px; color: rgba(31,41,55,.5); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 3px; }

/* Card registered badge */
.ai-card-badge {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 12px; border-radius: 9px;
    border: 1px solid rgba(99,102,241,.2);
    background: rgba(99,102,241,.05);
    font-size: 12px; color: rgba(31,41,55,.7);
    margin-bottom: 16px;
}
.ai-card-badge strong { font-weight: 700; color: rgba(31,41,55,.85); }

/* Form fields */
.ai-field { margin-bottom: 12px; }
.ai-label { display: block; font-size: 12px; font-weight: 600; color: rgba(31,41,55,.65); margin-bottom: 5px; }
.ai-hint  { font-size: 11px; color: rgba(31,41,55,.4); margin-top: 3px; }
.ai-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.ai-grid-2 .span2 { grid-column: 1 / -1; }

/* Divider */
.ai-divider { border: none; border-top: 1px solid rgba(17,24,39,.07); margin: 16px 0; }

/* Section label */
.ai-section-label {
    font-size: 12px; font-weight: 800; color: rgba(31,41,55,.7);
    text-transform: uppercase; letter-spacing: .06em; margin-bottom: 12px;
}

/* Alerts */
.ai-alert-warn {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 10px 13px; border-radius: 9px;
    border: 1px solid rgba(234,179,8,.3); background: rgba(253,224,71,.08);
    font-size: 12px; color: rgba(31,41,55,.75); margin-bottom: 14px; line-height: 1.5;
}
.ai-alert-info {
    padding: 11px 14px; border-radius: 9px;
    border: 1px solid rgba(238,184,16,.22); background: rgba(253,229,159,.10);
    font-size: 12px; color: rgba(31,41,55,.65); line-height: 1.5; margin-top: 16px;
}

/* Transaction table */
.ai-tx-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.ai-tx-table th {
    padding: 9px 11px; text-align: left; font-weight: 700;
    color: rgba(31,41,55,.55); border-bottom: 1px solid rgba(17,24,39,.07);
    background: rgba(17,24,39,.02);
}
.ai-tx-table td { padding: 8px 11px; border-bottom: 1px solid rgba(17,24,39,.04); color: rgba(31,41,55,.7); }
.ai-tx-table tr:last-child td { border-bottom: none; }
</style>

<!-- Page header -->
<div class="ai-page-header">
    <h1>IA — Configurações</h1>
    <p>Escolha como o sistema vai se conectar à inteligência artificial.</p>
</div>

<?php if ($success !== ''): ?>
<div class="lc-alert lc-alert--success" style="margin-bottom:16px;max-width:960px;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
<div class="lc-alert lc-alert--danger" style="margin-bottom:16px;max-width:960px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<!-- Tab navigation -->
<div class="ai-tabs" id="ai-tabs">
    <button type="button" class="ai-tab-btn active" onclick="switchTab('wallet')" id="tab-btn-wallet">
        ✨ Conexão automática
    </button>
    <button type="button" class="ai-tab-btn" onclick="switchTab('ownkey')" id="tab-btn-ownkey">
        🔑 Conexão manual
    </button>
</div>

<!-- ===== TAB: Conexão automática ===== -->
<div id="tab-wallet">

    <!-- Two-column layout: left = status/recharge, right = card form -->
    <div class="ai-cols">

        <!-- LEFT: Saldo + Recarga automática -->
        <div>
            <!-- Balance -->
            <div class="ai-balance-card">
                <span style="font-size:30px;">💰</span>
                <div>
                    <div class="ai-balance-label">Saldo disponível</div>
                    <div class="ai-balance-amount">R$ <?= $balance ?></div>
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
            <div class="ai-card-badge">
                <span>💳</span>
                <span><strong>Cartão ****<?= htmlspecialchars($cardLast4, ENT_QUOTES, 'UTF-8') ?></strong> registrado</span>
                <span style="color:rgba(31,41,55,.35);margin-left:4px;">· preencha abaixo para substituir</span>
            </div>
            <?php else: ?>
            <div class="ai-card-badge" style="border-color:rgba(239,68,68,.2);background:rgba(239,68,68,.04);">
                <span>⚠️</span>
                <span style="color:rgba(185,28,28,.8);">Nenhum cartão cadastrado — cadastre para ativar a recarga automática.</span>
            </div>
            <?php endif; ?>

            <!-- Recarga automática -->
            <div class="ai-panel">
                <div class="ai-panel-header">
                    <div class="ai-panel-header-title">⚡ Recarga automática</div>
                    <div class="ai-panel-header-sub">O sistema recarrega o saldo sozinho quando cair abaixo do limite configurado.</div>
                </div>
                <div class="ai-panel-body">
                    <form method="post" action="/sys/settings/ai/wallet" id="form-recharge-config">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <!-- Hidden card fields so form submission doesn't clear card -->
                        <input type="hidden" name="card_number" value="" />
                        <input type="hidden" name="expiry_month" value="" />
                        <input type="hidden" name="expiry_year" value="" />
                        <input type="hidden" name="ccv" value="" />

                        <div class="ai-field">
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;font-weight:600;color:rgba(31,41,55,.8);">
                                <input type="checkbox" name="auto_recharge_enabled" value="1" <?= $autoEnabled ? 'checked' : '' ?> style="width:15px;height:15px;accent-color:#6366f1;" />
                                Ativar recarga automática
                            </label>
                        </div>

                        <div class="ai-grid-2" style="margin-top:10px;">
                            <div class="ai-field">
                                <label class="ai-label">Saldo mínimo (R$)</label>
                                <input class="lc-input" type="number" name="auto_recharge_threshold_brl" value="<?= $threshold ?>" min="1" step="0.01" placeholder="10.00" />
                                <div class="ai-hint">Recarrega quando cair abaixo deste valor</div>
                            </div>
                            <div class="ai-field">
                                <label class="ai-label">Valor da recarga (R$)</label>
                                <input class="lc-input" type="number" name="auto_recharge_amount_brl" value="<?= $rechargeAmt ?>" min="1" step="0.01" placeholder="50.00" />
                                <div class="ai-hint">Cobrado no cartão a cada recarga</div>
                            </div>
                        </div>

                        <div style="margin-top:14px;">
                            <button class="lc-btn lc-btn--primary lc-btn--sm" type="submit">Salvar configurações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: Card form -->
        <div>
            <div class="ai-panel">
                <div class="ai-panel-header">
                    <div class="ai-panel-header-title"><?= $hasCard ? '🔄 Substituir cartão de crédito' : '💳 Cadastrar cartão de crédito' ?></div>
                    <div class="ai-panel-header-sub">Os dados são tokenizados com segurança. Nenhuma informação do cartão é armazenada.</div>
                </div>
                <div class="ai-panel-body">
                    <form method="post" action="/sys/settings/ai/wallet">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />
                        <!-- Preserve recharge config when only updating card -->
                        <input type="hidden" name="auto_recharge_enabled" value="<?= $autoEnabled ? '1' : '0' ?>" />
                        <input type="hidden" name="auto_recharge_threshold_brl" value="<?= $threshold ?>" />
                        <input type="hidden" name="auto_recharge_amount_brl" value="<?= $rechargeAmt ?>" />

                        <div class="ai-section-label">Dados do titular</div>
                        <div class="ai-grid-2">
                            <div class="ai-field span2">
                                <label class="ai-label">Nome no cartão</label>
                                <input class="lc-input" type="text" name="holder_name" value="<?= $saName ?>" placeholder="Nome completo" autocomplete="cc-name" />
                            </div>
                            <div class="ai-field">
                                <label class="ai-label">E-mail</label>
                                <input class="lc-input" type="email" name="email" value="<?= $saEmail ?>" placeholder="email@exemplo.com" autocomplete="email" />
                            </div>
                            <div class="ai-field">
                                <label class="ai-label">CPF</label>
                                <input class="lc-input" type="text" name="cpf" value="<?= $saCpf ?>" placeholder="000.000.000-00" autocomplete="off" />
                            </div>
                            <div class="ai-field">
                                <label class="ai-label">Telefone</label>
                                <input class="lc-input" type="text" name="phone" value="<?= $saPhone ?>" placeholder="(11) 99999-9999" autocomplete="tel" />
                            </div>
                            <div class="ai-field">
                                <label class="ai-label">CEP</label>
                                <input class="lc-input" type="text" name="postal_code" value="<?= $saPostal ?>" placeholder="00000-000" maxlength="9" autocomplete="postal-code" />
                            </div>
                            <div class="ai-field">
                                <label class="ai-label">Número do endereço</label>
                                <input class="lc-input" type="text" name="address_number" value="<?= $saAddrNum ?>" placeholder="123" maxlength="20" />
                            </div>
                        </div>

                        <hr class="ai-divider" />
                        <div class="ai-section-label">Dados do cartão</div>

                        <div class="ai-grid-2">
                            <div class="ai-field span2">
                                <label class="ai-label">Número do cartão</label>
                                <input class="lc-input" type="text" name="card_number" placeholder="0000 0000 0000 0000" autocomplete="cc-number" maxlength="19" />
                            </div>
                            <div class="ai-field">
                                <label class="ai-label">Mês de validade</label>
                                <input class="lc-input" type="text" name="expiry_month" placeholder="MM" maxlength="2" autocomplete="cc-exp-month" />
                            </div>
                            <div class="ai-field">
                                <label class="ai-label">Ano de validade</label>
                                <input class="lc-input" type="text" name="expiry_year" placeholder="AAAA" maxlength="4" autocomplete="cc-exp-year" />
                            </div>
                            <div class="ai-field">
                                <label class="ai-label">CVV</label>
                                <input class="lc-input" type="password" name="ccv" placeholder="000" maxlength="4" autocomplete="cc-csc" />
                            </div>
                        </div>

                        <div style="margin-top:16px;">
                            <button class="lc-btn lc-btn--primary" type="submit"><?= $hasCard ? 'Substituir cartão' : 'Cadastrar cartão' ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div><!-- .ai-cols -->

    <!-- Transaction history -->
    <?php if (!empty($wallet_transactions)): ?>
    <div style="max-width:960px;">
        <div style="font-weight:700;font-size:13px;color:rgba(31,41,55,.7);margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">📋 Histórico de transações</div>
        <div style="overflow-x:auto;border-radius:12px;border:1px solid rgba(17,24,39,.08);">
            <table class="ai-tx-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th style="text-align:right;">Valor</th>
                        <th>Descrição</th>
                        <th style="text-align:right;">Saldo após</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wallet_transactions as $tx):
                        $txType = (string)($tx['type'] ?? '');
                        $txAmt  = (float)($tx['amount_brl'] ?? 0);
                        $isDebit = $txType === 'debit';
                        $amtFormatted = ($isDebit ? '−' : '+') . 'R$ ' . number_format($txAmt, 2, ',', '.');
                        $amtColor = $isDebit ? '#dc2626' : '#16a34a';
                        $typeLabel = $typeLabels[$txType] ?? $txType;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($tx['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:right;font-weight:700;color:<?= $amtColor ?>;"><?= $amtFormatted ?></td>
                        <td><?= htmlspecialchars((string)($tx['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:right;">R$ <?= number_format((float)($tx['balance_after_brl'] ?? 0), 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div style="font-size:13px;color:rgba(31,41,55,.4);padding:8px 0;">Nenhuma transação registrada ainda.</div>
    <?php endif; ?>

</div><!-- #tab-wallet -->

<!-- ===== TAB: Conexão manual ===== -->
<div id="tab-ownkey" style="display:none;">

    <div class="ai-cols" style="max-width:700px;grid-template-columns:1fr;">

        <!-- Warning banner when manual key is active -->
        <?php if ($key_set): ?>
        <div class="ai-alert-warn">
            <span style="font-size:16px;flex-shrink:0;">⚠️</span>
            <div>
                <strong>Chave manual ativa</strong> — a Conexão automática está desativada enquanto esta chave estiver configurada.
                Remova a chave abaixo para voltar ao modo automático.
            </div>
        </div>
        <?php endif; ?>

        <div class="ai-panel">
            <div class="ai-panel-header" style="background:rgba(107,114,128,.03);">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:22px;">🔑</span>
                    <div>
                        <div class="ai-panel-header-title">Conexão manual — requer gerenciamento individual</div>
                        <div class="ai-panel-header-sub">
                            Exige criação e manutenção de uma chave de API externa, controle manual de limites e renovação periódica.
                            Qualquer interrupção na chave afeta diretamente o funcionamento da IA para todos os usuários.
                        </div>
                    </div>
                </div>
            </div>
            <div class="ai-panel-body">

                <div style="display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;border:1px solid <?= $key_set ? 'rgba(22,163,74,.22)' : 'rgba(107,114,128,.18)' ?>;background:<?= $key_set ? 'rgba(22,163,74,.06)' : 'rgba(107,114,128,.04)' ?>;margin-bottom:16px;">
                    <span><?= $key_set ? '✅' : '⚠️' ?></span>
                    <span style="font-weight:700;font-size:13px;color:<?= $key_set ? '#16a34a' : '#6b7280' ?>;"><?= $key_set ? 'Chave configurada e ativa' : 'Nenhuma chave configurada' ?></span>
                </div>

                <form method="post" action="/sys/settings/ai">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>" />

                    <div class="ai-field">
                        <label class="ai-label">Chave de API</label>
                        <input class="lc-input" type="password" name="openai_api_key"
                            placeholder="<?= $key_set ? 'Já configurada — deixe vazio para manter' : 'Cole a chave aqui' ?>"
                            autocomplete="off" />
                        <div class="ai-hint">Quando configurada, tem prioridade sobre a Conexão automática. Requer gerenciamento manual de limites e renovação.</div>
                    </div>

                    <?php if ($key_set): ?>
                    <div class="ai-field">
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(31,41,55,.55);cursor:pointer;">
                            <input type="checkbox" name="clear_key" value="1" style="width:15px;height:15px;" />
                            Remover chave manual e voltar para a Conexão automática
                        </label>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top:14px;">
                        <button class="lc-btn lc-btn--primary" type="submit">Salvar</button>
                    </div>
                </form>

                <div class="ai-alert-info">
                    💡 A IA é usada para transcrição de áudio nos prontuários. O limite de transcrição por clínica é definido no plano.
                </div>
            </div>
        </div>

    </div>

</div><!-- #tab-ownkey -->

<script>
function switchTab(tab) {
    document.getElementById('tab-wallet').style.display = tab === 'wallet' ? '' : 'none';
    document.getElementById('tab-ownkey').style.display  = tab === 'ownkey'  ? '' : 'none';

    document.getElementById('tab-btn-wallet').classList.toggle('active', tab === 'wallet');
    document.getElementById('tab-btn-ownkey').classList.toggle('active', tab === 'ownkey');
}

(function() {
    if (window.location.hash === '#ownkey') {
        switchTab('ownkey');
    } else {
        switchTab('wallet');
    }
})();
</script>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__, 2) . '/layout/app.php';
