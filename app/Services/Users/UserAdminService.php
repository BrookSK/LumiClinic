<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Core\Container\Container;
use App\Repositories\AdminUserRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\RoleRepository;
use App\Services\Auth\AuthService;

final class UserAdminService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function listUsers(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new AdminUserRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId);
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
}
