<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Core\Container\Container;
use App\Services\System\SystemSettingsService;

final class WelcomeMailService
{
    public function __construct(private readonly Container $container) {}

    public function sendClinicWelcome(
        string $toEmail,
        string $ownerName,
        string $clinicName,
        string $password
    ): void {
        $settings = new SystemSettingsService($this->container);
        $siteName = trim((string)($settings->getText('seo.site_name') ?? 'LumiClinic'));
        $faviconUrl = trim((string)($settings->getText('seo.favicon_url') ?? ''));
        $logoUrl = $faviconUrl !== '' ? $faviconUrl : '';

        $baseUrl = getenv('APP_BASE_URL') ?: (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        );
        $baseUrl = rtrim((string)$baseUrl, '/');
        $loginUrl = $baseUrl . '/login';

        $firstName = explode(' ', trim($ownerName))[0];

        $html = $this->buildHtml(
            siteName: $siteName,
            logoUrl: $logoUrl,
            baseUrl: $baseUrl,
            loginUrl: $loginUrl,
            firstName: $firstName,
            ownerName: $ownerName,
            clinicName: $clinicName,
            email: $toEmail,
            password: $password
        );

        (new MailerService($this->container))->send(
            $toEmail,
            $ownerName,
            'Bem-vindo ao ' . $siteName . ' — sua conta está pronta!',
            $html
        );
    }

    private function buildHtml(
        string $siteName,
        string $logoUrl,
        string $baseUrl,
        string $loginUrl,
        string $firstName,
        string $ownerName,
        string $clinicName,
        string $email,
        string $password
    ): string {
        $e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        $logoHtml = $logoUrl !== ''
            ? '<img src="' . $e($logoUrl) . '" alt="' . $e($siteName) . '" style="height:40px;width:auto;display:block;" />'
            : '<span style="font-size:22px;font-weight:900;color:#fff;letter-spacing:-0.5px;">' . $e($siteName) . '</span>';

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Bem-vindo ao ' . $e($siteName) . '</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:32px 16px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">

      <!-- Header -->
      <tr><td style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);border-radius:16px 16px 0 0;padding:28px 32px;text-align:center;">
        ' . $logoHtml . '
        <div style="margin-top:12px;font-size:13px;color:rgba(255,255,255,.55);font-weight:500;">Sistema de gestão para clínicas</div>
      </td></tr>

      <!-- Body -->
      <tr><td style="background:#ffffff;padding:36px 32px;">
        <h1 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#1a1a2e;">Olá, ' . $e($firstName) . '! 👋</h1>
        <p style="margin:0 0 20px;font-size:15px;color:#4a4a6a;line-height:1.6;">
          Sua conta no <strong>' . $e($siteName) . '</strong> foi criada com sucesso. Estamos felizes em ter a <strong>' . $e($clinicName) . '</strong> com a gente!
        </p>

        <!-- Credentials box -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8f8fc;border:1px solid #e8e8f0;border-radius:12px;margin-bottom:24px;">
          <tr><td style="padding:20px 24px;">
            <div style="font-size:12px;font-weight:700;color:#8888aa;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:14px;">Seus dados de acesso</div>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:6px 0;font-size:13px;color:#8888aa;width:80px;">E-mail</td>
                <td style="padding:6px 0;font-size:14px;font-weight:700;color:#1a1a2e;">' . $e($email) . '</td>
              </tr>
              <tr>
                <td style="padding:6px 0;font-size:13px;color:#8888aa;">Senha</td>
                <td style="padding:6px 0;font-size:14px;font-weight:700;color:#1a1a2e;font-family:monospace;letter-spacing:1px;">' . $e($password) . '</td>
              </tr>
            </table>
            <div style="margin-top:12px;font-size:11px;color:#aaaacc;line-height:1.5;">
              🔒 Por segurança, recomendamos alterar sua senha após o primeiro acesso em <strong>Meu perfil</strong>.
            </div>
          </td></tr>
        </table>

        <!-- CTA -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
          <tr><td align="center">
            <a href="' . $e($loginUrl) . '" style="display:inline-block;background:linear-gradient(135deg,#eeb810,#d4a010);color:#1a1a2e;font-weight:800;font-size:15px;text-decoration:none;padding:14px 36px;border-radius:10px;letter-spacing:0.2px;">
              Acessar o sistema →
            </a>
          </td></tr>
        </table>

        <!-- Features -->
        <div style="border-top:1px solid #f0f0f6;padding-top:20px;">
          <div style="font-size:12px;font-weight:700;color:#8888aa;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px;">O que você pode fazer agora</div>
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="padding:5px 0;font-size:13px;color:#4a4a6a;">📅 Configurar sua agenda e horários de atendimento</td>
            </tr>
            <tr>
              <td style="padding:5px 0;font-size:13px;color:#4a4a6a;">👤 Cadastrar seus profissionais e serviços</td>
            </tr>
            <tr>
              <td style="padding:5px 0;font-size:13px;color:#4a4a6a;">🩺 Começar a cadastrar seus pacientes</td>
            </tr>
            <tr>
              <td style="padding:5px 0;font-size:13px;color:#4a4a6a;">💳 Gerenciar financeiro e assinatura</td>
            </tr>
          </table>
        </div>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f8f8fc;border-radius:0 0 16px 16px;padding:20px 32px;text-align:center;border-top:1px solid #e8e8f0;">
        <p style="margin:0 0 6px;font-size:12px;color:#aaaacc;">
          Este e-mail foi enviado para <strong>' . $e($email) . '</strong> porque você criou uma conta no ' . $e($siteName) . '.
        </p>
        <p style="margin:0;font-size:12px;color:#ccccdd;">
          <a href="' . $e($baseUrl) . '" style="color:#8888aa;text-decoration:none;">' . $e($siteName) . '</a>
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>';
    }
}
