<?php

declare(strict_types=1);

namespace App\Services\Anamnesis;

use App\Core\Container\Container;
use App\Repositories\AnamnesisTemplateRepository;
use App\Repositories\ClinicRepository;
use App\Repositories\PatientRepository;
use App\Services\Auth\AuthService;
use App\Services\Mail\MailerService;
use App\Services\Whatsapp\EvolutionClient;
use App\Services\Whatsapp\WhatsappTemplateRenderer;
use App\Repositories\WhatsappTemplateRepository;

/**
 * Envia o link de preenchimento de anamnese via WhatsApp, e-mail ou disponibiliza no portal.
 */
final class AnamnesisLinkSendService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @param string $channel 'whatsapp' | 'email' | 'portal'
     */
    public function send(int $patientId, int $templateId, string $channel, string $waTemplateCode, string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        // Gerar token de acesso público
        $token = bin2hex(random_bytes(24));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        // Get template info for snapshot
        $tplRepo = new AnamnesisTemplateRepository($pdo);
        $template = $tplRepo->findById($clinicId, $templateId);
        $templateName = $template !== null ? (string)($template['name'] ?? '') : '';
        $templateUpdatedAt = $template !== null ? (string)($template['updated_at'] ?? '') : null;
        $fieldsJson = null; // fields are stored separately in anamnesis_template_fields

        // Create an empty anamnesis_response so it shows as "pending" in the patient portal
        $responseRepo = new \App\Repositories\AnamnesisResponseRepository($pdo);
        $auth2 = new AuthService($this->container);
        $responseId = $responseRepo->create(
            $clinicId,
            $patientId,
            $templateId,
            $templateName !== '' ? $templateName : null,
            $templateUpdatedAt !== '' ? $templateUpdatedAt : null,
            $fieldsJson !== '' ? $fieldsJson : null,
            null,
            '{}', // empty answers — pending
            $auth2->userId()
        );

        // Salvar request de anamnese
        $stmt = $pdo->prepare("
            INSERT INTO appointment_anamnesis_requests (
                clinic_id, appointment_id, patient_id, template_id,
                token_hash, token_encrypted, expires_at, response_id, created_at
            ) VALUES (
                :clinic_id, NULL, :patient_id, :template_id,
                :token_hash, :token_encrypted, :expires_at, :response_id, NOW()
            )
        ");
        $tokenHash = hash('sha256', $token);
        $stmt->execute([
            'clinic_id'       => $clinicId,
            'patient_id'      => $patientId,
            'template_id'     => $templateId,
            'token_hash'      => $tokenHash,
            'token_encrypted' => $token,
            'expires_at'      => $expiresAt,
            'response_id'     => $responseId,
        ]);

        $cfg = $this->container->has('config') ? $this->container->get('config') : [];
        $baseUrl = is_array($cfg) && isset($cfg['app']['base_url']) ? rtrim((string)$cfg['app']['base_url'], '/') : '';
        if ($baseUrl === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        $link = $baseUrl . '/a/anamnese?token=' . urlencode($token);

        $clinic = (new ClinicRepository($pdo))->findById($clinicId);
        $clinicName = $clinic !== null ? (string)($clinic['name'] ?? 'LumiClinic') : 'LumiClinic';
        $patientName = (string)($patient['name'] ?? '');

        if ($channel === 'whatsapp') {
            return $this->sendWhatsapp($clinicId, $patient, $link, $waTemplateCode, $clinicName);
        }

        if ($channel === 'email') {
            return $this->sendEmail($patient, $link, $clinicName, $patientName);
        }

        // portal — só gera o link
        return ['ok' => true, 'message' => 'Link gerado. O paciente pode acessar pelo portal.', 'link' => $link];
    }

    private function sendWhatsapp(int $clinicId, array $patient, string $link, string $waTemplateCode, string $clinicName): array
    {
        $phone = trim((string)($patient['phone'] ?? ''));
        if ($phone === '') {
            throw new \RuntimeException('Paciente sem telefone cadastrado.');
        }

        $pdo = $this->container->get(\PDO::class);
        $tplRepo = new WhatsappTemplateRepository($pdo);

        // Tentar usar template configurado, senão mensagem padrão
        $message = '';
        if ($waTemplateCode !== '') {
            $tpl = $tplRepo->findByCode($clinicId, $waTemplateCode);
            if ($tpl !== null && (string)($tpl['status'] ?? 'active') === 'active') {
                $message = (new WhatsappTemplateRenderer())->render((string)($tpl['body'] ?? ''), [
                    'patient_name' => (string)($patient['name'] ?? ''),
                    'clinic_name'  => $clinicName,
                    'anamnesis_url' => $link,
                    'link'         => $link,
                ]);
            }
        }

        if ($message === '') {
            $message = 'Olá, ' . (string)($patient['name'] ?? '') . '! '
                . $clinicName . ' solicita que você preencha sua anamnese antes da consulta. '
                . 'Acesse: ' . $link;
        }

        (new EvolutionClient($this->container))->sendText($phone, $message);

        return ['ok' => true, 'message' => 'Mensagem enviada via WhatsApp.'];
    }

    private function sendEmail(array $patient, string $link, string $clinicName, string $patientName): array
    {
        $email = trim((string)($patient['email'] ?? ''));
        if ($email === '') {
            throw new \RuntimeException('Paciente sem e-mail cadastrado.');
        }

        $safeClinic  = htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8');
        $safeName    = htmlspecialchars($patientName !== '' ? $patientName : $email, ENT_QUOTES, 'UTF-8');
        $safeUrl     = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $subject = 'Preencha sua anamnese — ' . $clinicName;

        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"/></head>'
            . '<body style="margin:0;padding:0;background:#f4ecd4;font-family:ui-sans-serif,system-ui,-apple-system,\'Segoe UI\',Roboto,Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4ecd4;padding:32px 0;">'
            . '<tr><td align="center">'
            . '<table width="560" cellpadding="0" cellspacing="0" style="background:#fffdf8;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10);max-width:560px;width:100%;">'
            . '<tr><td style="background:linear-gradient(135deg,#fde59f,#815901);padding:28px 32px;text-align:center;">'
            . '<div style="font-size:22px;font-weight:800;color:#fff;">' . $safeClinic . '</div>'
            . '<div style="font-size:13px;color:rgba(255,255,255,.85);margin-top:4px;">Anamnese</div>'
            . '</td></tr>'
            . '<tr><td style="padding:32px;">'
            . '<p style="font-size:16px;font-weight:700;color:#2a2a2a;margin:0 0 12px;">Olá, ' . $safeName . '!</p>'
            . '<p style="font-size:14px;color:#4b5563;line-height:1.7;margin:0 0 20px;">'
            . $safeClinic . ' solicita que você preencha sua anamnese antes da consulta. '
            . 'Clique no botão abaixo para acessar o formulário.</p>'
            . '<div style="text-align:center;margin-bottom:24px;">'
            . '<a href="' . $safeUrl . '" style="display:inline-block;background:linear-gradient(135deg,#fde59f,#815901);color:#fff;font-weight:700;font-size:15px;text-decoration:none;padding:14px 32px;border-radius:8px;">Preencher anamnese</a>'
            . '</div>'
            . '<p style="font-size:12px;color:#9ca3af;">Se o botão não funcionar, copie e cole: <span style="color:#b5841e;word-break:break-all;">' . $safeUrl . '</span></p>'
            . '<p style="font-size:12px;color:#9ca3af;margin-top:8px;">Este link expira em 7 dias.</p>'
            . '</td></tr>'
            . '<tr><td style="background:#f4ecd4;padding:16px 32px;text-align:center;border-top:1px solid rgba(238,184,16,.3);">'
            . '<div style="font-size:12px;color:#92400e;">' . $safeClinic . '</div>'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';

        (new MailerService($this->container))->send($email, $patientName !== '' ? $patientName : $email, $subject, $html);

        return ['ok' => true, 'message' => 'E-mail enviado para ' . $email . '.'];
    }
}
