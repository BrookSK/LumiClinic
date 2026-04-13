<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\CostCenterRepository;
use App\Repositories\DataVersionRepository;
use App\Repositories\FinancialEntryLogRepository;
use App\Repositories\FinancialEntryRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\SaleItemRepository;
use App\Repositories\SaleRepository;
use App\Services\Auth\AuthService;

final class FinancialService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listCostCenters(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new CostCenterRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId);
    }

    public function createCostCenter(string $name, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $name = trim($name);
        if ($name === '') {
            throw new \RuntimeException('Nome é obrigatório.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new CostCenterRepository($pdo);

        $existing = $repo->findByNameIncludingDeleted($clinicId, $name);
        if ($existing !== null) {
            $deletedAt = $existing['deleted_at'] ?? null;
            if ($deletedAt !== null && trim((string)$deletedAt) !== '') {
                $id = (int)$existing['id'];
                $repo->restore($clinicId, $id);
            } else {
                throw new \RuntimeException('Já existe um centro de custo com este nome.');
            }
        } else {
            $id = $repo->create($clinicId, $name);
        }

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.cost_centers.create', ['cost_center_id' => $id], $ip, $roleCodes, 'cost_center', $id, $userAgent);

        return $id;
    }

    /** @return list<array<string,mixed>> */
    public function listAllCostCenters(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new CostCenterRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId);
    }

    /** @return array<string,mixed>|null */
    public function getCostCenter(int $id): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new CostCenterRepository($this->container->get(\PDO::class));
        return $repo->findById($clinicId, $id);
    }

    public function updateCostCenter(int $id, string $name, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $name = trim($name);
        if ($id <= 0 || $name === '') {
            throw new \RuntimeException('Preencha os campos obrigatórios.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new CostCenterRepository($pdo);
        if ($repo->findById($clinicId, $id) === null) {
            throw new \RuntimeException('Centro de custo inválido.');
        }

        $repo->update($clinicId, $id, $name);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.cost_centers.update', ['cost_center_id' => $id], $ip, $roleCodes, 'cost_center', $id, $userAgent);
    }

    public function setCostCenterStatus(int $id, string $status, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            throw new \RuntimeException('Centro de custo inválido.');
        }

        $status = trim($status);
        if (!in_array($status, ['active', 'disabled'], true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new CostCenterRepository($pdo);
        if ($repo->findById($clinicId, $id) === null) {
            throw new \RuntimeException('Centro de custo inválido.');
        }

        $repo->setStatus($clinicId, $id, $status);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.cost_centers.set_status', ['cost_center_id' => $id, 'status' => $status], $ip, $roleCodes, 'cost_center', $id, $userAgent);
    }

    public function deleteCostCenter(int $id, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            throw new \RuntimeException('Centro de custo inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new CostCenterRepository($pdo);
        if ($repo->findById($clinicId, $id) === null) {
            throw new \RuntimeException('Centro de custo inválido.');
        }

        $repo->softDelete($clinicId, $id);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.cost_centers.delete', ['cost_center_id' => $id], $ip, $roleCodes, 'cost_center', $id, $userAgent);
    }

    /** @return array{from:string,to:string,entries:list<array<string,mixed>>,totals:array{in:float,out:float,balance:float}} */
    public function listEntries(string $from, string $to, int $limit = 200, int $offset = 0): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $from = $from === '' ? date('Y-m-01') : $from;
        $to = $to === '' ? date('Y-m-d') : $to;

        $limit = max(25, min($limit, 500));
        $offset = max(0, $offset);

        $repo = new FinancialEntryRepository($this->container->get(\PDO::class));
        $entries = $repo->listByClinicRange($clinicId, $from, $to, $limit, $offset);

        $totals = $repo->summarizeTotalsByClinicRange($clinicId, $from, $to);
        $in = (float)$totals['in_total'];
        $out = (float)$totals['out_total'];

        return [
            'from' => $from,
            'to' => $to,
            'entries' => $entries,
            'totals' => [
                'in' => $in,
                'out' => $out,
                'balance' => $in - $out,
            ],
        ];
    }

    public function createEntry(
        string $kind,
        string $occurredOn,
        string $amountStr,
        ?string $method,
        ?int $costCenterId,
        ?string $description,
        string $ip,
        ?string $userAgent = null
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $kind = trim($kind);
        if (!in_array($kind, ['in', 'out'], true)) {
            throw new \RuntimeException('Tipo inválido.');
        }

        $occurredOn = $occurredOn === '' ? date('Y-m-d') : $occurredOn;

        $amount = $this->parseMoney($amountStr);
        if ($amount <= 0) {
            throw new \RuntimeException('Valor inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        if ($costCenterId !== null) {
            $ccRepo = new CostCenterRepository($pdo);
            if ($ccRepo->findById($clinicId, $costCenterId) === null) {
                throw new \RuntimeException('Centro de custo inválido.');
            }
        }

        $repo = new FinancialEntryRepository($pdo);
        $id = $repo->create(
            $clinicId,
            $kind,
            $occurredOn,
            number_format($amount, 2, '.', ''),
            $method,
            'posted',
            $costCenterId,
            null,
            null,
            $description,
            $actorId
        );

        $log = new FinancialEntryLogRepository($pdo);
        $log->log($clinicId, $id, 'financial_entries.create', null, ['amount' => $amount, 'kind' => $kind], $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.entries.create', ['entry_id' => $id], $ip, $roleCodes, 'financial_entry', $id, $userAgent);

        return $id;
    }

    public function deleteEntry(int $entryId, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new FinancialEntryRepository($pdo);
        $before = $repo->findById($clinicId, $entryId);
        if ($before === null) {
            throw new \RuntimeException('Lançamento inválido.');
        }

        (new DataVersionRepository($pdo))->record(
            $clinicId,
            'financial_entry',
            $entryId,
            'finance.entries.delete',
            $before,
            $actorId,
            $ip,
            $userAgent
        );

        $repo->softDelete($clinicId, $entryId);

        $log = new FinancialEntryLogRepository($pdo);
        $log->log($clinicId, $entryId, 'financial_entries.delete', $before, null, $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.entries.delete', ['entry_id' => $entryId], $ip, $roleCodes, 'financial_entry', $entryId, $userAgent);
    }

    /** @return array{from:string,to:string,by_professional:list<array<string,mixed>>,by_service:list<array<string,mixed>>,ticket_medio:float,appointments:int,paid_sales:int,conversion_rate:float,recurring_revenue:float} */
    public function reports(string $from, string $to, ?int $professionalId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $from = $from === '' ? date('Y-m-01') : $from;
        $to = $to === '' ? date('Y-m-d') : $to;

        $pdo = $this->container->get(\PDO::class);

        $profFilter = '';
        $params = [
            'clinic_id' => $clinicId,
            'from_dt' => $from . ' 00:00:00',
            'to_dt' => $to . ' 23:59:59',
        ];

        if ($professionalId !== null && $professionalId > 0) {
            $profRepo = new ProfessionalRepository($pdo);
            if ($profRepo->findById($clinicId, $professionalId) === null) {
                throw new \RuntimeException('Profissional inválido.');
            }

            $profFilter = ' AND si.professional_id = :professional_id ';
            $params['professional_id'] = $professionalId;
        }

        $sqlByProf = "
            SELECT
                si.professional_id,
                SUM(
                    si.subtotal * (s.total_liquido / NULLIF(s.total_bruto, 0))
                ) AS revenue
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id AND s.deleted_at IS NULL
            WHERE si.clinic_id = :clinic_id
              AND si.deleted_at IS NULL
              AND s.created_at BETWEEN :from_dt AND :to_dt
              AND s.status = 'paid'
              " . $profFilter . "
            GROUP BY si.professional_id
            ORDER BY revenue DESC
        ";

        $stmt = $pdo->prepare($sqlByProf);
        $stmt->execute($params);
        $byProf = $stmt->fetchAll();

        $sqlBySvc = "
            SELECT
                si.reference_id AS service_id,
                SUM(
                    si.subtotal * (s.total_liquido / NULLIF(s.total_bruto, 0))
                ) AS revenue
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id AND s.deleted_at IS NULL
            WHERE si.clinic_id = :clinic_id
              AND si.deleted_at IS NULL
              AND si.type = 'procedure'
              AND s.created_at BETWEEN :from_dt AND :to_dt
              AND s.status = 'paid'
              " . $profFilter . "
            GROUP BY si.reference_id
            ORDER BY revenue DESC
        ";

        $stmt2 = $pdo->prepare($sqlBySvc);
        $stmt2->execute($params);
        $bySvc = $stmt2->fetchAll();

        $sqlCostsByProf = "
            SELECT
                pp.professional_id,
                SUM(pp.stock_total_cost_snapshot) AS cost
            FROM procedure_performed pp
            WHERE pp.clinic_id = :clinic_id
              AND pp.deleted_at IS NULL
              AND COALESCE(pp.real_ended_at, pp.created_at) BETWEEN :from_dt AND :to_dt
              " . ($professionalId !== null && $professionalId > 0 ? ' AND pp.professional_id = :professional_id ' : '') . "
            GROUP BY pp.professional_id
        ";

        $stmtC1 = $pdo->prepare($sqlCostsByProf);
        $stmtC1->execute($params);
        $costsByProfRows = $stmtC1->fetchAll();

        $costByProfMap = [];
        foreach (is_array($costsByProfRows) ? $costsByProfRows : [] as $r) {
            $pid = isset($r['professional_id']) ? (int)$r['professional_id'] : 0;
            if ($pid <= 0) {
                continue;
            }
            $costByProfMap[$pid] = (float)($r['cost'] ?? 0);
        }

        $sqlCostsBySvc = "
            SELECT
                pp.service_id,
                SUM(pp.stock_total_cost_snapshot) AS cost
            FROM procedure_performed pp
            WHERE pp.clinic_id = :clinic_id
              AND pp.deleted_at IS NULL
              AND COALESCE(pp.real_ended_at, pp.created_at) BETWEEN :from_dt AND :to_dt
              " . ($professionalId !== null && $professionalId > 0 ? ' AND pp.professional_id = :professional_id ' : '') . "
            GROUP BY pp.service_id
        ";

        $stmtC2 = $pdo->prepare($sqlCostsBySvc);
        $stmtC2->execute($params);
        $costsBySvcRows = $stmtC2->fetchAll();

        $costBySvcMap = [];
        foreach (is_array($costsBySvcRows) ? $costsBySvcRows : [] as $r) {
            $sid = isset($r['service_id']) ? (int)$r['service_id'] : 0;
            if ($sid <= 0) {
                continue;
            }
            $costBySvcMap[$sid] = (float)($r['cost'] ?? 0);
        }

        $byProf = is_array($byProf) ? $byProf : [];
        foreach ($byProf as &$r) {
            $pid = $r['professional_id'] === null ? 0 : (int)$r['professional_id'];
            $rev = (float)($r['revenue'] ?? 0);
            $cost = $pid > 0 && isset($costByProfMap[$pid]) ? (float)$costByProfMap[$pid] : 0.0;
            $r['cost'] = $cost;
            $r['margin'] = $rev - $cost;
        }
        unset($r);

        $bySvc = is_array($bySvc) ? $bySvc : [];
        foreach ($bySvc as &$r) {
            $sid = (int)($r['service_id'] ?? 0);
            $rev = (float)($r['revenue'] ?? 0);
            $cost = $sid > 0 && isset($costBySvcMap[$sid]) ? (float)$costBySvcMap[$sid] : 0.0;
            $r['cost'] = $cost;
            $r['margin'] = $rev - $cost;
        }
        unset($r);

        $sqlTicket = "
            SELECT
                AVG(s.total_liquido) AS avg_ticket
            FROM sales s
            WHERE s.clinic_id = :clinic_id
              AND s.deleted_at IS NULL
              AND s.status = 'paid'
              AND s.created_at BETWEEN :from_dt AND :to_dt
        ";
        $stmt3 = $pdo->prepare($sqlTicket);
        $stmt3->execute([
            'clinic_id' => $clinicId,
            'from_dt' => $from . ' 00:00:00',
            'to_dt' => $to . ' 23:59:59',
        ]);
        $row = $stmt3->fetch();
        $ticket = is_array($row) && isset($row['avg_ticket']) ? (float)$row['avg_ticket'] : 0.0;

        $sqlAppointments = "
            SELECT COUNT(1) AS cnt
            FROM appointments a
            WHERE a.clinic_id = :clinic_id
              AND a.deleted_at IS NULL
              AND a.start_at BETWEEN :from_dt AND :to_dt
        ";
        $stmt4 = $pdo->prepare($sqlAppointments);
        $stmt4->execute([
            'clinic_id' => $clinicId,
            'from_dt' => $from . ' 00:00:00',
            'to_dt' => $to . ' 23:59:59',
        ]);
        $rowA = $stmt4->fetch();
        $appointments = is_array($rowA) && isset($rowA['cnt']) ? (int)$rowA['cnt'] : 0;

        $sqlPaidSales = "
            SELECT COUNT(1) AS cnt
            FROM sales s
            WHERE s.clinic_id = :clinic_id
              AND s.deleted_at IS NULL
              AND s.status = 'paid'
              AND s.created_at BETWEEN :from_dt AND :to_dt
        ";
        $stmt5 = $pdo->prepare($sqlPaidSales);
        $stmt5->execute([
            'clinic_id' => $clinicId,
            'from_dt' => $from . ' 00:00:00',
            'to_dt' => $to . ' 23:59:59',
        ]);
        $rowS = $stmt5->fetch();
        $paidSales = is_array($rowS) && isset($rowS['cnt']) ? (int)$rowS['cnt'] : 0;

        $conversion = $appointments > 0 ? ($paidSales / $appointments) : 0.0;

        $sqlRecurring = "
            SELECT
                SUM(
                    si.subtotal * (s.total_liquido / NULLIF(s.total_bruto, 0))
                ) AS recurring
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id AND s.deleted_at IS NULL
            WHERE si.clinic_id = :clinic_id
              AND si.deleted_at IS NULL
              AND si.type = 'subscription'
              AND s.status = 'paid'
              AND s.created_at BETWEEN :from_dt AND :to_dt
              " . $profFilter . "
        ";
        $stmt6 = $pdo->prepare($sqlRecurring);
        $stmt6->execute($params);
        $rowR = $stmt6->fetch();
        $recurring = is_array($rowR) && isset($rowR['recurring']) ? (float)$rowR['recurring'] : 0.0;

        $inOut = (new FinancialEntryRepository($pdo))->summarizeTotalsByClinicRange((int)$clinicId, $from, $to);
        $inTotal = (float)($inOut['in_total'] ?? 0);
        $outTotal = (float)($inOut['out_total'] ?? 0);

        $sqlRevenue = "
            SELECT
                COALESCE(SUM(s.total_liquido), 0) AS revenue
            FROM sales s
            " . ($professionalId !== null && $professionalId > 0
                ? " INNER JOIN sale_items si
                        ON si.sale_id = s.id
                       AND si.deleted_at IS NULL
                       AND si.professional_id = :professional_id "
                : "") . "
            WHERE s.clinic_id = :clinic_id
              AND s.deleted_at IS NULL
              AND s.status = 'paid'
              AND s.created_at BETWEEN :from_dt AND :to_dt
        ";
        $stmtRev = $pdo->prepare($sqlRevenue);
        $stmtRev->execute($params);
        $rowRev = $stmtRev->fetch();
        $revenueTotal = is_array($rowRev) && isset($rowRev['revenue']) ? (float)$rowRev['revenue'] : 0.0;

        $sqlRecentSales = "
            SELECT DISTINCT
                   s.id,
                   s.patient_id,
                   COALESCE(p.name, '') AS patient_name,
                   s.total_liquido,
                   s.status,
                   s.created_at
            FROM sales s
            LEFT JOIN patients p
                   ON p.id = s.patient_id
                  AND p.clinic_id = s.clinic_id
                  AND p.deleted_at IS NULL
            " . ($professionalId !== null && $professionalId > 0
                ? " INNER JOIN sale_items si
                        ON si.sale_id = s.id
                       AND si.deleted_at IS NULL
                       AND si.professional_id = :professional_id "
                : "") . "
            WHERE s.clinic_id = :clinic_id
              AND s.deleted_at IS NULL
              AND s.status = 'paid'
              AND s.created_at BETWEEN :from_dt AND :to_dt
            ORDER BY s.id DESC
            LIMIT 10
        ";
        $stmtRS = $pdo->prepare($sqlRecentSales);
        $stmtRS->execute($params);
        $recentSales = $stmtRS->fetchAll();

        $recentEntries = (new FinancialEntryRepository($pdo))->listByClinicRange((int)$clinicId, $from, $to, 10, 0);

        // Budget status counts for follow-up
        $sqlBudgetStatus = "
            SELECT budget_status, COUNT(*) AS cnt
            FROM sales
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status <> 'cancelled'
              AND created_at BETWEEN :from_dt AND :to_dt
            GROUP BY budget_status
        ";
        $stmtBS = $pdo->prepare($sqlBudgetStatus);
        $stmtBS->execute([
            'clinic_id' => $clinicId,
            'from_dt' => $from . ' 00:00:00',
            'to_dt' => $to . ' 23:59:59',
        ]);
        $budgetStatusCounts = [];
        foreach ($stmtBS->fetchAll() as $r) {
            $budgetStatusCounts[(string)$r['budget_status']] = (int)$r['cnt'];
        }

        return [
            'from' => $from,
            'to' => $to,
            'by_professional' => $byProf,
            'by_service' => $bySvc,
            'ticket_medio' => $ticket,
            'appointments' => $appointments,
            'paid_sales' => $paidSales,
            'conversion_rate' => $conversion,
            'recurring_revenue' => $recurring,
            'kpi_in_total' => $inTotal,
            'kpi_out_total' => $outTotal,
            'kpi_net_total' => ($inTotal - $outTotal),
            'kpi_revenue_total' => $revenueTotal,
            'recent_sales' => is_array($recentSales) ? $recentSales : [],
            'recent_entries' => is_array($recentEntries) ? $recentEntries : [],
            'budget_status_counts' => $budgetStatusCounts,
        ];
    }

    private function parseMoney(string $raw): float
    {
        $s = trim($raw);
        if ($s === '') {
            return 0.0;
        }

        $s = str_replace(['R$', ' '], '', $s);

        if (substr_count($s, ',') > 0 && substr_count($s, '.') === 0) {
            $s = str_replace(',', '.', $s);
        }

        $s = preg_replace('/[^0-9.\-]/', '', $s);
        $s = $s === null ? '' : $s;

        if ($s === '' || $s === '-' || $s === '.') {
            return 0.0;
        }

        return (float)$s;
    }
}
