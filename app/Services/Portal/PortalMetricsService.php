<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientEventRepository;

final class PortalMetricsService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{portal_logins:int,appointment_confirms:int} */
    public function summary(int $clinicId, int $patientId, string $ip): array
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientEventRepository($pdo);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.metrics.view', ['patient_id' => $patientId], $ip);

        return $repo->summarizeSimple($clinicId, $patientId);
    }
}
