<?php

declare(strict_types=1);

namespace App\Services\Mail;

/**
 * Layout HTML padrao de e-mails (mesmo visual do e-mail de boas-vindas).
 */
final class EmailLayout
{
    /**
     * @param string $brandName  Nome exibido no cabecalho (ex.: nome da clinica)
     * @param string $title      Titulo do documento HTML
     * @param string $contentHtml HTML do corpo (ja escapado pelo chamador)
     * @param string $footerHtml  HTML do rodape (ja escapado pelo chamador)
     */
    public static function render(string $brandName, string $title, string $contentHtml, string $footerHtml): string
    {
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>' . $e($title) . '</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:32px 16px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
      <tr><td style="background:#1a1a2e;border-radius:16px 16px 0 0;padding:28px 32px;text-align:center;">
        <span style="font-size:22px;font-weight:900;color:#ffffff;letter-spacing:-0.5px;">' . $e($brandName) . '</span>
      </td></tr>
      <tr><td style="background:#ffffff;padding:36px 32px;">
        ' . $contentHtml . '
      </td></tr>
      <tr><td style="background:#f8f8fc;border-radius:0 0 16px 16px;padding:20px 32px;text-align:center;border-top:1px solid #e8e8f0;">
        <p style="margin:0;font-size:12px;color:#aaaacc;">' . $footerHtml . '</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>';
    }
}
