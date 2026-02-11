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
    public function listClinics(string $q = ''): array
    {
        $repo = new SystemClinicRepository($this->container->get(\PDO::class));
        $q = trim($q);
        if ($q === '') {
            return $repo->listAll();
        }
        return $repo->search($q, 250);
    }

    public function createClinicWithOwner(
        string $clinicName,
        ?string $tenantKey,
        ?string $primaryDomain,
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

            $primaryDomain = $primaryDomain !== null ? strtolower(trim($primaryDomain)) : null;
            if ($primaryDomain === '') {
                $primaryDomain = null;
            }
            if ($primaryDomain !== null) {
                $repo->createPrimaryDomain($clinicId, $primaryDomain);
            }

            $ownerPasswordHash = password_hash($ownerPassword, PASSWORD_BCRYPT);
            if ($ownerPasswordHash === false) {
                throw new \RuntimeException('Falha ao gerar hash de senha.');
            }

            $ownerUserId = $repo->createOwnerUser($clinicId, $ownerName, $ownerEmail, $ownerPasswordHash);
            $roleOwnerId = $repo->seedRbacAndReturnOwnerRoleId($clinicId);
            $repo->assignRole($clinicId, $ownerUserId, $roleOwnerId);

            $audit = new AuditLogRepository($pdo);
            $audit->log((int)($_SESSION['user_id'] ?? null), null, 'system.clinics.create', ['clinic_id' => $clinicId, 'tenant_key' => $tenantKey, 'primary_domain' => $primaryDomain], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<string,mixed>|null */
    public function getClinic(int $clinicId): ?array
    {
        return (new SystemClinicRepository($this->container->get(\PDO::class)))->findById($clinicId);
    }

    public function updateClinic(int $clinicId, string $name, ?string $tenantKey, ?string $primaryDomain, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);
        $pdo->beginTransaction();

        try {
            $repo = new SystemClinicRepository($pdo);
            $repo->updateClinic($clinicId, $name, $tenantKey);

            $primaryDomain = $primaryDomain !== null ? strtolower(trim($primaryDomain)) : null;
            if ($primaryDomain === '') {
                $primaryDomain = null;
            }
            if ($primaryDomain !== null) {
                $repo->updatePrimaryDomain($clinicId, $primaryDomain);
            }

            (new AuditLogRepository($pdo))->log((int)($_SESSION['user_id'] ?? null), null, 'system.clinics.update', [
                'clinic_id' => $clinicId,
                'tenant_key' => $tenantKey,
                'primary_domain' => $primaryDomain,
            ], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function setStatus(int $clinicId, string $status, string $ip): void
    {
        $allowed = ['active', 'inactive'];
        if (!in_array($status, $allowed, true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        (new SystemClinicRepository($pdo))->setStatus($clinicId, $status);

        (new AuditLogRepository($pdo))->log((int)($_SESSION['user_id'] ?? null), null, 'system.clinics.set_status', [
            'clinic_id' => $clinicId,
            'status' => $status,
        ], $ip);
    }

    public function deleteClinic(int $clinicId, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);
        (new SystemClinicRepository($pdo))->softDelete($clinicId);

        (new AuditLogRepository($pdo))->log((int)($_SESSION['user_id'] ?? null), null, 'system.clinics.delete', [
            'clinic_id' => $clinicId,
        ], $ip);
    }
}
