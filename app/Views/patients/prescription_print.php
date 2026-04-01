<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Receita — <?= htmlspecialchars((string)($rx['title'] ?? 'Receita'), ENT_QUOTES, 'UTF-8') ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 13px; color: #111; background: #fff; padding: 32px; max-width: 700px; margin: 0 auto; }
  .header { border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 20px; }
  .header h1 { font-size: 18px; }
  .header .meta { font-size: 12px; color: #555; margin-top: 4px; }
  .body { white-space: pre-wrap; line-height: 1.7; font-size: 13px; min-height: 200px; }
  .footer { margin-top: 60px; border-top: 1px solid #ccc; padding-top: 12px; font-size: 12px; color: #555; display: flex; justify-content: space-between; }
  .signature { margin-top: 60px; text-align: center; }
  .signature .line { border-top: 1px solid #111; width: 260px; margin: 0 auto 6px; }
  .signature .name { font-size: 12px; }
  @media print {
    body { padding: 20px; }
    .no-print { display: none !important; }
  }
</style>
</head>
<body>
<div class="header">
    <h1><?= htmlspecialchars((string)($rx['title'] ?? 'Receita'), ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="meta">
        Paciente: <strong><?= htmlspecialchars((string)($rx['patient_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        &nbsp;|&nbsp;
        Data: <strong><?= htmlspecialchars((string)($rx['issued_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if (($rx['professional_name'] ?? '') !== ''): ?>
            &nbsp;|&nbsp; Profissional: <strong><?= htmlspecialchars((string)$rx['professional_name'], ENT_QUOTES, 'UTF-8') ?></strong>
        <?php endif; ?>
    </div>
</div>

<div class="body"><?= htmlspecialchars((string)($rx['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

<div class="signature">
    <div class="line"></div>
    <div class="name"><?= htmlspecialchars((string)($rx['professional_name'] ?? 'Profissional'), ENT_QUOTES, 'UTF-8') ?></div>
</div>

<div class="footer">
    <span>Emitido em: <?= htmlspecialchars((string)($rx['issued_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
    <span>LumiClinic</span>
</div>

<div class="no-print" style="margin-top:24px; text-align:center;">
    <button onclick="window.print()" style="padding:8px 20px; cursor:pointer; font-size:14px;">Imprimir</button>
    <button onclick="window.close()" style="padding:8px 20px; cursor:pointer; font-size:14px; margin-left:10px;">Fechar</button>
</div>
</body>
</html>
