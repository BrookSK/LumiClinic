<?php

declare(strict_types=1);

namespace App\Controllers\Reports;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\PerformanceLogRepository;
use App\Repositories\SystemMetricRepository;
use App\Services\Auth\AuthService;

final class ReportsController extends Controller
{
    public function metricsCsv(Request $request): Response
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return Response::html('Contexto inválido.', 403);
        }

        $items = (new SystemMetricRepository($this->container->get(\PDO::class)))->latestByClinic($clinicId, 200);

        $csv = "metric,value,reference_date,created_at\n";
        foreach ($items as $it) {
            $csv .= $this->csvRow([
                (string)$it['metric'],
                (string)$it['value'],
                (string)$it['reference_date'],
                (string)$it['created_at'],
            ]);
        }

        return Response::raw($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="metrics.csv"',
        ]);
    }

    public function performanceCsv(Request $request): Response
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }

        $pdo = $this->container->get(\PDO::class);

        $stmt = $pdo->query("\n            SELECT endpoint, method, response_time_ms, status_code, clinic_id, created_at
            FROM performance_logs
            ORDER BY id DESC
            LIMIT 2000
        ");
        $items = $stmt->fetchAll();

        $csv = "endpoint,method,response_time_ms,status_code,clinic_id,created_at\n";
        foreach ($items as $it) {
            $csv .= $this->csvRow([
                (string)$it['endpoint'],
                (string)$it['method'],
                (string)$it['response_time_ms'],
                (string)$it['status_code'],
                (string)($it['clinic_id'] ?? ''),
                (string)$it['created_at'],
            ]);
        }

        return Response::raw($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="performance.csv"',
        ]);
    }

    /** @param list<string> $cols */
    private function csvRow(array $cols): string
    {
        $escaped = array_map(function (string $v): string {
            $v = str_replace('"', '""', $v);
            return '"' . $v . '"';
        }, $cols);

        return implode(',', $escaped) . "\n";
    }
}
