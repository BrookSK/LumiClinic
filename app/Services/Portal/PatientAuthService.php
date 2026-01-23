<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientPasswordResetRepository;
use App\Repositories\PatientUserRepository;
use App\Repositories\PatientEventRepository;

final class PatientAuthService
{
    public function __construct(private readonly Container $container) {}

    public function attempt(string $email, string $password, string $ip): PatientAuthResult
    {
        $pdo = $this->container->get(\PDO::class);
        $users = new PatientUserRepository($pdo);
        $audit = new AuditLogRepository($pdo);

        $candidates = $users->listActiveByEmail($email, 5);
        if (count($candidates) === 0) {
            $audit->log(null, null, 'portal.login_failed', ['email' => $email], $ip);
            return new PatientAuthResult(false, 'Credenciais inválidas.');
        }
        if (count($candidates) > 1) {
            $audit->log(null, null, 'portal.login_failed_multi_clinic', ['email' => $email], $ip);
            return new PatientAuthResult(false, 'Este e-mail está vinculado a mais de uma clínica. Contate a clínica para ajustar seu acesso.');
        }

        $user = $candidates[0];
        $clinicId = (int)$user['clinic_id'];

        if (!password_verify($password, (string)$user['password_hash'])) {
            $audit->log(null, $clinicId, 'portal.login_failed', ['email' => $email, 'patient_user_id' => (int)$user['id'], 'patient_id' => (int)$user['patient_id']], $ip);
            return new PatientAuthResult(false, 'Credenciais inválidas.');
        }

        $_SESSION['patient_user_id'] = (int)$user['id'];
        $_SESSION['patient_id'] = (int)$user['patient_id'];
        $_SESSION['clinic_id'] = (int)$user['clinic_id'];
        $_SESSION['patient_two_factor_required'] = ((int)($user['two_factor_enabled'] ?? 0) === 1) ? 1 : 0;

        $users->touchLogin($clinicId, (int)$user['id'], $ip);

        $audit->log(null, $clinicId, 'portal.login', ['patient_user_id' => (int)$user['id'], 'patient_id' => (int)$user['patient_id']], $ip);

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

    public function logout(?string $ip): void
    {
        $clinicId = isset($_SESSION['clinic_id']) ? (int)$_SESSION['clinic_id'] : null;
        $patientUserId = isset($_SESSION['patient_user_id']) ? (int)$_SESSION['patient_user_id'] : null;
        $patientId = isset($_SESSION['patient_id']) ? (int)$_SESSION['patient_id'] : null;

        $pdo = $this->container->get(\PDO::class);
        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.logout', ['patient_user_id' => $patientUserId, 'patient_id' => $patientId], $ip);

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
