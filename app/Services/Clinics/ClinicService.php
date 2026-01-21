<?php

declare(strict_types=1);

namespace App\Services\Clinics;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicRepository;
use App\Services\Auth\AuthService;

final class ClinicService
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string, mixed>|null */
    public function getCurrentClinic(): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            return null;
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        return $repo->findById($clinicId);
    }

    public function updateClinicName(string $name, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        $repo->updateName($clinicId, $name);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.update', ['fields' => ['name']], $ip);
    }
}
