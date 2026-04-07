<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Controllers\Controller;
use App\Core\Http\Request;

final class SystemDashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            return $this->redirect('/');
        }

        $pdo = $this->container->get(\PDO::class);

        try { $totalClinics = (int)$pdo->query("SELECT COUNT(*) FROM clinics WHERE deleted_at IS NULL")->fetchColumn(); } catch (\Throwable $e) { $totalClinics = 0; }
        try { $activeClinics = (int)$pdo->query("SELECT COUNT(*) FROM clinics WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn(); } catch (\Throwable $e) { $activeClinics = 0; }

        $subsByStatus = [];
        try {
            $subStmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM clinic_subscriptions GROUP BY status");
            foreach ($subStmt->fetchAll() as $r) { $subsByStatus[(string)$r['status']] = (int)$r['cnt']; }
        } catch (\Throwable $e) {}

        try { $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn(); } catch (\Throwable $e) { $totalUsers = 0; }
        try { $totalPatients = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE deleted_at IS NULL")->fetchColumn(); } catch (\Throwable $e) { $totalPatients = 0; }
        try { $todayAppts = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE deleted_at IS NULL AND DATE(start_at) = CURDATE()")->fetchColumn(); } catch (\Throwable $e) { $todayAppts = 0; }
        try { $queuePending = (int)$pdo->query("SELECT COUNT(*) FROM queue_jobs WHERE status = 'pending'")->fetchColumn(); } catch (\Throwable $e) { $queuePending = 0; }
        try { $queueDead = (int)$pdo->query("SELECT COUNT(*) FROM queue_jobs WHERE status = 'dead'")->fetchColumn(); } catch (\Throwable $e) { $queueDead = 0; }
        try { $recentErrors = (int)$pdo->query("SELECT COUNT(*) FROM system_error_logs WHERE created_at >= NOW() - INTERVAL 24 HOUR")->fetchColumn(); } catch (\Throwable $e) { $recentErrors = 0; }

        $recentClinics = [];
        try { $recentClinics = $pdo->query("SELECT id, name, status, created_at FROM clinics WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 5")->fetchAll(); } catch (\Throwable $e) {}

        $mrr = 0.0;
        try {
            $mrr = (float)$pdo->query("SELECT COALESCE(SUM(p.price_cents), 0) / 100 FROM clinic_subscriptions cs JOIN saas_plans p ON p.id = cs.plan_id WHERE cs.status IN ('active', 'trial')")->fetchColumn();
        } catch (\Throwable $e) {}

        return $this->view('dashboard/admin', [
            'total_clinics' => $totalClinics,
            'active_clinics' => $activeClinics,
            'subs_by_status' => $subsByStatus,
            'total_users' => $totalUsers,
            'total_patients' => $totalPatients,
            'today_appts' => $todayAppts,
            'queue_pending' => $queuePending,
            'queue_dead' => $queueDead,
            'recent_errors' => $recentErrors,
            'recent_clinics' => $recentClinics,
            'mrr' => $mrr,
        ]);
    }
}
