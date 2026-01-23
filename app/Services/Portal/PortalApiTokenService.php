<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientApiTokenRepository;

final class PortalApiTokenService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function list(int $clinicId, int $patientUserId): array
    {
        $repo = new PatientApiTokenRepository($this->container->get(\PDO::class));
        return $repo->listByPatientUser($clinicId, $patientUserId, 50);
    }

    /** @return array{token:string} */
    public function create(int $clinicId, int $patientUserId, int $patientId, ?string $name, string $ip): array
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);

        $repo = new PatientApiTokenRepository($this->container->get(\PDO::class));
        $repo->create($clinicId, $patientUserId, $patientId, $hash, $name, null, null);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log(null, $clinicId, 'portal.api_tokens.create', ['patient_id' => $patientId], $ip);

        return ['token' => $token];
    }

    public function revoke(int $clinicId, int $patientUserId, int $id, string $ip): void
    {
        $repo = new PatientApiTokenRepository($this->container->get(\PDO::class));
        $repo->revoke($clinicId, $patientUserId, $id);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log(null, $clinicId, 'portal.api_tokens.revoke', ['token_id' => $id], $ip);
    }
}
