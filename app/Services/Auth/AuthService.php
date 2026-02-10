<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\UserRoleRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserPasswordResetRepository;
use App\Services\Mail\MailerService;
use App\Services\Observability\SystemEvent;
use Throwable;

final class AuthService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{reset_token:string} */
    public function createPasswordReset(string $email, string $ip): array
    {
        $pdo = $this->container->get(\PDO::class);
        $users = new UserRepository($pdo);
        $audit = new AuditLogRepository($pdo);

        $user = $users->findActiveByEmail($email);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60);

        if ($user !== null) {
            $clinicId = (isset($user['clinic_id']) && $user['clinic_id'] !== null) ? (int)$user['clinic_id'] : null;
            $resets = new UserPasswordResetRepository($pdo);
            $resets->create($clinicId, (int)$user['id'], $tokenHash, $expiresAt, $ip);

            $appConfig = $this->container->has('config') ? $this->container->get('config') : [];
            $baseUrl = is_array($appConfig) && isset($appConfig['app']) && is_array($appConfig['app'])
                ? (string)($appConfig['app']['base_url'] ?? '')
                : '';
            $baseUrl = rtrim($baseUrl !== '' ? $baseUrl : (string)(getenv('APP_BASE_URL') ?: ''), '/');

            if ($baseUrl === '' && isset($_SERVER['HTTP_HOST'])) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $baseUrl = $scheme . '://' . (string)$_SERVER['HTTP_HOST'];
            }

            $resetUrl = ($baseUrl !== '' ? $baseUrl : '') . '/reset?token=' . urlencode($token);

            try {
                $toEmail = (string)($user['email'] ?? '');
                $toName = (string)($user['name'] ?? '');
                if ($toEmail !== '') {
                    $subject = 'Redefinição de senha - LumiClinic';
                    $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
                    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
                    $html = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#111827">'
                        . '<p>Olá, ' . $safeName . '.</p>'
                        . '<p>Recebemos uma solicitação para redefinir sua senha no LumiClinic.</p>'
                        . '<p><a href="' . $safeUrl . '">Clique aqui para redefinir sua senha</a></p>'
                        . '<p>Se você não solicitou, ignore este e-mail.</p>'
                        . '</div>';

                    (new MailerService($this->container))->send($toEmail, $toName !== '' ? $toName : $toEmail, $subject, $html);
                }
            } catch (Throwable $e) {
                $audit->log((int)$user['id'], $clinicId, 'auth.password_reset.email_failed', ['error' => $e->getMessage()], $ip);
            }

            $audit->log((int)$user['id'], $clinicId, 'auth.password_reset.request', ['email' => $email, 'user_id' => (int)$user['id']], $ip);
            SystemEvent::dispatch($this->container, 'user.password_reset.request', ['email' => $email], 'user', (int)$user['id'], $ip, null);
        } else {
            $audit->log(null, null, 'auth.password_reset.request', ['email' => $email], $ip);
            SystemEvent::dispatch($this->container, 'user.password_reset.request', ['email' => $email], 'user', null, $ip, null);
        }

        return ['reset_token' => $token];
    }

    public function resetPassword(string $token, string $newPassword, ?string $ip): AuthResult
    {
        $token = trim($token);
        if ($token === '') {
            return new AuthResult(false, 'Token inválido.');
        }

        if (strlen($newPassword) < 8) {
            return new AuthResult(false, 'Senha deve ter pelo menos 8 caracteres.');
        }

        $pdo = $this->container->get(\PDO::class);
        $resets = new UserPasswordResetRepository($pdo);
        $reset = $resets->findValidByTokenHash(hash('sha256', $token));
        if ($reset === null) {
            $audit = new AuditLogRepository($pdo);
            $audit->log(null, null, 'auth.password_reset.invalid_token', [], $ip);
            SystemEvent::dispatch($this->container, 'user.password_reset.invalid_token', [], 'user', null, $ip, null);
            return new AuthResult(false, 'Token inválido ou expirado.');
        }

        $clinicId = (isset($reset['clinic_id']) && $reset['clinic_id'] !== null) ? (int)$reset['clinic_id'] : null;
        $userId = (int)$reset['user_id'];

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $users = new UserRepository($pdo);
        $users->updatePasswordById($userId, $hash);

        $resets->markUsed((int)$reset['id']);

        $audit = new AuditLogRepository($pdo);
        $audit->log($userId, $clinicId, 'auth.password_reset.success', ['user_id' => $userId], $ip);
        SystemEvent::dispatch($this->container, 'user.password_reset.success', [], 'user', $userId, $ip, null);

        return new AuthResult(true, 'Senha atualizada com sucesso.');
    }

    public function attempt(string $email, string $password, string $ip, ?string $userAgent = null): AuthResult
    {
        $users = new UserRepository($this->container->get(\PDO::class));
        $audit = new AuditLogRepository($this->container->get(\PDO::class));

        $user = $users->findActiveByEmail($email);
        if ($user === null) {
            $audit->log(null, null, 'auth.login_failed', ['email' => $email], $ip, null, 'user', null, $userAgent);
            SystemEvent::dispatch($this->container, 'user.login_failed', ['email' => $email], 'user', null, $ip, $userAgent);
            return new AuthResult(false, 'Credenciais inválidas.');
        }

        $isSuperAdmin = isset($user['is_super_admin']) && (int)$user['is_super_admin'] === 1;
        $clinicIdForAudit = (!$isSuperAdmin && isset($user['clinic_id']) && $user['clinic_id'] !== null)
            ? (int)$user['clinic_id']
            : null;

        if (!password_verify($password, $user['password_hash'])) {
            $audit->log((int)$user['id'], $clinicIdForAudit, 'auth.login_failed', ['email' => $email], $ip, null, 'user', (int)$user['id'], $userAgent);
            SystemEvent::dispatch($this->container, 'user.login_failed', ['email' => $email], 'user', (int)$user['id'], $ip, $userAgent);
            return new AuthResult(false, 'Credenciais inválidas.');
        }

        $hostClinicId = null;
        if ($this->container->has('host_clinic_id')) {
            $hostClinicId = $this->container->get('host_clinic_id');
        }

        if (!$isSuperAdmin && is_int($hostClinicId) && $hostClinicId !== (int)$user['clinic_id']) {
            $audit->log((int)$user['id'], $clinicIdForAudit, 'auth.login_blocked_host_mismatch', ['host_clinic_id' => $hostClinicId], $ip, null, 'user', (int)$user['id'], $userAgent);
            SystemEvent::dispatch($this->container, 'user.login_blocked_host_mismatch', ['host_clinic_id' => $hostClinicId], 'user', (int)$user['id'], $ip, $userAgent);
            return new AuthResult(false, 'Credenciais inválidas.');
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['is_super_admin'] = $isSuperAdmin ? 1 : 0;

        if (!$isSuperAdmin) {
            $_SESSION['clinic_id'] = (int)$user['clinic_id'];
        } else {
            unset($_SESSION['clinic_id']);
        }

        if (!$isSuperAdmin) {
            $permissionsRepo = new PermissionRepository($this->container->get(\PDO::class));
            $decisions = $permissionsRepo->getPermissionDecisionsForUser((int)$user['clinic_id'], (int)$user['id']);

            if (is_array($decisions) && isset($decisions['allow'], $decisions['deny'])) {
                $_SESSION['permissions'] = $decisions;
            } else {
                $_SESSION['permissions'] = $permissionsRepo->getPermissionCodesForUser((int)$user['clinic_id'], (int)$user['id']);
            }

            $rolesRepo = new UserRoleRepository($this->container->get(\PDO::class));
            $_SESSION['role_codes'] = $rolesRepo->getRoleCodesForUser((int)$user['clinic_id'], (int)$user['id']);
        } else {
            $_SESSION['permissions'] = [];
            $_SESSION['role_codes'] = [];
        }

        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log((int)$user['id'], $clinicIdForAudit, 'auth.login', [], $ip, $roleCodes, 'user', (int)$user['id'], $userAgent);
        SystemEvent::dispatch($this->container, 'user.login', ['is_super_admin' => $isSuperAdmin], 'user', (int)$user['id'], $ip, $userAgent);

        return new AuthResult(true, 'OK');
    }

    public function logout(string $ip, ?string $userAgent = null): void
    {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $clinicId = isset($_SESSION['clinic_id']) ? (int)$_SESSION['clinic_id'] : null;
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'auth.logout', [], $ip, $roleCodes, 'user', $userId, $userAgent);
        SystemEvent::dispatch($this->container, 'user.logout', [], 'user', $userId, $ip, $userAgent);

        unset($_SESSION['user_id'], $_SESSION['clinic_id'], $_SESSION['active_clinic_id'], $_SESSION['permissions'], $_SESSION['is_super_admin']);
        unset($_SESSION['role_codes']);
        session_regenerate_id(true);
    }

    public function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public function clinicId(): ?int
    {
        if (isset($_SESSION['clinic_id'])) {
            return (int)$_SESSION['clinic_id'];
        }

        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if ($isSuperAdmin && isset($_SESSION['active_clinic_id']) && is_int($_SESSION['active_clinic_id'])) {
            return $_SESSION['active_clinic_id'];
        }

        if ($this->container->has('clinic_id')) {
            $activeClinicId = $this->container->get('clinic_id');
            return is_int($activeClinicId) ? $activeClinicId : null;
        }

        return null;
    }
}
