<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Portal Dev — LumiClinic AI Billing</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4ff;
            min-height: 100vh;
            color: #1f2937;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar-title { font-size: 16px; font-weight: 800; color: #1f2937; }
        .topbar-sub { font-size: 12px; color: #6b7280; }
        .logout-btn {
            font-size: 13px;
            color: #6b7280;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: none;
            cursor: pointer;
        }
        .logout-btn:hover { background: #f9fafb; }
        .container { max-width: 1100px; margin: 0 auto; padding: 24px 20px; }
        .alert-success {
            background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px;
            padding: 10px 14px; font-size: 13px; color: #16a34a; margin-bottom: 16px;
        }
        .alert-error {
            background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px;
            padding: 10px 14px; font-size: 13px; color: #dc2626; margin-bottom: 16px;
        }
        /* Tabs */
        .tabs { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 24px; }
        .tab-btn {
            padding: 10px 20px; font-size: 14px; font-weight: 600;
            border: none; background: none; cursor: pointer;
            border-bottom: 3px solid transparent; margin-bottom: -2px;
            color: #6b7280; transition: color .15s;
        }
        .tab-btn.active { border-bottom-color: #6366f1; color: #1f2937; font-weight: 700; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        /* Cards */
        .card {
            background: #fff; border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(17,24,39,.06);
            padding: 20px; margin-bottom: 20px;
        }
        .card-title { font-size: 15px; font-weight: 700; color: #1f2937; margin-bottom: 14px; }
        /* Form fields */
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .field input[type="text"],
        .field input[type="password"],
        .field input[type="number"] {
            width: 100%; padding: 9px 11px; border: 1.5px solid #d1d5db;
            border-radius: 7px; font-size: 13px; color: #1f2937; outline: none;
        }
        .field input:focus { border-color: #6366f1; }
        .field .hint { font-size: 11px; color: #9ca3af; margin-top: 3px; }
        .field .key-status { font-size: 12px; font-weight: 700; color: #16a34a; margin-top: 3px; }
        .btn {
            padding: 9px 18px; background: #6366f1; color: #fff;
            border: none; border-radius: 7px; font-size: 13px; font-weight: 700;
            cursor: pointer; transition: background .15s;
        }
        .btn:hover { background: #4f46e5; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        /* Stats grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .stat-card {
            background: #fff; border-radius: 10px; border: 1px solid #e5e7eb;
            padding: 14px 16px;
        }
        .stat-label { font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .stat-value { font-size: 22px; font-weight: 800; color: #1f2937; margin-top: 4px; }
        /* Table */
        .table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead tr { background: #f9fafb; }
        th { padding: 10px 12px; text-align: left; font-weight: 700; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        td { padding: 9px 12px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        tr:last-child td { border-bottom: none; }
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
        }
        .badge-debit { background: #fef2f2; color: #dc2626; }
        .badge-credit { background: #f0fdf4; color: #16a34a; }
        .badge-pending { background: #fffbeb; color: #d97706; }
        .badge-manual { background: #eff6ff; color: #2563eb; }
        .balance-display {
            display: flex; align-items: center; gap: 16px;
            padding: 16px 20px; border-radius: 12px;
            border: 1px solid rgba(22,163,74,.22); background: rgba(22,163,74,.05);
            margin-bottom: 20px;
        }
        .balance-amount { font-size: 28px; font-weight: 850; color: #16a34a; }
        .balance-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; }
        .readonly-input {
            width: 100%; padding: 9px 11px; border: 1.5px solid #e5e7eb;
            border-radius: 7px; font-size: 12px; color: #6b7280;
            background: #f9fafb; font-family: monospace;
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="topbar">
    <div>
        <div class="topbar-title">🤖 AI Billing Portal</div>
        <div class="topbar-sub">
            LumiClinic — Painel do desenvolvedor
            &nbsp;·&nbsp;
            <?php if (($asaasMode ?? 'sandbox') === 'sandbox'): ?>
            <span style="background:#fef9c3;color:#92400e;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;">🧪 SANDBOX</span>
            <?php else: ?>
            <span style="background:#f0fdf4;color:#166534;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;">🚀 PRODUÇÃO</span>
            <?php endif; ?>
        </div>
    </div>
    <form method="post" action="/dev/ai-billing/logout" style="display:inline;">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>" />
        <button type="submit" class="logout-btn">Sair</button>
    </form>
</div>

<div class="container">

    <?php if (isset($saved) && $saved !== ''): ?>
    <div class="alert-success">✅ <?= htmlspecialchars(isset($msgQs) && $msgQs !== '' ? $msgQs : 'Salvo com sucesso.', ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($errorQs) && $errorQs !== ''): ?>
    <div class="alert-error">⚠️ <?= htmlspecialchars($errorQs, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- Tab navigation -->
    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('config')" id="tab-btn-config">⚙️ Configurações</button>
        <button class="tab-btn" onclick="switchTab('stats')" id="tab-btn-stats">📊 Estatísticas</button>
        <button class="tab-btn" onclick="switchTab('wallet')" id="tab-btn-wallet">💰 Carteira</button>
    </div>

    <!-- ===== TAB: Configurações ===== -->
    <div class="tab-panel active" id="tab-config">
        <div class="card">
            <div class="card-title">Configurações do sistema</div>
            <form method="post" action="/dev/ai-billing/settings">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>" />

                <!-- Asaas mode toggle -->
                <div class="field" style="margin-bottom:18px;">
                    <label style="font-size:13px;font-weight:700;color:#374151;display:block;margin-bottom:8px;">Modo Asaas</label>
                    <div style="display:flex;gap:0;border:1.5px solid #d1d5db;border-radius:8px;overflow:hidden;width:fit-content;">
                        <label style="display:flex;align-items:center;gap:6px;padding:8px 18px;cursor:pointer;font-size:13px;font-weight:600;background:<?= ($asaasMode ?? 'sandbox') === 'sandbox' ? '#fef9c3' : '#fff' ?>;color:<?= ($asaasMode ?? 'sandbox') === 'sandbox' ? '#92400e' : '#6b7280' ?>;">
                            <input type="radio" name="asaas_mode" value="sandbox" <?= ($asaasMode ?? 'sandbox') === 'sandbox' ? 'checked' : '' ?> onchange="this.form.submit()" style="display:none;" />
                            🧪 Sandbox
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;padding:8px 18px;cursor:pointer;font-size:13px;font-weight:600;border-left:1.5px solid #d1d5db;background:<?= ($asaasMode ?? 'sandbox') === 'production' ? '#f0fdf4' : '#fff' ?>;color:<?= ($asaasMode ?? 'sandbox') === 'production' ? '#166534' : '#6b7280' ?>;">
                            <input type="radio" name="asaas_mode" value="production" <?= ($asaasMode ?? 'sandbox') === 'production' ? 'checked' : '' ?> onchange="this.form.submit()" style="display:none;" />
                            🚀 Produção
                        </label>
                    </div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:5px;">
                        <?php if (($asaasMode ?? 'sandbox') === 'sandbox'): ?>
                        ⚠️ Modo sandbox ativo — cobranças não são reais. URL: <code>sandbox.asaas.com</code>
                        <?php else: ?>
                        ✅ Modo produção ativo — cobranças reais. URL: <code>api.asaas.com</code>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label>Chave Asaas — Sandbox</label>
                        <input type="password" name="asaas_sandbox_key" placeholder="<?= $asaasSandboxSet ? '••••••••••••' : 'Não configurada' ?>" autocomplete="off" />
                        <?php if ($asaasSandboxSet): ?>
                        <div class="key-status">✅ Configurada</div>
                        <?php else: ?>
                        <div class="hint">Chave da conta sandbox do Asaas</div>
                        <?php endif; ?>
                    </div>
                    <div class="field">
                        <label>Chave Asaas — Produção</label>
                        <input type="password" name="asaas_api_key" placeholder="<?= $asaasKeySet ? '••••••••••••' : 'Não configurada' ?>" autocomplete="off" />
                        <?php if ($asaasKeySet): ?>
                        <div class="key-status">✅ Configurada</div>
                        <?php else: ?>
                        <div class="hint">Chave da conta de produção do Asaas</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Webhook section -->
                <div style="background:#f9fafb;border-radius:8px;padding:14px 16px;margin-bottom:14px;border:1px solid #e5e7eb;">
                    <div style="font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">🔗 Webhook</div>
                    <div class="field">
                        <label>URL do Webhook (somente leitura)</label>
                        <input type="text" class="readonly-input" readonly value="<?= htmlspecialchars($webhookUrl ?? '', ENT_QUOTES, 'UTF-8') ?>" onclick="this.select()" />
                        <div class="hint">Configure na Asaas os eventos: <strong>PAYMENT_CONFIRMED</strong>, <strong>PAYMENT_RECEIVED</strong></div>
                    </div>
                    <div class="grid-2" style="margin-top:10px;">
                        <div class="field">
                            <label>Webhook Secret — Sandbox</label>
                            <input type="password" name="webhook_secret_sandbox" placeholder="<?= $webhookSecretSbxSet ? '••••••••••••' : 'Não configurado' ?>" autocomplete="off" />
                            <?php if ($webhookSecretSbxSet): ?>
                            <div class="key-status">✅ Configurado</div>
                            <?php else: ?>
                            <div class="hint">Token configurado no webhook do Asaas sandbox</div>
                            <?php endif; ?>
                        </div>
                        <div class="field">
                            <label>Webhook Secret — Produção</label>
                            <input type="password" name="webhook_secret_production" placeholder="<?= $webhookSecretProdSet ? '••••••••••••' : 'Não configurado' ?>" autocomplete="off" />
                            <?php if ($webhookSecretProdSet): ?>
                            <div class="key-status">✅ Configurado</div>
                            <?php else: ?>
                            <div class="hint">Token configurado no webhook do Asaas produção</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label>Chave OpenAI API</label>
                    <input type="password" name="openai_api_key" placeholder="<?= $openaiKeySet ? '••••••••••••' : 'Não configurada' ?>" autocomplete="off" />
                    <?php if ($openaiKeySet): ?>
                    <div class="key-status">✅ Configurada</div>
                    <?php else: ?>
                    <div class="hint">Não configurada — insira para salvar</div>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label>URL do Webhook (somente leitura)</label>
                    <input type="text" class="readonly-input" readonly value="<?= htmlspecialchars($webhookUrl ?? '', ENT_QUOTES, 'UTF-8') ?>" onclick="this.select()" />
                    <div class="hint">Configure na Asaas os eventos: <strong>PAYMENT_CONFIRMED</strong>, <strong>PAYMENT_RECEIVED</strong></div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label>Preço por minuto (R$) — cobrado do superadmin</label>
                        <input type="number" name="price_per_minute_brl"
                            value="<?= number_format((float)($settings['price_per_minute_brl'] ?? 0.0910), 4, '.', '') ?>"
                            min="0.0001" step="0.0001" />
                        <div class="hint">Valor debitado da carteira por minuto de transcrição</div>
                    </div>
                    <div class="field">
                        <label>Custo por minuto (R$) — custo real OpenAI</label>
                        <input type="number" name="cost_per_minute_brl"
                            value="<?= number_format((float)($settings['cost_per_minute_brl'] ?? 0.0350), 4, '.', '') ?>"
                            min="0.0001" step="0.0001" />
                        <div class="hint">Custo real pago à OpenAI (para cálculo de lucro)</div>
                    </div>
                </div>

                <div class="field">
                    <label>Nova senha do portal (deixe vazio para manter a atual)</label>
                    <input type="password" name="new_password" placeholder="••••••••" autocomplete="new-password" />
                    <div class="hint">Mínimo recomendado: 12 caracteres</div>
                </div>

                <button class="btn" type="submit">Salvar configurações</button>
            </form>
        </div>
    </div>

    <!-- ===== TAB: Estatísticas ===== -->
    <div class="tab-panel" id="tab-stats">

        <?php
        // Stats based on CREDITS received (recharges from superadmin)
        // Total received = what superadmin paid us
        // Our cost = received × (cost_per_min / price_per_min) = received × (0.035/0.091)
        // Our profit = received - our cost
        $ratio = ($pricePerMin ?? 0.0910) > 0 ? ($costPerMin ?? 0.0350) / ($pricePerMin ?? 0.0910) : 0;

        $monthReceived = (float)($statsMonth['total_credited_brl'] ?? 0);
        $monthOurCost  = round($monthReceived * $ratio, 2);
        $monthOurProfit = round($monthReceived - $monthOurCost, 2);
        $monthDebited  = (float)($statsMonth['total_charged_brl'] ?? 0); // already consumed
        $monthBalance  = round($monthReceived - $monthDebited, 2); // still in wallet

        $totalReceived = (float)($statsTotal['total_credited_brl'] ?? 0);
        $totalOurCost  = round($totalReceived * $ratio, 2);
        $totalOurProfit = round($totalReceived - $totalOurCost, 2);
        $totalDebited  = (float)($statsTotal['total_charged_brl'] ?? 0);
        $totalBalance  = round($totalReceived - $totalDebited, 2);
        ?>

        <!-- Info box explaining the logic -->
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;font-size:13px;color:#1e40af;margin-bottom:20px;line-height:1.6;">
            💡 <strong>Como ler estas estatísticas:</strong>
            O superadmin recarrega a carteira. Cada real recebido tem um custo proporcional de
            <strong>R$ <?= number_format($ratio, 4, ',', '.') ?></strong> por real
            (proporção custo/preço: <?= number_format($costPerMin ?? 0.0350, 4, ',', '.') ?> / <?= number_format($pricePerMin ?? 0.0910, 4, ',', '.') ?>).
            O restante é lucro.
        </div>

        <div style="font-weight:700;font-size:14px;color:#374151;margin-bottom:12px;">📅 Mês atual (<?= htmlspecialchars($currentMonth ?? date('Y-m'), ENT_QUOTES, 'UTF-8') ?>)</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Recebido do superadmin</div>
                <div class="stat-value">R$ <?= number_format($monthReceived, 2, ',', '.') ?></div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Total de recargas no mês</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Nosso custo (OpenAI)</div>
                <div class="stat-value" style="color:#dc2626;">R$ <?= number_format($monthOurCost, 2, ',', '.') ?></div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Precisa ter na OpenAI</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Nosso lucro</div>
                <div class="stat-value" style="color:#16a34a;">R$ <?= number_format($monthOurProfit, 2, ',', '.') ?></div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Margem de <?= number_format((1 - $ratio) * 100, 1, ',', '.') ?>%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Já consumido</div>
                <div class="stat-value">R$ <?= number_format($monthDebited, 2, ',', '.') ?></div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Debitado em transcrições</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Saldo disponível</div>
                <div class="stat-value" style="color:#6366f1;">R$ <?= number_format($monthBalance, 2, ',', '.') ?></div>
                <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Ainda na carteira</div>
            </div>
        </div>

        <div style="font-weight:700;font-size:14px;color:#374151;margin-bottom:12px;margin-top:20px;">🌐 Total acumulado</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Recebido do superadmin</div>
                <div class="stat-value">R$ <?= number_format($totalReceived, 2, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Nosso custo (OpenAI)</div>
                <div class="stat-value" style="color:#dc2626;">R$ <?= number_format($totalOurCost, 2, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Nosso lucro</div>
                <div class="stat-value" style="color:#16a34a;">R$ <?= number_format($totalOurProfit, 2, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Já consumido</div>
                <div class="stat-value">R$ <?= number_format($totalDebited, 2, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Saldo disponível</div>
                <div class="stat-value" style="color:#6366f1;">R$ <?= number_format($totalBalance, 2, ',', '.') ?></div>
            </div>
        </div>

        <?php if (!empty($transactions)): ?>
        <div class="card">
            <div class="card-title">Transações detalhadas</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Cobrado (R$)</th>
                            <th>Custo (R$)</th>
                            <th>Lucro (R$)</th>
                            <th>Clínica</th>
                            <th>Minutos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx):
                            $txType = (string)($tx['type'] ?? '');
                            $txAmt  = (float)($tx['amount_brl'] ?? 0);
                            $txSecs = (int)($tx['duration_seconds'] ?? 0);
                            $txMins = $txSecs > 0 ? (int)ceil($txSecs / 60) : 0;
                            $txCost = $txType === 'debit' ? round($txMins * ($costPerMin ?? 0.0350), 4) : 0;
                            $txProfit = $txType === 'debit' ? round($txAmt - $txCost, 4) : 0;
                            $badgeClass = match($txType) {
                                'debit' => 'badge-debit',
                                'credit' => 'badge-credit',
                                'charge_pending' => 'badge-pending',
                                default => 'badge-manual',
                            };
                            $typeLabels = [
                                'debit' => 'Débito', 'credit' => 'Crédito',
                                'charge_pending' => 'Pendente', 'manual_credit' => 'Manual',
                            ];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($tx['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($typeLabels[$txType] ?? $txType, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= $txType === 'debit' ? 'R$ ' . number_format($txAmt, 4, ',', '.') : '—' ?></td>
                            <td><?= $txType === 'debit' ? 'R$ ' . number_format($txCost, 4, ',', '.') : '—' ?></td>
                            <td><?= $txType === 'debit' ? 'R$ ' . number_format($txProfit, 4, ',', '.') : '—' ?></td>
                            <td><?= htmlspecialchars((string)($tx['clinic_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $txMins > 0 ? $txMins : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div style="font-size:13px;color:#9ca3af;padding:12px 0;">Nenhuma transação registrada ainda.</div>
        <?php endif; ?>

    </div>

    <!-- ===== TAB: Carteira ===== -->
    <div class="tab-panel" id="tab-wallet">

        <div class="balance-display">
            <span style="font-size:28px;">💰</span>
            <div>
                <div class="balance-label">Saldo atual</div>
                <div class="balance-amount">R$ <?= number_format((float)($wallet['balance_brl'] ?? 0), 2, ',', '.') ?></div>
            </div>
        </div>

        <!-- Manual credit form -->
        <div class="card" style="max-width:480px;">
            <div class="card-title">➕ Crédito manual</div>
            <form method="post" action="/dev/ai-billing/credit">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                <div class="grid-2">
                    <div class="field">
                        <label>Valor (R$)</label>
                        <input type="number" name="amount" min="0.01" step="0.01" placeholder="50.00" required />
                    </div>
                    <div class="field">
                        <label>Descrição</label>
                        <input type="text" name="description" value="Crédito manual" maxlength="200" />
                    </div>
                </div>
                <button class="btn" type="submit">Aplicar crédito</button>
            </form>
        </div>

        <?php if (($asaasMode ?? 'sandbox') === 'sandbox'): ?>
        <div class="card" style="max-width:480px;border-color:#fca5a5;">
            <div class="card-title" style="color:#dc2626;">🧹 Zerar ambiente sandbox</div>
            <p style="font-size:13px;color:#6b7280;margin-bottom:14px;">
                Zera o saldo, remove o cartão tokenizado e apaga todas as transações do ambiente sandbox.
                Útil para limpar os dados de teste antes de ir para produção.
            </p>
            <form method="post" action="/dev/ai-billing/reset-sandbox"
                  onsubmit="return confirm('Tem certeza? Isso vai zerar o saldo e apagar todas as transações do sandbox.')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                <button class="btn btn-danger" type="submit">Zerar sandbox</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card" style="max-width:480px;border-color:#fed7aa;">
            <div class="card-title" style="color:#c2410c;">⚠️ Limpar cobranças pendentes travadas</div>
            <p style="font-size:13px;color:#6b7280;margin-bottom:14px;">
                Remove registros <code>charge_pending</code> travados (webhook não chegou).
                Use se "Recarregar agora" não estiver criando novas cobranças.
            </p>
            <form method="post" action="/dev/ai-billing/clear-pending">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                <button class="btn" style="background:#ea580c;" type="submit">Limpar pendentes</button>
            </form>
        </div>

        <!-- Transaction history -->
        <?php if (!empty($transactions)): ?>
        <div class="card">
            <div class="card-title">📋 Histórico completo</div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Valor (R$)</th>
                            <th>Descrição</th>
                            <th>Saldo após</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx):
                            $txType = (string)($tx['type'] ?? '');
                            $txAmt  = (float)($tx['amount_brl'] ?? 0);
                            $isDebit = $txType === 'debit';
                            $amtFormatted = ($isDebit ? '−' : '+') . 'R$ ' . number_format($txAmt, 2, ',', '.');
                            $amtColor = $isDebit ? '#dc2626' : '#16a34a';
                            $badgeClass = match($txType) {
                                'debit' => 'badge-debit',
                                'credit' => 'badge-credit',
                                'charge_pending' => 'badge-pending',
                                default => 'badge-manual',
                            };
                            $typeLabels = [
                                'debit' => 'Débito', 'credit' => 'Crédito',
                                'charge_pending' => 'Pendente', 'manual_credit' => 'Manual',
                            ];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($tx['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($typeLabels[$txType] ?? $txType, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td style="font-weight:700;color:<?= $amtColor ?>;"><?= $amtFormatted ?></td>
                            <td><?= htmlspecialchars((string)($tx['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>R$ <?= number_format((float)($tx['balance_after_brl'] ?? 0), 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div style="font-size:13px;color:#9ca3af;padding:12px 0;">Nenhuma transação registrada ainda.</div>
        <?php endif; ?>

    </div>

</div><!-- .container -->

<script>
function switchTab(tab) {
    ['config', 'stats', 'wallet'].forEach(function(t) {
        document.getElementById('tab-' + t).classList.toggle('active', t === tab);
        document.getElementById('tab-btn-' + t).classList.toggle('active', t === tab);
    });
}

// Activate tab from URL hash
(function() {
    var hash = window.location.hash.replace('#', '');
    if (['config', 'stats', 'wallet'].indexOf(hash) !== -1) {
        switchTab(hash);
    }
})();
</script>

</body>
</html>
