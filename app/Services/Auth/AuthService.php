<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\UserRoleRepository;
use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(private readonly Container $container) {}

    public function attempt(string $email, string $password, string $ip): AuthResult
    {
        $users = new UserRepository($this->container->get(\PDO::class));
        $audit = new AuditLogRepository($this->container->get(\PDO::class));

        $user = $users->findActiveByEmail($email);
        if ($user === null) {
            $audit->log(null, null, 'auth.login_failed', ['email' => $email], $ip);
            return new AuthResult(false, 'Credenciais inválidas.');
        }

        $isSuperAdmin = isset($user['is_super_admin']) && (int)$user['is_super_admin'] === 1;
        $clinicIdForAudit = (!$isSuperAdmin && isset($user['clinic_id']) && $user['clinic_id'] !== null)
            ? (int)$user['clinic_id']
            : null;

        if (!password_verify($password, $user['password_hash'])) {
            $audit->log((int)$user['id'], $clinicIdForAudit, 'auth.login_failed', ['email' => $email], $ip);
            return new AuthResult(false, 'Credenciais inválidas.');
        }

        $hostClinicId = null;
        if ($this->container->has('host_clinic_id')) {
            $hostClinicId = $this->container->get('host_clinic_id');
        }

        if (!$isSuperAdmin && is_int($hostClinicId) && $hostClinicId !== (int)$user['clinic_id']) {
            $audit->log((int)$user['id'], $clinicIdForAudit, 'auth.login_blocked_host_mismatch', ['host_clinic_id' => $hostClinicId], $ip);
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

        $audit->log((int)$user['id'], $clinicIdForAudit, 'auth.login', [], $ip);

        return new AuthResult(true, 'OK');
    }

    public function logout(string $ip): void
    {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $clinicId = isset($_SESSION['clinic_id']) ? (int)$_SESSION['clinic_id'] : null;

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'auth.logout', [], $ip);

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
