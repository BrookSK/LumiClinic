<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SaleRepository;
use App\Repositories\PatientEventRepository;

final class PortalMetricsService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array{
     *   portal_logins:int,
     *   appointment_confirms:int,
     *   paid_total:float,
     *   total_liquido:float,
     *   paid_sales_count:int,
     *   ticket_medio:float,
     *   completed_appointments:int,
     *   first_completed_at:?string,
     *   last_completed_at:?string,
     *   days_since_last:?int,
     *   recorrente:int,
     *   retencao_status:string,
     *   top_services:list<array{service_name:string,cnt:int}>,
     *   top_procedures_by_revenue:list<array{service_name:string,revenue:float,qty:int}>
     * }
     */
    public function summary(int $clinicId, int $patientId, string $ip): array
    {
        $pdo = $this->container->get(\PDO::class);
        $eventsRepo = new PatientEventRepository($pdo);
        $salesRepo = new SaleRepository($pdo);
        $paymentsRepo = new PaymentRepository($pdo);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.metrics.view', ['patient_id' => $patientId], $ip);

        $simple = $eventsRepo->summarizeSimple($clinicId, $patientId);

        $paidTotal = $paymentsRepo->summarizePaidByPatient($clinicId, $patientId);
        $totalLiquido = $salesRepo->summarizeTotalLiquidoByPatient($clinicId, $patientId);

        $sqlTicket = "
            SELECT
                COUNT(1) AS cnt,
                COALESCE(AVG(s.total_liquido), 0) AS avg_ticket
            FROM sales s
            WHERE s.clinic_id = :clinic_id
              AND s.patient_id = :patient_id
              AND s.deleted_at IS NULL
              AND s.status = 'paid'
        ";
        $stmtT = $pdo->prepare($sqlTicket);
        $stmtT->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $rowT = $stmtT->fetch() ?: [];
        $paidSalesCount = (int)($rowT['cnt'] ?? 0);
        $ticketMedio = (float)($rowT['avg_ticket'] ?? 0);

        $sqlAppt = "
            SELECT
                COUNT(1) AS cnt,
                MIN(a.start_at) AS first_completed_at,
                MAX(a.start_at) AS last_completed_at
            FROM appointments a
            WHERE a.clinic_id = :clinic_id
              AND a.patient_id = :patient_id
              AND a.deleted_at IS NULL
              AND a.status = 'completed'
        ";
        $stmtA = $pdo->prepare($sqlAppt);
        $stmtA->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $rowA = $stmtA->fetch() ?: [];

        $completedAppointments = (int)($rowA['cnt'] ?? 0);
        $firstCompletedAt = isset($rowA['first_completed_at']) ? (string)$rowA['first_completed_at'] : null;
        $lastCompletedAt = isset($rowA['last_completed_at']) ? (string)$rowA['last_completed_at'] : null;
        $daysSinceLast = null;
        if ($lastCompletedAt !== null && trim($lastCompletedAt) !== '') {
            $sqlDays = "SELECT DATEDIFF(NOW(), :dt) AS d";
            $stmtD = $pdo->prepare($sqlDays);
            $stmtD->execute(['dt' => $lastCompletedAt]);
            $rowD = $stmtD->fetch() ?: [];
            $daysSinceLast = isset($rowD['d']) ? (int)$rowD['d'] : null;
        }

        $recorrente = $completedAppointments >= 2 ? 1 : 0;

        $retencaoStatus = 'novo';
        if ($completedAppointments >= 2) {
            $retencaoStatus = 'recorrente';
        }
        if ($daysSinceLast !== null) {
            if ($daysSinceLast <= 90) {
                $retencaoStatus = 'ativo';
            } elseif ($daysSinceLast <= 180) {
                $retencaoStatus = 'risco';
            } else {
                $retencaoStatus = 'inativo';
            }
        }

        $sqlTopServices = "
            SELECT
                COALESCE(s.name, '') AS service_name,
                COUNT(1) AS cnt
            FROM appointments a
            LEFT JOIN services s
                   ON s.id = a.service_id
                  AND s.clinic_id = a.clinic_id
                  AND s.deleted_at IS NULL
            WHERE a.clinic_id = :clinic_id
              AND a.patient_id = :patient_id
              AND a.deleted_at IS NULL
              AND a.status = 'completed'
            GROUP BY a.service_id
            ORDER BY cnt DESC
            LIMIT 10
        ";
        $stmtTS = $pdo->prepare($sqlTopServices);
        $stmtTS->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $topServicesRows = $stmtTS->fetchAll();
        $topServices = [];
        foreach (is_array($topServicesRows) ? $topServicesRows : [] as $r) {
            $topServices[] = [
                'service_name' => (string)($r['service_name'] ?? ''),
                'cnt' => (int)($r['cnt'] ?? 0),
            ];
        }

        $sqlTopProcedures = "
            SELECT
                COALESCE(svc.name, '') AS service_name,
                SUM(
                    si.subtotal * (s.total_liquido / NULLIF(s.total_bruto, 0))
                ) AS revenue,
                SUM(si.quantity) AS qty
            FROM sale_items si
            INNER JOIN sales s
                    ON s.id = si.sale_id
                   AND s.clinic_id = si.clinic_id
                   AND s.deleted_at IS NULL
            LEFT JOIN services svc
                   ON svc.id = si.reference_id
                  AND svc.clinic_id = si.clinic_id
                  AND svc.deleted_at IS NULL
            WHERE si.clinic_id = :clinic_id
              AND si.deleted_at IS NULL
              AND si.type = 'procedure'
              AND s.patient_id = :patient_id
              AND s.status = 'paid'
            GROUP BY si.reference_id
            ORDER BY revenue DESC
            LIMIT 10
        ";
        $stmtTP = $pdo->prepare($sqlTopProcedures);
        $stmtTP->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $topProcRows = $stmtTP->fetchAll();
        $topProcedures = [];
        foreach (is_array($topProcRows) ? $topProcRows : [] as $r) {
            $topProcedures[] = [
                'service_name' => (string)($r['service_name'] ?? ''),
                'revenue' => (float)($r['revenue'] ?? 0),
                'qty' => (int)($r['qty'] ?? 0),
            ];
        }

        return [
            'portal_logins' => (int)($simple['portal_logins'] ?? 0),
            'appointment_confirms' => (int)($simple['appointment_confirms'] ?? 0),
            'paid_total' => (float)($paidTotal['paid_total'] ?? 0),
            'total_liquido' => (float)($totalLiquido['total_liquido'] ?? 0),
            'paid_sales_count' => $paidSalesCount,
            'ticket_medio' => $ticketMedio,
            'completed_appointments' => $completedAppointments,
            'first_completed_at' => ($firstCompletedAt !== null && trim($firstCompletedAt) !== '' ? $firstCompletedAt : null),
            'last_completed_at' => ($lastCompletedAt !== null && trim($lastCompletedAt) !== '' ? $lastCompletedAt : null),
            'days_since_last' => $daysSinceLast,
            'recorrente' => $recorrente,
            'retencao_status' => $retencaoStatus,
            'top_services' => $topServices,
            'top_procedures_by_revenue' => $topProcedures,
        ];
    }
}
