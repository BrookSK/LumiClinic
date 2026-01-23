<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Core\Container\Container;
use App\Repositories\AdminUserRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Services\Auth\AuthService;

final class UserAdminService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function listUsers(int $limit = 200, int $offset = 0): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new AdminUserRepository($this->container->get(\PDO::class));
        $limit = max(1, min($limit, 500));
        $offset = max(0, $offset);
        return $repo->listByClinic($clinicId, $limit, $offset);
    }

    /** @return list<array{id:int,code:string,name:string}> */
    public function listRoles(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $roles = new RoleRepository($this->container->get(\PDO::class));
        return $roles->listByClinic($clinicId);
    }

    public function createUser(string $name, string $email, string $password, int $roleId, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        if ($passwordHash === false) {
            throw new \RuntimeException('Falha ao gerar hash de senha.');
        }

        $users = new AdminUserRepository($this->container->get(\PDO::class));
        $newUserId = $users->create($clinicId, $name, $email, $passwordHash);

        $roles = new RoleRepository($this->container->get(\PDO::class));
        $roles->assignRoleToUser($clinicId, $newUserId, $roleId);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($actorId, $clinicId, 'users.create', ['target_user_id' => $newUserId], $ip);
    }

    /** @return array<string, mixed>|null */
    public function getUser(int $userId): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $users = new AdminUserRepository($this->container->get(\PDO::class));
        return $users->findById($clinicId, $userId);
    }

    public function updateUser(int $userId, string $name, string $email, string $status, int $roleId, ?string $newPassword, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $users = new AdminUserRepository($this->container->get(\PDO::class));
        $users->updateProfile($clinicId, $userId, $name, $email, $status);

        $roles = new RoleRepository($this->container->get(\PDO::class));
        $roles->clearRolesForUser($clinicId, $userId);
        $roles->assignRoleToUser($clinicId, $userId, $roleId);

        if ($newPassword !== null && $newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            if ($hash === false) {
                throw new \RuntimeException('Falha ao gerar hash de senha.');
            }
            $users->updatePassword($clinicId, $userId, $hash);
        }

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($actorId, $clinicId, 'users.update', ['target_user_id' => $userId], $ip);

        if ($auth->userId() === $userId) {
            $permRepo = new PermissionRepository($this->container->get(\PDO::class));
            $_SESSION['permissions'] = $permRepo->getPermissionCodesForUser($clinicId, $userId);
        }
    }

    public function disableUser(int $userId, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $users = new AdminUserRepository($this->container->get(\PDO::class));
        $users->disable($clinicId, $userId);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($actorId, $clinicId, 'users.delete', ['target_user_id' => $userId], $ip);
    }
}
