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
        $id = $repo->create($clinicId, $name);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.cost_centers.create', ['cost_center_id' => $id], $ip, $roleCodes, 'cost_center', $id, $userAgent);

        return $id;
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

        return [
            'from' => $from,
            'to' => $to,
            'by_professional' => is_array($byProf) ? $byProf : [],
            'by_service' => is_array($bySvc) ? $bySvc : [],
            'ticket_medio' => $ticket,
            'appointments' => $appointments,
            'paid_sales' => $paidSales,
            'conversion_rate' => $conversion,
            'recurring_revenue' => $recurring,
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
