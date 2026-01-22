<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\SystemClinicRepository;

final class SystemClinicService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function listClinics(): array
    {
        $repo = new SystemClinicRepository($this->container->get(\PDO::class));
        return $repo->listAll();
    }

    public function createClinicWithOwner(
        string $clinicName,
        ?string $tenantKey,
        string $ownerName,
        string $ownerEmail,
        string $ownerPassword,
        string $ip
    ): void {
        $pdo = $this->container->get(\PDO::class);
        $pdo->beginTransaction();

        try {
            $repo = new SystemClinicRepository($pdo);

            $clinicId = $repo->createClinic($clinicName, $tenantKey);
            $repo->createClinicDefaults($clinicId);

            $ownerPasswordHash = password_hash($ownerPassword, PASSWORD_BCRYPT);
            if ($ownerPasswordHash === false) {
                throw new \RuntimeException('Falha ao gerar hash de senha.');
            }

            $ownerUserId = $repo->createOwnerUser($clinicId, $ownerName, $ownerEmail, $ownerPasswordHash);
            $roleOwnerId = $repo->seedRbacAndReturnOwnerRoleId($clinicId);
            $repo->assignRole($clinicId, $ownerUserId, $roleOwnerId);

            $audit = new AuditLogRepository($pdo);
            $audit->log((int)($_SESSION['user_id'] ?? null), null, 'system.clinics.create', ['clinic_id' => $clinicId], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
