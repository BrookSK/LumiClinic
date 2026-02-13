<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\PatientNotificationRepository;

final class PortalDashboardService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array{
     *   upcoming_appointments:list<array<string,mixed>>
     *   notifications:list<array<string,mixed>>
     * }
     */
    public function dashboard(int $clinicId, int $patientId): array
    {
        $pdo = $this->container->get(\PDO::class);
        $apptRepo = new AppointmentRepository($pdo);
        $notifRepo = new PatientNotificationRepository($pdo);

        return [
            'upcoming_appointments' => $apptRepo->listUpcomingByPatient($clinicId, $patientId, 10),
            'notifications' => $notifRepo->listLatestByPatient($clinicId, $patientId, 5),
        ];
    }
}
