<?php

declare(strict_types=1);

namespace App\Services\Stock;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\MaterialCategoryRepository;
use App\Repositories\MaterialUnitRepository;
use App\Services\Auth\AuthService;

final class MaterialMetaService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listActiveCategories(): array
    {
        $clinicId = (new AuthService($this->container))->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new MaterialCategoryRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId, 500);
    }

    /** @return list<array<string,mixed>> */
    public function listActiveUnits(): array
    {
        $clinicId = (new AuthService($this->container))->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new MaterialUnitRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId, 500);
    }

    /** @return list<array<string,mixed>> */
    public function listCategories(): array
    {
        $clinicId = (new AuthService($this->container))->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new MaterialCategoryRepository($this->container->get(\PDO::class));
        return $repo->listAllByClinic($clinicId, 500);
    }

    /** @return list<array<string,mixed>> */
    public function listUnits(): array
    {
        $clinicId = (new AuthService($this->container))->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new MaterialUnitRepository($this->container->get(\PDO::class));
        return $repo->listAllByClinic($clinicId, 500);
    }

    public function createCategory(string $name, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $name = trim($name);
        if ($name === '') {
            throw new \RuntimeException('Nome obrigatório.');
        }
        if (mb_strlen($name) > 64) {
            throw new \RuntimeException('Nome muito longo.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MaterialCategoryRepository($pdo);
        $id = $repo->create($clinicId, $name);

        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.material_categories.create', ['id' => $id, 'name' => $name], $ip);

        return $id;
    }

    public function deleteCategory(int $id, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        (new MaterialCategoryRepository($pdo))->softDelete($clinicId, $id);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.material_categories.delete', ['id' => $id], $ip);
    }

    public function createUnit(string $code, ?string $name, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $code = trim($code);
        $name = $name === null ? null : trim($name);

        if ($code === '') {
            throw new \RuntimeException('Código obrigatório.');
        }
        if (mb_strlen($code) > 16) {
            throw new \RuntimeException('Código muito longo.');
        }
        if ($name !== null && mb_strlen($name) > 64) {
            throw new \RuntimeException('Nome muito longo.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MaterialUnitRepository($pdo);
        $id = $repo->create($clinicId, $code, $name);

        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.material_units.create', ['id' => $id, 'code' => $code, 'name' => $name], $ip);

        return $id;
    }

    public function deleteUnit(int $id, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        (new MaterialUnitRepository($pdo))->softDelete($clinicId, $id);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.material_units.delete', ['id' => $id], $ip);
    }
}
