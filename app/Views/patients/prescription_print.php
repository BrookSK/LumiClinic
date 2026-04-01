<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= htmlspecialchars((string)($rx['title'] ?? 'Receita'), ENT_QUOTES, 'UTF-8') ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Georgia', 'Times New Roman', serif;
    font-size: 13px;
    color: #1a1a1a;
    background: #f0f0f0;
}

.page {
    width: 210mm;
    min-height: 297mm;
    margin: 20px auto;
    background: #fff;
    padding: 0;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.15);
}

/* Decoração de fundo — círculos suaves nos cantos */
.page::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(180,120,120,.08);
    pointer-events: none;
}
.page::after {
    content: '';
    position: absolute;
    bottom: -80px; right: -40px;
    width: 280px; height: 280px;
    border-radius: 50%;
    background: rgba(180,120,120,.06);
    pointer-events: none;
}

/* ── CABEÇALHO ── */
.header {
    padding: 28px 36px 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    border-bottom: 2px solid #c8a0a0;
}

.header-logo {
    width: 64px;
    height: 64px;
    object-fit: contain;
    flex-shrink: 0;
}

.header-logo-placeholder {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #c8a0a0, #e8c8c8);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 22px;
    color: #fff;
    font-weight: 700;
}

.header-info {
    flex: 1;
}

.header-clinic-name {
    font-size: 22px;
    font-weight: 700;
    color: #8b4a4a;
    letter-spacing: 0.3px;
    font-style: italic;
}

.header-specialty {
    font-size: 12px;
    color: #a06060;
    margin-top: 2px;
    letter-spacing: 0.5px;
}

/* ── CORPO ── */
.body {
    padding: 32px 36px;
    min-height: 180mm;
}

/* Dados do paciente */
.patient-block {
    margin-bottom: 28px;
}

.patient-name {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 4px;
}

.patient-meta {
    font-size: 12px;
    color: #555;
}

/* Separador */
.divider {
    border: none;
    border-top: 1px solid #ddd;
    margin: 20px 0;
}

/* Tipo de receita */
.rx-type {
    font-size: 13px;
    font-weight: 600;
    color: #555;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* Conteúdo */
.rx-body {
    font-size: 14px;
    line-height: 1.9;
    white-space: pre-wrap;
    color: #1a1a1a;
    min-height: 80px;
}

/* ── ASSINATURA ── */
.signature-area {
    margin-top: 48px;
    text-align: center;
}

.signature-line {
    width: 240px;
    border-top: 1px solid #1a1a1a;
    margin: 0 auto 8px;
}

.signature-name {
    font-size: 13px;
    font-weight: 600;
    color: #1a1a1a;
}

.signature-detail {
    font-size: 11px;
    color: #666;
    margin-top: 2px;
}

/* ── RODAPÉ ── */
.footer {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: 14px 36px;
    border-top: 1px solid #e0c8c8;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    font-size: 11px;
    color: #a06060;
    background: rgba(248,240,240,.6);
}

.footer-left {
    line-height: 1.6;
}

.footer-right {
    text-align: right;
    line-height: 1.6;
}

/* ── BOTÕES (não imprime) ── */
.no-print {
    width: 210mm;
    margin: 0 auto 24px;
    display: flex;
    gap: 12px;
    justify-content: center;
    padding: 16px 0;
}

.btn-print, .btn-close {
    padding: 10px 28px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    font-family: Arial, sans-serif;
}

.btn-print {
    background: #8b4a4a;
    color: #fff;
}

.btn-close {
    background: #e5e7eb;
    color: #374151;
}

@media print {
    body { background: #fff; }
    .page { margin: 0; box-shadow: none; width: 100%; min-height: 100vh; }
    .no-print { display: none !important; }
}
</style>
</head>
<body>

<?php
$clinicName    = trim((string)($clinic['name'] ?? 'LumiClinic'));
$clinicPhone   = trim((string)($clinic['contact_phone'] ?? ''));
$clinicAddress = trim((string)($clinic['contact_address'] ?? ''));
$clinicEmail   = trim((string)($clinic['contact_email'] ?? ''));
$clinicInitial = mb_strtoupper(mb_substr($clinicName, 0, 1, 'UTF-8'), 'UTF-8');

$patientName   = trim((string)($rx['patient_name'] ?? ''));
$profName      = trim((string)($rx['professional_name'] ?? ''));
$profSpecialty = trim((string)($professional_specialty ?? ''));
$issuedAt      = trim((string)($rx['issued_at'] ?? ''));
$rxTitle       = trim((string)($rx['title'] ?? 'Receita'));
$rxBody        = trim((string)($rx['body'] ?? ''));

// Formatar data
$issuedFmt = $issuedAt;
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $issuedAt, $m)) {
    $issuedFmt = $m[3] . '/' . $m[2] . '/' . $m[1];
}
?>

<div class="page">

    <!-- Cabeçalho -->
    <div class="header">
        <div class="header-logo-placeholder"><?= htmlspecialchars($clinicInitial, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="header-info">
            <div class="header-clinic-name"><?= htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($profSpecialty !== ''): ?>
                <div class="header-specialty"><?= htmlspecialchars($profSpecialty, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div style="text-align:right; font-size:11px; color:#a06060; line-height:1.7;">
            <div><?= htmlspecialchars($issuedFmt, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($clinicPhone !== ''): ?>
                <div><?= htmlspecialchars($clinicPhone, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Corpo -->
    <div class="body">

        <!-- Dados do paciente -->
        <div class="patient-block">
            <div class="patient-name">Paciente: <?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($clinicAddress !== ''): ?>
                <div class="patient-meta"><?= htmlspecialchars($clinicAddress, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <hr class="divider" />

        <!-- Tipo -->
        <div class="rx-type"><?= htmlspecialchars($rxTitle, ENT_QUOTES, 'UTF-8') ?></div>

        <!-- Conteúdo -->
        <div class="rx-body"><?= htmlspecialchars($rxBody, ENT_QUOTES, 'UTF-8') ?></div>

        <!-- Assinatura -->
        <div class="signature-area">
            <div class="signature-line"></div>
            <div class="signature-name"><?= htmlspecialchars($profName !== '' ? $profName : $clinicName, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($profSpecialty !== ''): ?>
                <div class="signature-detail"><?= htmlspecialchars($profSpecialty, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Rodapé -->
    <div class="footer">
        <div class="footer-left">
            <?php if ($clinicPhone !== ''): ?><div>Tel: <?= htmlspecialchars($clinicPhone, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if ($clinicEmail !== ''): ?><div><?= htmlspecialchars($clinicEmail, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        </div>
        <div class="footer-right">
            <?php if ($clinicAddress !== ''): ?><div><?= htmlspecialchars($clinicAddress, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <div><?= htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>

</div>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">Imprimir</button>
    <button class="btn-close" onclick="window.close()">Fechar</button>
</div>

</body>
</html>
