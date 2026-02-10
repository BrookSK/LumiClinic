<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientPasswordResetRepository;
use App\Repositories\PatientUserRepository;
use App\Repositories\PatientEventRepository;
use App\Services\Mail\MailerService;
use Throwable;

final class PatientAuthService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{patient_user_id:int,patient_id:int,clinic_id:int,email:string,two_factor_enabled:int,status:string}|null */
    public function me(int $clinicId, int $patientUserId): ?array
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientUserRepository($pdo);
        $row = $repo->findById($clinicId, $patientUserId);
        if ($row === null) {
            return null;
        }

        return [
            'patient_user_id' => (int)($row['id'] ?? 0),
            'patient_id' => (int)($row['patient_id'] ?? 0),
            'clinic_id' => (int)($row['clinic_id'] ?? 0),
            'email' => (string)($row['email'] ?? ''),
            'two_factor_enabled' => (int)($row['two_factor_enabled'] ?? 0),
            'status' => (string)($row['status'] ?? ''),
        ];
    }

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

    public function loginPatientUserByIdForSession(int $patientUserId, string $ip, ?string $userAgent = null): void
    {
        $pdo = $this->container->get(\PDO::class);
        $users = new PatientUserRepository($pdo);
        $audit = new AuditLogRepository($pdo);

        $user = $users->findByIdGlobal($patientUserId);
        if ($user === null) {
            throw new \RuntimeException('Acesso inválido.');
        }

        $clinicId = (int)$user['clinic_id'];

        $_SESSION['patient_user_id'] = (int)$user['id'];
        $_SESSION['patient_id'] = (int)$user['patient_id'];
        $_SESSION['clinic_id'] = (int)$user['clinic_id'];
        $_SESSION['patient_two_factor_required'] = ((int)($user['two_factor_enabled'] ?? 0) === 1) ? 1 : 0;

        $users->touchLogin($clinicId, (int)$user['id'], $ip);

        $audit->log(null, $clinicId, 'portal.login', ['patient_user_id' => (int)$user['id'], 'patient_id' => (int)$user['patient_id'], 'via' => 'choose_access'], $ip, null, 'patient_user', (int)$user['id'], $userAgent);

        (new PatientEventRepository($pdo))->create(
            $clinicId,
            (int)$user['patient_id'],
            'portal_login',
            null,
            null,
            ['via' => 'choose_access']
        );
    }

    public function attempt(string $email, string $password, string $ip, ?string $userAgent = null): PatientAuthResult
    {
        $pdo = $this->container->get(\PDO::class);
        $users = new PatientUserRepository($pdo);
        $audit = new AuditLogRepository($pdo);

        $candidates = $users->listActiveByEmail($email, 5);
        if (count($candidates) === 0) {
            $audit->log(null, null, 'portal.login_failed', ['email' => $email], $ip, null, 'patient_user', null, $userAgent);
            return new PatientAuthResult(false, 'Credenciais inválidas.');
        }

        $matches = [];
        foreach ($candidates as $cand) {
            if (isset($cand['password_hash']) && password_verify($password, (string)$cand['password_hash'])) {
                $matches[] = $cand;
            }
        }

        if (count($matches) === 0) {
            $audit->log(null, null, 'portal.login_failed', ['email' => $email], $ip, null, 'patient_user', null, $userAgent);
            return new PatientAuthResult(false, 'Credenciais inválidas.');
        }

        if (count($matches) > 1) {
            $audit->log(null, null, 'portal.login_failed_multi_clinic', ['email' => $email], $ip, null, 'patient_user', null, $userAgent);
            return new PatientAuthResult(false, 'Selecione a clínica na tela de escolha de acesso.');
        }

        $user = $matches[0];
        $clinicId = (int)$user['clinic_id'];

        if (!password_verify($password, (string)$user['password_hash'])) {
            $audit->log(null, $clinicId, 'portal.login_failed', ['email' => $email, 'patient_user_id' => (int)$user['id'], 'patient_id' => (int)$user['patient_id']], $ip, null, 'patient_user', (int)$user['id'], $userAgent);
            return new PatientAuthResult(false, 'Credenciais inválidas.');
        }

        $_SESSION['patient_user_id'] = (int)$user['id'];
        $_SESSION['patient_id'] = (int)$user['patient_id'];
        $_SESSION['clinic_id'] = (int)$user['clinic_id'];
        $_SESSION['patient_two_factor_required'] = ((int)($user['two_factor_enabled'] ?? 0) === 1) ? 1 : 0;

        $users->touchLogin($clinicId, (int)$user['id'], $ip);

        $audit->log(null, $clinicId, 'portal.login', ['patient_user_id' => (int)$user['id'], 'patient_id' => (int)$user['patient_id']], $ip, null, 'patient_user', (int)$user['id'], $userAgent);

        (new PatientEventRepository($pdo))->create(
            $clinicId,
            (int)$user['patient_id'],
            'portal_login',
            null,
            null,
            []
        );

        return new PatientAuthResult(true, 'OK');
    }

    public function logout(?string $ip, ?string $userAgent = null): void
    {
        $clinicId = isset($_SESSION['clinic_id']) ? (int)$_SESSION['clinic_id'] : null;
        $patientUserId = isset($_SESSION['patient_user_id']) ? (int)$_SESSION['patient_user_id'] : null;
        $patientId = isset($_SESSION['patient_id']) ? (int)$_SESSION['patient_id'] : null;

        $pdo = $this->container->get(\PDO::class);
        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.logout', ['patient_user_id' => $patientUserId, 'patient_id' => $patientId], $ip, null, 'patient_user', $patientUserId, $userAgent);

        unset($_SESSION['patient_user_id'], $_SESSION['patient_id'], $_SESSION['clinic_id'], $_SESSION['patient_two_factor_required']);
        session_regenerate_id(true);
    }

    public function patientUserId(): ?int
    {
        return isset($_SESSION['patient_user_id']) ? (int)$_SESSION['patient_user_id'] : null;
    }

    public function patientId(): ?int
    {
        return isset($_SESSION['patient_id']) ? (int)$_SESSION['patient_id'] : null;
    }

    public function clinicId(): ?int
    {
        return isset($_SESSION['clinic_id']) ? (int)$_SESSION['clinic_id'] : null;
    }

    /** @return array{reset_token:string} */
    public function createPasswordReset(string $email, string $ip): array
    {
        $pdo = $this->container->get(\PDO::class);
        $users = new PatientUserRepository($pdo);
        $audit = new AuditLogRepository($pdo);
        $candidates = $users->listActiveByEmail($email, 5);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60);

        if (count($candidates) === 1) {
            $user = $candidates[0];
            $clinicId = (int)$user['clinic_id'];
            $resets = new PatientPasswordResetRepository($pdo);
            $resets->create($clinicId, (int)$user['id'], $tokenHash, $expiresAt, $ip);

            $audit->log(null, $clinicId, 'portal.password_reset.request', ['email' => $email, 'patient_user_id' => (int)$user['id'], 'patient_id' => (int)$user['patient_id']], $ip);
        } else {
            $audit->log(null, null, 'portal.password_reset.request', ['email' => $email], $ip);
        }

        return ['reset_token' => $token];
    }

    /** @return array{reset_token:string,reset_url:string} */
    public function createPasswordResetAndNotify(string $email, string $ip): array
    {
        $email = trim((string)$email);
        $out = $this->createPasswordReset($email, $ip);
        $token = (string)($out['reset_token'] ?? '');
        $resetUrl = $this->buildResetUrl($token);

        if ($token !== '' && $email !== '') {
            try {
                $subject = 'Redefinição de senha - Portal do Paciente';
                $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
                $html = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#111827">'
                    . '<p>Você solicitou a redefinição de senha do <strong>Portal do Paciente</strong>.</p>'
                    . '<p>Para definir uma nova senha, use o link abaixo:</p>'
                    . '<p><a href="' . $safeUrl . '">Redefinir minha senha</a></p>'
                    . '<p style="color:rgba(17,24,39,0.65);font-size:12px;">Se não foi você, ignore este e-mail.</p>'
                    . '</div>';

                (new MailerService($this->container))->send($email, $email, $subject, $html);
            } catch (Throwable $e) {
                // Não bloqueia o fluxo.
            }
        }

        return ['reset_token' => $token, 'reset_url' => $resetUrl];
    }

    public function resetPassword(string $token, string $newPassword, ?string $ip): PatientAuthResult
    {
        $token = trim($token);
        if ($token === '') {
            return new PatientAuthResult(false, 'Token inválido.');
        }

        if (strlen($newPassword) < 8) {
            return new PatientAuthResult(false, 'Senha deve ter pelo menos 8 caracteres.');
        }

        $pdo = $this->container->get(\PDO::class);
        $resets = new PatientPasswordResetRepository($pdo);
        $reset = $resets->findValidByTokenHash(hash('sha256', $token));
        if ($reset === null) {
            $audit = new AuditLogRepository($pdo);
            $audit->log(null, null, 'portal.password_reset.invalid_token', [], $ip);
            return new PatientAuthResult(false, 'Token inválido ou expirado.');
        }

        $clinicId = (int)$reset['clinic_id'];
        $patientUserId = (int)$reset['patient_user_id'];

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $users = new PatientUserRepository($pdo);
        $users->updatePassword($clinicId, $patientUserId, $hash);

        $resets->markUsed((int)$reset['id']);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.password_reset.success', ['patient_user_id' => $patientUserId], $ip);

        return new PatientAuthResult(true, 'Senha atualizada com sucesso.');
    }
}
