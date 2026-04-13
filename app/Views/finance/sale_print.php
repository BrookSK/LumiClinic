<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Orçamento #<?= (int)$sale['id'] ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --gold: #eeb810; --gold-dk: #815901; --gold-md: #b5841e;
    --surface: #fffdf8; --text: #2a2a2a; --muted: rgba(42,42,42,.55);
    --border: rgba(17,24,39,.12);
    --font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
}
body { font-family: var(--font); font-size: 13px; color: var(--text); background: #e8e4dc; }
.page {
    width: 210mm; min-height: 297mm; margin: 20px auto; background: var(--surface);
    position: relative; overflow: hidden; box-shadow: 0 6px 32px rgba(0,0,0,.18);
    display: flex; flex-direction: column;
}
.page::before {
    content: ''; position: absolute; top: -70px; right: -70px;
    width: 240px; height: 240px; border-radius: 50%;
    background: rgba(238,184,16,.10); pointer-events: none; z-index: 0;
}
.header {
    padding: 26px 36px 18px; display: flex; align-items: center; gap: 16px;
    border-bottom: 2.5px solid var(--gold); position: relative; z-index: 1;
}
.header-logo { width: 60px; height: 60px; object-fit: contain; border-radius: 10px; flex-shrink: 0; }
.header-info { flex: 1; }
.header-clinic-name { font-size: 20px; font-weight: 800; color: var(--gold-dk); }
.header-date { text-align: right; font-size: 11px; color: var(--muted); line-height: 1.7; flex-shrink: 0; }
.body { padding: 30px 36px 100px; flex: 1; position: relative; z-index: 1; }
.section-title { font-size: 11px; font-weight: 700; color: var(--gold-md); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
.patient-block { margin-bottom: 20px; }
.patient-name { font-size: 15px; font-weight: 700; margin-bottom: 3px; }
.patient-meta { font-size: 12px; color: var(--muted); }
.divider { border: none; border-top: 1px solid var(--border); margin: 16px 0; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { text-align: left; padding: 8px 10px; border-bottom: 2px solid var(--gold); font-size: 11px; font-weight: 700; color: var(--gold-dk); }
td { padding: 8px 10px; border-bottom: 1px solid var(--border); }
.text-right { text-align: right; }
.total-row td { font-weight: 800; font-size: 15px; border-top: 2px solid var(--text); border-bottom: none; }
.discount-row td { font-size: 12px; color: #16a34a; }
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; }
.footer {
    position: absolute; bottom: 0; left: 0; right: 0; padding: 12px 36px;
    border-top: 1px solid rgba(238,184,16,.35); display: flex; justify-content: space-between;
    font-size: 11px; color: var(--gold-md); background: rgba(253,229,159,.15); z-index: 1;
}
.footer-col { line-height: 1.7; }
.footer-col--right { text-align: right; }
.no-print { width: 210mm; margin: 0 auto 28px; display: flex; gap: 12px; justify-content: center; padding: 16px 0; }
.btn { padding: 10px 32px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-print { background: linear-gradient(135deg, #fde59f, var(--gold-dk)); color: #fff; }
.btn-close { background: #e5e7eb; color: #374151; }
@media print { body { background: #fff; } .page { margin: 0; box-shadow: none; width: 100%; min-height: 100vh; } .no-print { display: none !important; } }
</style>
</head>
<body>
<?php
$svcMap  = []; foreach ($services  as $s) { $svcMap[(int)$s['id']]  = $s; }
$pkgMap  = []; foreach ($packages  as $p) { $pkgMap[(int)$p['id']]  = $p; }
$planMap = []; foreach ($plans     as $p) { $planMap[(int)$p['id']] = $p; }
$profMap = []; foreach ($professionals as $p) { $profMap[(int)$p['id']] = $p; }

$clinicName    = trim((string)($clinic['name'] ?? 'Clínica'));
$clinicPhone   = trim((string)($clinic['contact_phone'] ?? ''));
$clinicAddress = trim((string)($clinic['contact_address'] ?? ''));
$clinicEmail   = trim((string)($clinic['contact_email'] ?? ''));

$patientName = is_array($patient) ? trim((string)($patient['name'] ?? '')) : '';
$patientCpf  = is_array($patient) ? trim((string)($patient['cpf'] ?? '')) : '';
$patientPhone = is_array($patient) ? trim((string)($patient['phone'] ?? '')) : '';

$createdAt = (string)($sale['created_at'] ?? '');
$dateFmt = '';
try { $dateFmt = (new \DateTimeImmutable($createdAt))->format('d/m/Y'); } catch (\Throwable $e) { $dateFmt = $createdAt; }

$budgetStatusLabel = ['draft'=>'Rascunho','sent'=>'Enviado','approved'=>'Aprovado','standby'=>'Em espera','rejected'=>'Recusado'];
$bs = (string)($sale['budget_status'] ?? 'draft');
$isPaid = (string)$sale['status'] === 'paid';
?>

<div class="page">
    <div class="header">
        <img src="/icone_1.png" alt="<?= htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') ?>" class="header-logo" />
        <div class="header-info">
            <div class="header-clinic-name"><?= htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="header-date">
            <div>Orçamento #<?= (int)$sale['id'] ?></div>
            <div><?= htmlspecialchars($dateFmt, ENT_QUOTES, 'UTF-8') ?></div>
            <div>
                <span class="status-badge" style="background:<?= $isPaid ? 'rgba(22,163,74,.15);color:#16a34a' : 'rgba(238,184,16,.2);color:#815901' ?>;">
                    <?= $isPaid ? 'PAGO' : htmlspecialchars($budgetStatusLabel[$bs] ?? $bs, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>
    </div>

    <div class="body">
        <div class="patient-block">
            <div class="section-title">Paciente</div>
            <div class="patient-name"><?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($patientCpf !== ''): ?><div class="patient-meta">CPF: <?= htmlspecialchars($patientCpf, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if ($patientPhone !== ''): ?><div class="patient-meta">Tel: <?= htmlspecialchars($patientPhone, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>

        <hr class="divider" />

        <div class="section-title">Itens do orçamento</div>
        <table>
            <thead><tr><th>Serviço</th><th>Profissional</th><th class="text-right">Qtd</th><th class="text-right">Valor unit.</th><th class="text-right">Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it):
                $refName = '';
                $t = (string)$it['type']; $rid = (int)$it['reference_id'];
                if ($t === 'procedure' && isset($svcMap[$rid])) $refName = (string)$svcMap[$rid]['name'];
                elseif ($t === 'package' && isset($pkgMap[$rid])) $refName = (string)$pkgMap[$rid]['name'];
                elseif ($t === 'subscription' && isset($planMap[$rid])) $refName = (string)$planMap[$rid]['name'];
                $pid = (int)($it['professional_id'] ?? 0);
                $pname = $pid > 0 && isset($profMap[$pid]) ? (string)$profMap[$pid]['name'] : '—';
            ?>
                <tr>
                    <td><?= htmlspecialchars($refName !== '' ? $refName : '#'.$rid, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-right"><?= (int)$it['quantity'] ?></td>
                    <td class="text-right">R$ <?= number_format((float)$it['unit_price'], 2, ',', '.') ?></td>
                    <td class="text-right">R$ <?= number_format((float)$it['subtotal'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ((float)$sale['desconto'] > 0): ?>
                <tr class="discount-row"><td colspan="4" class="text-right">Desconto</td><td class="text-right">-R$ <?= number_format((float)$sale['desconto'], 2, ',', '.') ?></td></tr>
            <?php endif; ?>
            <tr class="total-row"><td colspan="4" class="text-right">Total</td><td class="text-right">R$ <?= number_format((float)$sale['total_liquido'], 2, ',', '.') ?></td></tr>
            </tbody>
        </table>

        <?php if (!empty($payments)): ?>
        <div style="margin-top:24px;">
            <div class="section-title">Pagamentos</div>
            <table>
                <thead><tr><th>Método</th><th>Data</th><th>Status</th><th class="text-right">Valor</th></tr></thead>
                <tbody>
                <?php
                $pmLabels = ['pix'=>'PIX','card'=>'Cartão','credit_card'=>'Crédito','debit_card'=>'Débito','cash'=>'Dinheiro','boleto'=>'Boleto'];
                $psLabels = ['pending'=>'Pendente','paid'=>'Pago','refunded'=>'Estornado'];
                foreach ($payments as $pp):
                    $pm = (string)($pp['method'] ?? '');
                    $ps = (string)($pp['status'] ?? '');
                    $pAt = (string)($pp['paid_at'] ?? '');
                    $pFmt = '';
                    try { $pFmt = $pAt !== '' ? (new \DateTimeImmutable($pAt))->format('d/m/Y') : ''; } catch (\Throwable $e) {}
                ?>
                <tr>
                    <td><?= htmlspecialchars($pmLabels[$pm] ?? $pm, ENT_QUOTES, 'UTF-8') ?><?php if (trim((string)($pp['gateway_ref'] ?? '')) !== ''): ?> <span style="font-size:11px;color:var(--muted);">(<?= htmlspecialchars((string)$pp['gateway_ref'], ENT_QUOTES, 'UTF-8') ?>)</span><?php endif; ?></td>
                    <td><?= htmlspecialchars($pFmt, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($psLabels[$ps] ?? $ps, ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-right">R$ <?= number_format((float)$pp['amount'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <div class="footer-col">
            <?php if ($clinicPhone !== ''): ?><div>Tel: <?= htmlspecialchars($clinicPhone, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if ($clinicEmail !== ''): ?><div><?= htmlspecialchars($clinicEmail, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>
        <div class="footer-col footer-col--right">
            <?php if ($clinicAddress !== ''): ?><div><?= htmlspecialchars($clinicAddress, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <div><?= htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</div>

<div class="no-print">
    <button class="btn btn-print" onclick="window.print()">Imprimir</button>
    <button class="btn btn-close" onclick="window.close()">Fechar</button>
</div>
</body>
</html>
