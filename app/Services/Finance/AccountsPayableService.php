<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Core\Container\Container;
use App\Repositories\AccountsPayableInstallmentRepository;
use App\Repositories\AccountsPayableRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\CostCenterRepository;
use App\Repositories\FinancialEntryLogRepository;
use App\Repositories\FinancialEntryRepository;
use App\Services\Auth\AuthService;

final class AccountsPayableService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listProjectedInstallments(string $from, string $to, string $status = 'open'): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new AccountsPayableInstallmentRepository($this->container->get(\PDO::class));
        return $repo->listByClinicRange((int)$clinicId, $from, $to, $status, 5000);
    }

    /** @param array{vendor_name?:string,title:string,description?:string,cost_center_id?:int,payable_type?:string,start_due_date:string,amount:string,total_installments?:int,recurrence_until?:string} $data */
    public function create(array $data, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $vendor = isset($data['vendor_name']) ? trim((string)$data['vendor_name']) : null;
        $title = trim((string)($data['title'] ?? ''));
        $description = isset($data['description']) ? trim((string)$data['description']) : null;
        $costCenterId = isset($data['cost_center_id']) ? (int)$data['cost_center_id'] : 0;
        $payableType = isset($data['payable_type']) ? trim((string)$data['payable_type']) : 'single';
        $startDue = trim((string)($data['start_due_date'] ?? ''));
        $amount = $this->parseMoney((string)($data['amount'] ?? ''));

        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }
        if ($startDue === '' || \DateTimeImmutable::createFromFormat('Y-m-d', $startDue) === false) {
            throw new \RuntimeException('Vencimento inválido.');
        }
        if ($amount <= 0) {
            throw new \RuntimeException('Valor inválido.');
        }

        $allowedTypes = ['single', 'installments', 'recurring_monthly'];
        if (!in_array($payableType, $allowedTypes, true)) {
            $payableType = 'single';
        }

        $pdo = $this->container->get(\PDO::class);

        $ccId = null;
        if ($costCenterId > 0) {
            $ccRepo = new CostCenterRepository($pdo);
            if ($ccRepo->findById((int)$clinicId, $costCenterId) === null) {
                throw new \RuntimeException('Centro de custo inválido.');
            }
            $ccId = $costCenterId;
        }

        $apRepo = new AccountsPayableRepository($pdo);
        $instRepo = new AccountsPayableInstallmentRepository($pdo);

        $totalInstallments = null;
        $recurrenceInterval = null;
        $recurrenceUntil = null;

        $pdo->beginTransaction();
        try {
            if ($payableType === 'installments') {
                $n = isset($data['total_installments']) ? (int)$data['total_installments'] : 1;
                $n = max(1, min(60, $n));
                $totalInstallments = $n;
            }

            if ($payableType === 'recurring_monthly') {
                $recurrenceInterval = 'monthly';
                $until = isset($data['recurrence_until']) ? trim((string)$data['recurrence_until']) : '';
                if ($until !== '' && \DateTimeImmutable::createFromFormat('Y-m-d', $until) !== false) {
                    $recurrenceUntil = $until;
                } else {
                    $recurrenceUntil = (new \DateTimeImmutable($startDue))->modify('+11 months')->format('Y-m-d');
                }
            }

            $payableId = $apRepo->create(
                (int)$clinicId,
                $vendor,
                $title,
                $description,
                $ccId,
                $payableType,
                'active',
                $startDue,
                $totalInstallments,
                $recurrenceInterval,
                $recurrenceUntil,
                $actorId
            );

            if ($payableType === 'single') {
                $instRepo->create((int)$clinicId, $payableId, 1, $startDue, number_format($amount, 2, '.', ''), 'open');
            } elseif ($payableType === 'installments') {
                $per = $amount;
                for ($i = 1; $i <= (int)$totalInstallments; $i++) {
                    $due = (new \DateTimeImmutable($startDue))->modify('+' . ($i - 1) . ' month')->format('Y-m-d');
                    $instRepo->create((int)$clinicId, $payableId, $i, $due, number_format($per, 2, '.', ''), 'open');
                }
            } else {
                $start = new \DateTimeImmutable($startDue);
                $end = new \DateTimeImmutable((string)$recurrenceUntil);
                $i = 1;
                $cursor = $start;
                while ($cursor <= $end && $i <= 120) {
                    $instRepo->create((int)$clinicId, $payableId, $i, $cursor->format('Y-m-d'), number_format($amount, 2, '.', ''), 'open');
                    $cursor = $cursor->modify('+1 month');
                    $i++;
                }
            }

            $audit = new AuditLogRepository($pdo);
            $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
            $audit->log($actorId, (int)$clinicId, 'finance.ap.create', ['payable_id' => $payableId], $ip, $roleCodes, 'accounts_payable', $payableId, $userAgent);

            $pdo->commit();
            return $payableId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function markInstallmentPaid(int $installmentId, string $paidOn, ?string $method, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $paidOn = trim($paidOn);
        if ($paidOn === '' || \DateTimeImmutable::createFromFormat('Y-m-d', $paidOn) === false) {
            $paidOn = date('Y-m-d');
        }

        $pdo = $this->container->get(\PDO::class);
        $instRepo = new AccountsPayableInstallmentRepository($pdo);
        $inst = $instRepo->findById((int)$clinicId, $installmentId);
        if ($inst === null) {
            throw new \RuntimeException('Parcela inválida.');
        }

        if ((string)$inst['status'] !== 'open') {
            throw new \RuntimeException('Parcela já está paga/cancelada.');
        }

        $apRepo = new AccountsPayableRepository($pdo);
        $payable = $apRepo->findById((int)$clinicId, (int)$inst['payable_id']);
        if ($payable === null) {
            throw new \RuntimeException('Conta inválida.');
        }

        $amount = (float)($inst['amount'] ?? 0);
        if ($amount <= 0) {
            throw new \RuntimeException('Valor inválido.');
        }

        $desc = trim((string)($payable['title'] ?? ''));
        $vendor = trim((string)($payable['vendor_name'] ?? ''));
        if ($vendor !== '') {
            $desc = $vendor . ' - ' . $desc;
        }
        $desc = $desc !== '' ? $desc : ('Conta #' . (int)$payable['id']);
        $desc .= ' (parcela ' . (int)$inst['installment_no'] . ')';

        $entryRepo = new FinancialEntryRepository($pdo);
        $entryId = $entryRepo->create(
            (int)$clinicId,
            'out',
            $paidOn,
            number_format($amount, 2, '.', ''),
            ($method === '' ? null : $method),
            'posted',
            $payable['cost_center_id'] !== null ? (int)$payable['cost_center_id'] : null,
            null,
            null,
            $desc,
            $actorId
        );

        (new FinancialEntryLogRepository($pdo))->log((int)$clinicId, $entryId, 'finance.ap.pay', null, ['installment_id' => $installmentId, 'payable_id' => (int)$payable['id']], $actorId, $ip);

        $instRepo->markPaid((int)$clinicId, $installmentId, $paidOn . ' 00:00:00', $entryId);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, (int)$clinicId, 'finance.ap.installments.pay', ['installment_id' => $installmentId, 'entry_id' => $entryId], $ip, $roleCodes, 'accounts_payable_installment', $installmentId, $userAgent);
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
