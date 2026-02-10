<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Stock\StockService;

final class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $can = function (string $permissionCode): bool {
            if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
                return true;
            }

            $permissions = $_SESSION['permissions'] ?? [];
            if (!is_array($permissions)) {
                return false;
            }

            if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
                if (in_array($permissionCode, $permissions['deny'], true)) {
                    return false;
                }
                return in_array($permissionCode, $permissions['allow'], true);
            }

            return in_array($permissionCode, $permissions, true);
        };

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        $data = [
            'has_clinic_context' => $clinicId !== null,
            'can_schedule' => $can('scheduling.read'),
            'can_patients' => $can('patients.read'),
            'can_finance' => $can('finance.sales.read'),
            'can_stock_alerts' => $can('stock.alerts.read'),
            'kpis' => [
                'today_total' => 0,
                'today_confirmed' => 0,
                'today_in_progress' => 0,
                'today_completed' => 0,
                'new_patients_month' => 0,
                'revenue_paid_month' => 0.0,
            ],
            'upcoming_appointments' => [],
            'stock_alerts' => [
                'low_stock' => 0,
                'out_of_stock' => 0,
                'expiring_soon' => 0,
                'expired' => 0,
            ],
        ];

        if ($clinicId !== null) {
            $pdo = $this->container->get(\PDO::class);

            if ($can('scheduling.read')) {
                $sqlToday = "
                    SELECT status, COUNT(*) AS cnt
                    FROM appointments
                    WHERE clinic_id = :clinic_id
                      AND deleted_at IS NULL
                      AND DATE(start_at) = CURDATE()
                    GROUP BY status
                ";
                $stmtToday = $pdo->prepare($sqlToday);
                $stmtToday->execute(['clinic_id' => $clinicId]);
                $rowsToday = $stmtToday->fetchAll();
                $todayTotal = 0;
                foreach ($rowsToday as $r) {
                    $status = (string)($r['status'] ?? '');
                    $cnt = (int)($r['cnt'] ?? 0);
                    $todayTotal += $cnt;
                    if ($status === 'confirmed') {
                        $data['kpis']['today_confirmed'] = $cnt;
                    } elseif ($status === 'in_progress') {
                        $data['kpis']['today_in_progress'] = $cnt;
                    } elseif ($status === 'completed') {
                        $data['kpis']['today_completed'] = $cnt;
                    }
                }
                $data['kpis']['today_total'] = $todayTotal;

                $sqlUpcoming = "
                    SELECT
                        a.id,
                        a.start_at,
                        a.status,
                        COALESCE(pat.name, '') AS patient_name,
                        COALESCE(s.name, '') AS service_name,
                        COALESCE(pro.name, '') AS professional_name
                    FROM appointments a
                    LEFT JOIN patients pat
                           ON pat.id = a.patient_id
                          AND pat.clinic_id = a.clinic_id
                          AND pat.deleted_at IS NULL
                    LEFT JOIN services s
                           ON s.id = a.service_id
                          AND s.clinic_id = a.clinic_id
                          AND s.deleted_at IS NULL
                    LEFT JOIN professionals pro
                           ON pro.id = a.professional_id
                          AND pro.clinic_id = a.clinic_id
                          AND pro.deleted_at IS NULL
                    WHERE a.clinic_id = :clinic_id
                      AND a.deleted_at IS NULL
                      AND a.status <> 'cancelled'
                      AND a.start_at >= NOW()
                    ORDER BY a.start_at ASC
                    LIMIT 6
                ";
                $stmtUpcoming = $pdo->prepare($sqlUpcoming);
                $stmtUpcoming->execute(['clinic_id' => $clinicId]);
                $data['upcoming_appointments'] = $stmtUpcoming->fetchAll() ?: [];
            }

            $monthStart = date('Y-m-01');
            $monthEnd = date('Y-m-d');

            if ($can('patients.read')) {
                $sqlNewPatients = "
                    SELECT COUNT(*) AS cnt
                    FROM patients
                    WHERE clinic_id = :clinic_id
                      AND deleted_at IS NULL
                      AND DATE(created_at) BETWEEN :start AND :end
                ";
                $stmtPatients = $pdo->prepare($sqlNewPatients);
                $stmtPatients->execute(['clinic_id' => $clinicId, 'start' => $monthStart, 'end' => $monthEnd]);
                $rowPatients = $stmtPatients->fetch() ?: [];
                $data['kpis']['new_patients_month'] = (int)($rowPatients['cnt'] ?? 0);
            }

            if ($can('finance.sales.read')) {
                $sqlRevenuePaid = "
                    SELECT COALESCE(SUM(s.total_liquido), 0) AS revenue_paid
                    FROM sales s
                    WHERE s.clinic_id = :clinic_id
                      AND s.deleted_at IS NULL
                      AND s.status = 'paid'
                      AND DATE(s.created_at) BETWEEN :start AND :end
                ";
                $stmtRev = $pdo->prepare($sqlRevenuePaid);
                $stmtRev->execute(['clinic_id' => $clinicId, 'start' => $monthStart, 'end' => $monthEnd]);
                $rowRev = $stmtRev->fetch() ?: [];
                $data['kpis']['revenue_paid_month'] = (float)($rowRev['revenue_paid'] ?? 0.0);
            }

            if ($can('stock.alerts.read')) {
                try {
                    $alerts = (new StockService($this->container))->alerts(30);
                    $data['stock_alerts'] = [
                        'low_stock' => count($alerts['low_stock'] ?? []),
                        'out_of_stock' => count($alerts['out_of_stock'] ?? []),
                        'expiring_soon' => count($alerts['expiring_soon'] ?? []),
                        'expired' => count($alerts['expired'] ?? []),
                    ];
                } catch (\Throwable $e) {
                }
            }
        }

        return $this->view('dashboard/index', $data);
    }
}
