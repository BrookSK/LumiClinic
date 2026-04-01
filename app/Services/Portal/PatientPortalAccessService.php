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

        $safeName    = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
        $safeClinic  = htmlspecialchars($clinicName !== '' ? $clinicName : 'sua clínica', ENT_QUOTES, 'UTF-8');
        $safeUrl     = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $safeEmail   = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');

        $subject = 'Seu acesso ao Portal do Paciente — ' . ($clinicName !== '' ? $clinicName : 'LumiClinic');

        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"/></head><body style="margin:0;padding:0;background:#f4ecd4;font-family:ui-sans-serif,system-ui,-apple-system,\'Segoe UI\',Roboto,Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4ecd4;padding:32px 0;">'
            . '<tr><td align="center">'
            . '<table width="560" cellpadding="0" cellspacing="0" style="background:#fffdf8;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10);max-width:560px;width:100%;">'

            // Header
            . '<tr><td style="background:linear-gradient(135deg,#fde59f,#815901);padding:28px 32px;text-align:center;">'
            . '<div style="font-size:22px;font-weight:800;color:#fff;letter-spacing:0.3px;">' . $safeClinic . '</div>'
            . '<div style="font-size:13px;color:rgba(255,255,255,.85);margin-top:4px;">Portal do Paciente</div>'
            . '</td></tr>'

            // Body
            . '<tr><td style="padding:32px;">'
            . '<p style="font-size:16px;font-weight:700;color:#2a2a2a;margin:0 0 12px;">Olá, ' . $safeName . '!</p>'
            . '<p style="font-size:14px;color:#4b5563;line-height:1.7;margin:0 0 20px;">Seu acesso ao <strong>Portal do Paciente</strong> foi criado. Pelo portal você pode acompanhar seus agendamentos, documentos e muito mais.</p>'

            // Credentials box
            . '<div style="background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:16px 20px;margin-bottom:24px;">'
            . '<div style="font-size:12px;color:#92400e;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Seus dados de acesso</div>'
            . '<div style="font-size:14px;color:#2a2a2a;margin-bottom:4px;"><strong>E-mail:</strong> ' . $safeEmail . '</div>'
            . '<div style="font-size:13px;color:#6b7280;">Você precisará definir sua senha pelo link abaixo.</div>'
            . '</div>'

            // CTA button
            . '<div style="text-align:center;margin-bottom:24px;">'
            . '<a href="' . $safeUrl . '" style="display:inline-block;background:linear-gradient(135deg,#fde59f,#815901);color:#fff;font-weight:700;font-size:15px;text-decoration:none;padding:14px 32px;border-radius:8px;">Definir minha senha e acessar</a>'
            . '</div>'

            . '<p style="font-size:12px;color:#9ca3af;line-height:1.6;margin:0;">Se o botão não funcionar, copie e cole este link no navegador:<br/><span style="color:#b5841e;word-break:break-all;">' . $safeUrl . '</span></p>'
            . '<p style="font-size:12px;color:#9ca3af;margin-top:12px;">Este link expira em <strong>1 hora</strong>. Se você não solicitou este acesso, ignore este e-mail.</p>'
            . '</td></tr>'

            // Footer
            . '<tr><td style="background:#f4ecd4;padding:16px 32px;text-align:center;border-top:1px solid rgba(238,184,16,.3);">'
            . '<div style="font-size:12px;color:#92400e;">' . $safeClinic . ' · Portal do Paciente</div>'
            . '</td></tr>'

            . '</table>'
            . '</td></tr></table>'
            . '</body></html>';

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

    /**
     * Cria acesso ao portal com senha definida na hora.
     * Envia e-mail com login e senha em texto claro + aviso para trocar no primeiro acesso.
     */
    public function createWithPassword(int $patientId, string $email, string $plainPassword, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            throw new \RuntimeException('E-mail é obrigatório.');
        }
        if (strlen($plainPassword) < 4) {
            throw new \RuntimeException('Senha muito curta.');
        }

        $pdo = $this->container->get(\PDO::class);
        $patients = new PatientRepository($pdo);
        $patient = $patients->findById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente não encontrado.');
        }

        $users = new PatientUserRepository($pdo);
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {
            $existing = $users->findByPatientIdForUpdate($clinicId, $patientId);
            if ($existing === null) {
                $users->create($clinicId, $patientId, $email, $passwordHash);
            } else {
                $users->updateEmail($clinicId, (int)$existing['id'], $email);
                $users->updatePassword($clinicId, (int)$existing['id'], $passwordHash);
            }

            (new AuditLogRepository($pdo))->log(
                $actorId, $clinicId,
                'patients.portal_access.create_with_password',
                ['patient_id' => $patientId],
                $ip
            );

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Enviar e-mail com credenciais
        try {
            $clinic = (new ClinicRepository($pdo))->findById($clinicId);
            $clinicName = $clinic !== null ? (string)($clinic['name'] ?? '') : '';
            $portalUrl = rtrim($this->buildResetUrl(''), '/portal/reset?token=');

            $this->sendCredentialsEmail([
                'name'        => (string)($patient['name'] ?? ''),
                'clinic_name' => $clinicName,
                'email'       => $email,
                'password'    => $plainPassword,
                'portal_url'  => $portalUrl . '/portal/login',
            ]);
        } catch (Throwable $ignore) {
            // Não bloqueia por falha de e-mail
        }
    }

    /** @param array{name:string,clinic_name:string,email:string,password:string,portal_url:string} $data */
    private function sendCredentialsEmail(array $data): void
    {
        $toEmail = trim($data['email']);
        if ($toEmail === '') return;

        $toName     = trim($data['name']);
        $clinicName = trim($data['clinic_name']);
        $password   = $data['password'];
        $portalUrl  = trim($data['portal_url']);

        $safeName   = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
        $safeClinic = htmlspecialchars($clinicName !== '' ? $clinicName : 'sua clínica', ENT_QUOTES, 'UTF-8');
        $safeEmail  = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $safePass   = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
        $safeUrl    = htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8');

        $subject = 'Seu acesso ao Portal do Paciente — ' . ($clinicName !== '' ? $clinicName : 'LumiClinic');

        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"/></head>'
            . '<body style="margin:0;padding:0;background:#f4ecd4;font-family:ui-sans-serif,system-ui,-apple-system,\'Segoe UI\',Roboto,Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4ecd4;padding:32px 0;">'
            . '<tr><td align="center">'
            . '<table width="560" cellpadding="0" cellspacing="0" style="background:#fffdf8;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10);max-width:560px;width:100%;">'

            // Header
            . '<tr><td style="background:linear-gradient(135deg,#fde59f,#815901);padding:28px 32px;text-align:center;">'
            . '<div style="font-size:22px;font-weight:800;color:#fff;">' . $safeClinic . '</div>'
            . '<div style="font-size:13px;color:rgba(255,255,255,.85);margin-top:4px;">Portal do Paciente</div>'
            . '</td></tr>'

            // Body
            . '<tr><td style="padding:32px;">'
            . '<p style="font-size:16px;font-weight:700;color:#2a2a2a;margin:0 0 12px;">Olá, ' . $safeName . '!</p>'
            . '<p style="font-size:14px;color:#4b5563;line-height:1.7;margin:0 0 20px;">Seu acesso ao <strong>Portal do Paciente</strong> foi criado. Use os dados abaixo para entrar.</p>'

            // Credentials box
            . '<div style="background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:20px 24px;margin-bottom:24px;">'
            . '<div style="font-size:12px;color:#92400e;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px;">Seus dados de acesso</div>'
            . '<div style="font-size:15px;color:#2a2a2a;margin-bottom:8px;"><strong>E-mail:</strong> ' . $safeEmail . '</div>'
            . '<div style="font-size:15px;color:#2a2a2a;"><strong>Senha:</strong> ' . $safePass . '</div>'
            . '</div>'

            // CTA
            . '<div style="text-align:center;margin-bottom:24px;">'
            . '<a href="' . $safeUrl . '" style="display:inline-block;background:linear-gradient(135deg,#fde59f,#815901);color:#fff;font-weight:700;font-size:15px;text-decoration:none;padding:14px 32px;border-radius:8px;">Acessar o Portal</a>'
            . '</div>'

            . '<div style="background:#f3f4f6;border-radius:8px;padding:12px 16px;margin-bottom:16px;">'
            . '<p style="font-size:13px;color:#374151;margin:0;"><strong>💡 Dica:</strong> No seu primeiro acesso, vá em <strong>Segurança</strong> e troque a senha para uma de sua preferência.</p>'
            . '</div>'

            . '<p style="font-size:12px;color:#9ca3af;margin:0;">Se você não esperava este e-mail, entre em contato com a clínica.</p>'
            . '</td></tr>'

            // Footer
            . '<tr><td style="background:#f4ecd4;padding:16px 32px;text-align:center;border-top:1px solid rgba(238,184,16,.3);">'
            . '<div style="font-size:12px;color:#92400e;">' . $safeClinic . ' · Portal do Paciente</div>'
            . '</td></tr>'

            . '</table></td></tr></table></body></html>';

        (new MailerService($this->container))->send($toEmail, $toName !== '' ? $toName : $toEmail, $subject, $html);
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
