<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientPasswordResetRepository;
use App\Repositories\PatientUserRepository;
use App\Services\Auth\AuthService;
use App\Services\Mail\MailerService;
use Throwable;

final class PatientPortalAccessService
{
    public function __construct(private readonly Container $container) {}

    public function buildResetUrl(string $token): string
    {
        $token = trim($token);
        $cfg = $this->container->has('config') ? $this->container->get('config') : [];
        $baseUrl = is_array($cfg) && isset($cfg['app']) && is_array($cfg['app'])
            ? (string)($cfg['app']['base_url'] ?? '')
            : '';
        $baseUrl = rtrim($baseUrl, '/');

        if ($baseUrl === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . (string)$_SERVER['HTTP_HOST'];
        }

        return $baseUrl . '/portal/reset?token=' . urlencode($token);
    }

    /** @param array{name:string, clinic_name:string, email:string, reset_url:string} $data */
    private function sendWelcomeEmail(array $data): void
    {
        $toEmail = trim($data['email']);
        if ($toEmail === '') {
            return;
        }

        $toName = trim($data['name']);
        $clinicName = trim($data['clinic_name']);
        $resetUrl = trim($data['reset_url']);

        $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
        $safeClinic = htmlspecialchars($clinicName !== '' ? $clinicName : 'sua clínica', ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        $subject = 'Acesso ao Portal do Paciente - LumiClinic';
        $html = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#111827">'
            . '<p>Olá, ' . $safeName . '.</p>'
            . '<p>Seu acesso ao <strong>Portal do Paciente</strong> da clínica <strong>' . $safeClinic . '</strong> foi criado.</p>'
            . '<p><strong>Seu e-mail de login é:</strong> ' . htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Para definir sua senha e acessar o portal, use o link abaixo:</p>'
            . '<p><a href="' . $safeUrl . '">Definir minha senha</a></p>'
            . '<p style="color:rgba(17,24,39,0.65);font-size:12px;">Se você não solicitou este acesso, ignore este e-mail.</p>'
            . '</div>';

        (new MailerService($this->container))->send($toEmail, $toName !== '' ? $toName : $toEmail, $subject, $html);
    }

    /** @return array{patient_user:?array<string,mixed>} */
    public function getAccess(int $patientId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new PatientUserRepository($this->container->get(\PDO::class));
        $patientUser = $repo->findByPatientId($clinicId, $patientId);

        return ['patient_user' => $patientUser];
    }

    /** @return array{reset_token:string,reset_url:string} */
    public function ensureAccessAndCreateReset(int $patientId, string $email, string $ip, bool $notifyByEmail = false): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            throw new \RuntimeException('E-mail do paciente é obrigatório para criar acesso.');
        }

        $pdo = $this->container->get(\PDO::class);
        $patients = new PatientRepository($pdo);
        $patient = $patients->findById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente não encontrado.');
        }

        $users = new PatientUserRepository($pdo);

        $pdo->beginTransaction();
        try {
            $existing = $users->findByPatientIdForUpdate($clinicId, $patientId);
            if ($existing === null) {
                $patientUserId = $users->create($clinicId, $patientId, $email, password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT));
            } else {
                $patientUserId = (int)$existing['id'];
                $users->updateEmail($clinicId, $patientUserId, $email);
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60);

            $resets = new PatientPasswordResetRepository($pdo);
            $resets->create($clinicId, $patientUserId, $tokenHash, $expiresAt, $ip);

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'patients.portal_access.ensure', ['patient_id' => $patientId, 'patient_user_id' => $patientUserId], $ip);

            $pdo->commit();

            $resetUrl = $this->buildResetUrl($token);
            if ($notifyByEmail) {
                try {
                    $clinic = (new ClinicRepository($pdo))->findById($clinicId);
                    $clinicName = $clinic !== null ? (string)($clinic['name'] ?? '') : '';
                    $this->sendWelcomeEmail([
                        'name' => (string)($patient['name'] ?? ''),
                        'clinic_name' => $clinicName,
                        'email' => $email,
                        'reset_url' => $resetUrl,
                    ]);
                } catch (Throwable $e) {
                    // Não bloqueia o fluxo por falha de e-mail.
                }
            }

            return ['reset_token' => $token, 'reset_url' => $resetUrl];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
