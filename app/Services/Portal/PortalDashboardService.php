<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\PatientPackageRepository;
use App\Repositories\PatientSubscriptionRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SaleRepository;
use App\Repositories\PatientNotificationRepository;

final class PortalDashboardService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array{
     *   upcoming_appointments:list<array<string,mixed>>
     *   packages:list<array<string,mixed>>,
     *   subscriptions:list<array<string,mixed>>,
     *   finance:array{total:float,paid:float,open:float}
     *   notifications:list<array<string,mixed>>
     * }
     */
    public function dashboard(int $clinicId, int $patientId): array
    {
        $pdo = $this->container->get(\PDO::class);
        $apptRepo = new AppointmentRepository($pdo);
        $pkgRepo = new PatientPackageRepository($pdo);
        $subRepo = new PatientSubscriptionRepository($pdo);
        $saleRepo = new SaleRepository($pdo);
        $payRepo = new PaymentRepository($pdo);
        $notifRepo = new PatientNotificationRepository($pdo);

        $total = $saleRepo->summarizeTotalLiquidoByPatient($clinicId, $patientId)['total_liquido'] ?? 0.0;
        $paid = $payRepo->summarizePaidByPatient($clinicId, $patientId)['paid_total'] ?? 0.0;
        $total = (float)$total;
        $paid = (float)$paid;
        $open = max(0.0, $total - $paid);

        return [
            'upcoming_appointments' => $apptRepo->listUpcomingByPatient($clinicId, $patientId, 10),
            'packages' => $pkgRepo->listActiveByPatient($clinicId, $patientId, 20),
            'subscriptions' => $subRepo->listActiveByPatient($clinicId, $patientId, 20),
            'finance' => [
                'total' => $total,
                'paid' => $paid,
                'open' => $open,
            ],
            'notifications' => $notifRepo->listLatestByPatient($clinicId, $patientId, 5),
        ];
    }
}
