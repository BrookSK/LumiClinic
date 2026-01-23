<?php

declare(strict_types=1);

namespace App\Services\Observability;

use App\Core\Container\Container;
use App\Repositories\SystemMetricRepository;

final class MetricsService
{
    public function __construct(private readonly Container $container) {}

    public function computeDailyClinicMetrics(int $clinicId, string $referenceDateYmd): void
    {
        $pdo = $this->container->get(\PDO::class);

        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN a.status = 'no_show' THEN 1 ELSE 0 END) AS no_show,
                SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
            FROM appointments a
            WHERE a.clinic_id = :clinic_id
              AND a.deleted_at IS NULL
              AND DATE(a.start_at) = :ref
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'ref' => $referenceDateYmd]);
        $row = $stmt->fetch() ?: [];

        $total = (int)($row['total'] ?? 0);
        $noShow = (int)($row['no_show'] ?? 0);
        $cancelled = (int)($row['cancelled'] ?? 0);

        $noShowRate = $total > 0 ? ($noShow / $total) * 100 : 0;
        $cancelRate = $total > 0 ? ($cancelled / $total) * 100 : 0;

        $repo = new SystemMetricRepository($pdo);
        $repo->record($clinicId, 'total_appointments', (float)$total, $referenceDateYmd);
        $repo->record($clinicId, 'no_show_rate', (float)$noShowRate, $referenceDateYmd);
        $repo->record($clinicId, 'cancel_rate', (float)$cancelRate, $referenceDateYmd);
    }
}
