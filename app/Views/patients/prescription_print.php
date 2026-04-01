<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= htmlspecialchars((string)($rx['title'] ?? 'Receita'), ENT_QUOTES, 'UTF-8') ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --gold:    #eeb810;
    --gold-dk: #815901;
    --gold-md: #b5841e;
    --surface: #fffdf8;
    --text:    #2a2a2a;
    --muted:   rgba(42,42,42,.55);
    --border:  rgba(17,24,39,.12);
    --font: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", sans-serif;
}

body {
    font-family: var(--font);
    font-size: 13px;
    color: var(--text);
    background: #e8e4dc;
}

/* ── PÁGINA A4 ── */
.page {
    width: 210mm;
    min-height: 297mm;
    margin: 20px auto;
    background: var(--surface);
    position: relative;
    overflow: hidden;
    box-shadow: 0 6px 32px rgba(0,0,0,.18);
    display: flex;
    flex-direction: column;
}

/* Decoração de fundo — círculos dourados suaves */
.page::before {
    content: '';
    position: absolute;
    top: -70px; right: -70px;
    width: 240px; height: 240px;
    border-radius: 50%;
    background: rgba(238,184,16,.10);
    pointer-events: none;
    z-index: 0;
}
.page::after {
    content: '';
    position: absolute;
    bottom: -90px; right: -50px;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: rgba(238,184,16,.07);
    pointer-events: none;
    z-index: 0;
}

/* ── CABEÇALHO ── */
.header {
    padding: 26px 36px 18px;
    display: flex;
    align-items: center;
    gap: 16px;
    border-bottom: 2.5px solid var(--gold);
    position: relative;
    z-index: 1;
}

.header-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
    border-radius: 10px;
    flex-shrink: 0;
}

.header-info {
    flex: 1;
}

.header-clinic-name {
    font-size: 20px;
    font-weight: 800;
    color: var(--gold-dk);
    letter-spacing: 0.2px;
}

.header-specialty {
    font-size: 12px;
    color: var(--gold-md);
    margin-top: 3px;
    font-weight: 500;
}

.header-date {
    text-align: right;
    font-size: 11px;
    color: var(--muted);
    line-height: 1.7;
    flex-shrink: 0;
}

/* ── CORPO ── */
.body {
    padding: 30px 36px 100px;
    flex: 1;
    position: relative;
    z-index: 1;
}

.patient-block {
    margin-bottom: 24px;
}

.patient-name {
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 3px;
}

.patient-meta {
    font-size: 12px;
    color: var(--muted);
}

.divider {
    border: none;
    border-top: 1px solid var(--border);
    margin: 18px 0;
}

.rx-type {
    font-size: 11px;
    font-weight: 700;
    color: var(--gold-md);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 18px;
}

.rx-body {
    font-size: 14px;
    line-height: 1.9;
    white-space: pre-wrap;
    color: var(--text);
    min-height: 80px;
}

/* ── ASSINATURA ── */
.signature-area {
    margin-top: 52px;
    text-align: center;
}

.signature-line {
    width: 220px;
    border-top: 1.5px solid var(--text);
    margin: 0 auto 8px;
}

.signature-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--text);
}

.signature-detail {
    font-size: 11px;
    color: var(--muted);
    margin-top: 2px;
}

/* ── RODAPÉ ── */
.footer {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: 12px 36px;
    border-top: 1px solid rgba(238,184,16,.35);
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    font-size: 11px;
    color: var(--gold-md);
    background: rgba(253,229,159,.15);
    z-index: 1;
}

.footer-col { line-height: 1.7; }
.footer-col--right { text-align: right; }

/* ── BOTÕES ── */
.no-print {
    width: 210mm;
    margin: 0 auto 28px;
    display: flex;
    gap: 12px;
    justify-content: center;
    padding: 16px 0;
}

.btn {
    padding: 10px 32px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-family: var(--font);
    font-weight: 600;
    cursor: pointer;
    transition: opacity .15s;
}
.btn:hover { opacity: .88; }

.btn-print {
    background: linear-gradient(135deg, #fde59f, var(--gold-dk));
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

$patientName   = trim((string)($rx['patient_name'] ?? ''));
$profName      = trim((string)($rx['professional_name'] ?? ''));
$profSpecialty = trim((string)($professional_specialty ?? ''));
$issuedAt      = trim((string)($rx['issued_at'] ?? ''));
$rxTitle       = trim((string)($rx['title'] ?? 'Receita'));
$rxBody        = trim((string)($rx['body'] ?? ''));

// Formatar data dd/mm/aaaa
$issuedFmt = $issuedAt;
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $issuedAt, $m)) {
    $issuedFmt = $m[3] . '/' . $m[2] . '/' . $m[1];
}
?>

<div class="page">

    <!-- Cabeçalho -->
    <div class="header">
        <img src="/icone_1.png" alt="<?= htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') ?>" class="header-logo" />
        <div class="header-info">
            <div class="header-clinic-name"><?= htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($profSpecialty !== ''): ?>
                <div class="header-specialty"><?= htmlspecialchars($profSpecialty, ENT_QUOTES, 'UTF-8') ?></div>
            <?php elseif ($profName !== ''): ?>
                <div class="header-specialty"><?= htmlspecialchars($profName, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="header-date">
            <div><?= htmlspecialchars($issuedFmt, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($clinicPhone !== ''): ?>
                <div><?= htmlspecialchars($clinicPhone, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Corpo -->
    <div class="body">

        <!-- Paciente -->
        <div class="patient-block">
            <div class="patient-name"><?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?></div>
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

    <!-- Rodapé — dados da clínica/profissional -->
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
