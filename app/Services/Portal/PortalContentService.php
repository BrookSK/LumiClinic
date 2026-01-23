<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientContentAccessRepository;

final class PortalContentService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listForPatient(int $clinicId, int $patientId, string $ip): array
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientContentAccessRepository($pdo);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.content.view', ['patient_id' => $patientId], $ip);

        return $repo->listForPatient($clinicId, $patientId, 200);
    }
}
