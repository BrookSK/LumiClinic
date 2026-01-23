<?php

declare(strict_types=1);

namespace App\Services\Bi;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\BiSnapshotRepository;
use App\Services\Auth\AuthService;

final class BiService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{period_start:string,period_end:string,metrics:array<string,mixed>,computed_at:?string} */
    public function dashboard(string $periodStartYmd, string $periodEndYmd, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $snapshots = new BiSnapshotRepository($pdo);

        $latest = $snapshots->latestByMetric($clinicId, 'executive');
        $computedAt = $latest !== null ? (string)($latest['computed_at'] ?? null) : null;
        $metrics = $latest !== null && isset($latest['data']) && is_array($latest['data']) ? $latest['data'] : [];

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'bi.view', ['period_start' => $periodStartYmd, 'period_end' => $periodEndYmd], $ip, $roleCodes, null, null, $userAgent);

        return [
            'period_start' => $periodStartYmd,
            'period_end' => $periodEndYmd,
            'metrics' => $metrics,
            'computed_at' => $computedAt,
        ];
    }

    public function refreshExecutiveSnapshot(string $periodStartYmd, string $periodEndYmd, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $sqlRevenue = "
            SELECT
                COALESCE(SUM(s.total_liquido), 0) AS revenue_paid,
                COUNT(*) AS sales_paid
            FROM sales s
            WHERE s.clinic_id = :clinic_id
              AND s.deleted_at IS NULL
              AND s.status = 'paid'
              AND DATE(s.created_at) BETWEEN :start AND :end
        ";
        $stmt = $pdo->prepare($sqlRevenue);
        $stmt->execute(['clinic_id' => $clinicId, 'start' => $periodStartYmd, 'end' => $periodEndYmd]);
        $rev = $stmt->fetch() ?: [];

        $sqlPatients = "
            SELECT COUNT(*) AS new_patients
            FROM patients p
            WHERE p.clinic_id = :clinic_id
              AND p.deleted_at IS NULL
              AND DATE(p.created_at) BETWEEN :start AND :end
        ";
        $stmt2 = $pdo->prepare($sqlPatients);
        $stmt2->execute(['clinic_id' => $clinicId, 'start' => $periodStartYmd, 'end' => $periodEndYmd]);
        $pat = $stmt2->fetch() ?: [];

        $sqlAppt = "
            SELECT
                COUNT(*) AS appointments_total,
                SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END) AS appointments_confirmed
            FROM appointments a
            WHERE a.clinic_id = :clinic_id
              AND a.deleted_at IS NULL
              AND DATE(a.start_at) BETWEEN :start AND :end
        ";
        $stmt3 = $pdo->prepare($sqlAppt);
        $stmt3->execute(['clinic_id' => $clinicId, 'start' => $periodStartYmd, 'end' => $periodEndYmd]);
        $appt = $stmt3->fetch() ?: [];

        $sqlEvents = "
            SELECT event_type, COUNT(*) AS cnt
            FROM patient_events
            WHERE clinic_id = :clinic_id
              AND created_at BETWEEN :start_dt AND :end_dt
            GROUP BY event_type
        ";
        $stmt4 = $pdo->prepare($sqlEvents);
        $stmt4->execute([
            'clinic_id' => $clinicId,
            'start_dt' => $periodStartYmd . ' 00:00:00',
            'end_dt' => $periodEndYmd . ' 23:59:59',
        ]);
        $eventRows = $stmt4->fetchAll();
        $events = [];
        foreach ($eventRows as $r) {
            $events[(string)$r['event_type']] = (int)$r['cnt'];
        }

        $metrics = [
            'revenue_paid' => (float)($rev['revenue_paid'] ?? 0),
            'sales_paid' => (int)($rev['sales_paid'] ?? 0),
            'new_patients' => (int)($pat['new_patients'] ?? 0),
            'appointments_total' => (int)($appt['appointments_total'] ?? 0),
            'appointments_confirmed' => (int)($appt['appointments_confirmed'] ?? 0),
            'patient_events' => $events,
        ];

        (new BiSnapshotRepository($pdo))->upsert($clinicId, 'executive', $periodStartYmd, $periodEndYmd, $metrics, $actorId);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'bi.refresh', ['period_start' => $periodStartYmd, 'period_end' => $periodEndYmd], $ip, $roleCodes, 'bi_snapshot', null, $userAgent);
    }
}
