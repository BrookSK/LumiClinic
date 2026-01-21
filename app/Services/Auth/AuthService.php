<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PermissionRepository;
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

        if (!password_verify($password, $user['password_hash'])) {
            $audit->log((int)$user['id'], (int)$user['clinic_id'], 'auth.login_failed', ['email' => $email], $ip);
            return new AuthResult(false, 'Credenciais inválidas.');
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['clinic_id'] = (int)$user['clinic_id'];

        $permissionsRepo = new PermissionRepository($this->container->get(\PDO::class));
        $_SESSION['permissions'] = $permissionsRepo->getPermissionCodesForUser((int)$user['clinic_id'], (int)$user['id']);

        $audit->log((int)$user['id'], (int)$user['clinic_id'], 'auth.login', [], $ip);

        return new AuthResult(true, 'OK');
    }

    public function logout(string $ip): void
    {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $clinicId = isset($_SESSION['clinic_id']) ? (int)$_SESSION['clinic_id'] : null;

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'auth.logout', [], $ip);

        unset($_SESSION['user_id'], $_SESSION['clinic_id'], $_SESSION['permissions']);
        session_regenerate_id(true);
    }

    public function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public function clinicId(): ?int
    {
        return isset($_SESSION['clinic_id']) ? (int)$_SESSION['clinic_id'] : null;
    }
}
