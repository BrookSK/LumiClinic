<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientLgpdRequestRepository;

final class PortalLgpdService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function list(int $clinicId, int $patientId, string $ip): array
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientLgpdRequestRepository($pdo);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.lgpd.view', ['patient_id' => $patientId], $ip);

        return $repo->listByPatient($clinicId, $patientId, 50);
    }

    public function request(int $clinicId, int $patientId, string $type, ?string $note, string $ip): int
    {
        $allowed = ['export', 'delete', 'revoke_consent'];
        if (!in_array($type, $allowed, true)) {
            throw new \RuntimeException('Tipo inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientLgpdRequestRepository($pdo);
        $id = $repo->create($clinicId, $patientId, $type, $note);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.lgpd.request', ['patient_id' => $patientId, 'request_id' => $id, 'type' => $type], $ip);

        return $id;
    }
}
