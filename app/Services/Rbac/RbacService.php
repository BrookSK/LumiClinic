<?php

declare(strict_types=1);

namespace App\Services\Rbac;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PermissionChangeLogRepository;
use App\Repositories\RbacRepository;
use App\Services\Auth\AuthService;

final class RbacService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array{id:int,code:string,name:string,is_system:int,is_editable:int}> */
    public function listRoles(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new RbacRepository($this->container->get(\PDO::class));
        return $repo->listRolesByClinic($clinicId);
    }

    /** @return array{id:int,code:string,name:string,is_system:int,is_editable:int} */
    public function getRole(int $roleId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new RbacRepository($this->container->get(\PDO::class));
        $role = $repo->findRoleById($clinicId, $roleId);
        if ($role === null) {
            throw new \RuntimeException('Role não encontrada.');
        }

        return $role;
    }

    /** @return list<array{id:int,module:string,action:string,code:string,description:?string}> */
    public function listPermissionsCatalog(): array
    {
        $repo = new RbacRepository($this->container->get(\PDO::class));
        return $repo->listGlobalPermissions();
    }

    /** @return array{allow:list<string>,deny:list<string>} */
    public function getRoleDecisions(int $roleId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new RbacRepository($this->container->get(\PDO::class));
        return $repo->getRolePermissionDecisions($clinicId, $roleId);
    }

    public function createRoleFromClone(int $fromRoleId, string $newName, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $newName = trim($newName);
        if ($newName === '') {
            throw new \RuntimeException('Nome é obrigatório.');
        }

        $pdo = $this->container->get(\PDO::class);
        $pdo->beginTransaction();

        try {
            $repo = new RbacRepository($pdo);
            $from = $repo->findRoleById($clinicId, $fromRoleId);
            if ($from === null) {
                throw new \RuntimeException('Role origem não encontrada.');
            }

            $fromDecisions = $repo->getRolePermissionDecisions($clinicId, $fromRoleId);

            $code = 'custom_' . bin2hex(random_bytes(6));
            $newRoleId = $repo->createRole($clinicId, $code, $newName, 0, 1);
            $repo->copyRolePermissions($clinicId, $fromRoleId, $newRoleId);

            $toDecisions = $repo->getRolePermissionDecisions($clinicId, $newRoleId);

            $changes = new PermissionChangeLogRepository($pdo);
            $changes->log(
                $clinicId,
                $actorId,
                $newRoleId,
                'rbac.roles.clone',
                ['from_role_id' => $fromRoleId, 'decisions' => $fromDecisions],
                ['new_role_id' => $newRoleId, 'decisions' => $toDecisions],
                $ip
            );

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'rbac.roles.clone', ['from_role_id' => $fromRoleId, 'new_role_id' => $newRoleId], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateRole(int $roleId, string $name, array $allowCodes, array $denyCodes, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new RbacRepository($this->container->get(\PDO::class));
        $role = $repo->findRoleById($clinicId, $roleId);
        if ($role === null) {
            throw new \RuntimeException('Role não encontrada.');
        }

        if ((int)$role['is_editable'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }

        $name = trim($name);
        if ($name === '') {
            throw new \RuntimeException('Nome é obrigatório.');
        }

        $catalog = $repo->listGlobalPermissions();
        $codeToId = [];
        foreach ($catalog as $p) {
            $codeToId[$p['code']] = (int)$p['id'];
        }

        $allowCodes = array_values(array_unique(array_filter(array_map('strval', $allowCodes))));
        $denyCodes = array_values(array_unique(array_filter(array_map('strval', $denyCodes))));

        $pdo = $this->container->get(\PDO::class);
        $pdo->beginTransaction();

        try {
            $repoTx = new RbacRepository($pdo);
            $before = $repoTx->getRolePermissionDecisions($clinicId, $roleId);
            $repoTx->updateRoleName($clinicId, $roleId, $name);
            $repoTx->clearRolePermissions($clinicId, $roleId);

            foreach ($allowCodes as $code) {
                if (!isset($codeToId[$code])) {
                    continue;
                }
                $repoTx->addRolePermission($clinicId, $roleId, $codeToId[$code], 'allow');
            }

            foreach ($denyCodes as $code) {
                if (!isset($codeToId[$code])) {
                    continue;
                }
                $repoTx->addRolePermission($clinicId, $roleId, $codeToId[$code], 'deny');
            }

            $after = $repoTx->getRolePermissionDecisions($clinicId, $roleId);

            $changes = new PermissionChangeLogRepository($pdo);
            $changes->log(
                $clinicId,
                $actorId,
                $roleId,
                'rbac.roles.update',
                ['name' => (string)$role['name'], 'decisions' => $before],
                ['name' => $name, 'decisions' => $after],
                $ip
            );

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'rbac.roles.update', ['role_id' => $roleId], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function resetRoleToDefaults(int $roleId, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new RbacRepository($pdo);
        $role = $repo->findRoleById($clinicId, $roleId);
        if ($role === null) {
            throw new \RuntimeException('Role não encontrada.');
        }

        if ((int)$role['is_editable'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }

        $pdo->beginTransaction();
        try {
            $repoTx = new RbacRepository($pdo);
            $before = $repoTx->getRolePermissionDecisions($clinicId, $roleId);
            $repoTx->resetRolePermissionsToDefaults($clinicId, (string)$role['code'], $roleId);

            $after = $repoTx->getRolePermissionDecisions($clinicId, $roleId);

            $changes = new PermissionChangeLogRepository($pdo);
            $changes->log(
                $clinicId,
                $actorId,
                $roleId,
                'rbac.roles.reset',
                ['decisions' => $before],
                ['decisions' => $after],
                $ip
            );

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'rbac.roles.reset', ['role_id' => $roleId], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
