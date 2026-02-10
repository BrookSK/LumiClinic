<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Core\Container\Container;
use App\Services\System\SystemSettingsService;
use PHPMailer\PHPMailer\PHPMailer;

final class MailerService
{
    public function __construct(private readonly Container $container) {}

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): void
    {
        $settings = new SystemSettingsService($this->container);
        $smtpHost = trim((string)($settings->getText('mail.smtp.host') ?? ''));
        $smtpPort = (int)trim((string)($settings->getText('mail.smtp.port') ?? '587'));
        $smtpUser = trim((string)($settings->getText('mail.smtp.username') ?? ''));
        $smtpPass = (string)($settings->getText('mail.smtp.password') ?? '');
        $smtpEnc = strtolower(trim((string)($settings->getText('mail.smtp.encryption') ?? 'tls')));

        $fromEmail = trim((string)($settings->getText('mail.from_address') ?? ''));
        $fromName = trim((string)($settings->getText('mail.from_name') ?? 'LumiClinic'));

        if ($fromEmail === '') {
            $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : 'localhost';
            $host = preg_replace('/[^a-zA-Z0-9\.-]/', '', $host) ?: 'localhost';
            $fromEmail = 'no-reply@' . $host;
        }

        $toEmail = trim($toEmail);
        if ($toEmail === '') {
            throw new \RuntimeException('Destinatário inválido.');
        }

        $toName = trim($toName);
        $subject = trim($subject);

        if ($smtpHost !== '') {
            $phpMailer = new PHPMailer(true);
            $phpMailer->isSMTP();
            $phpMailer->Host = $smtpHost;
            $phpMailer->Port = $smtpPort > 0 ? $smtpPort : 587;
            $phpMailer->SMTPAuth = $smtpUser !== '';
            if ($phpMailer->SMTPAuth) {
                $phpMailer->Username = $smtpUser;
                $phpMailer->Password = $smtpPass;
            }

            $phpMailer->SMTPSecure = match ($smtpEnc) {
                'ssl' => PHPMailer::ENCRYPTION_SMTPS,
                'tls' => PHPMailer::ENCRYPTION_STARTTLS,
                '', 'none' => '',
                default => PHPMailer::ENCRYPTION_STARTTLS,
            };

            $phpMailer->CharSet = 'UTF-8';
            $phpMailer->setFrom($fromEmail, $fromName);
            $phpMailer->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
            $phpMailer->isHTML(true);
            $phpMailer->Subject = $subject;
            $phpMailer->Body = $htmlBody;
            $phpMailer->send();
            return;
        }

        $encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8');
        $encodedToName = $toName !== '' ? mb_encode_mimeheader($toName, 'UTF-8') : '';
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');

        $toHeader = $encodedToName !== '' ? ($encodedToName . ' <' . $toEmail . '>') : $toEmail;
        $fromHeader = $encodedFromName . ' <' . $fromEmail . '>';

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . $fromHeader;
        $headers[] = 'Reply-To: ' . $fromEmail;

        $ok = mail($toHeader, $encodedSubject, $htmlBody, implode("\r\n", $headers));
        if (!$ok) {
            throw new \RuntimeException('Falha ao enviar e-mail (mail()).');
        }
    }
}
