<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\DataVersionRepository;
use App\Repositories\PackageRepository;
use App\Repositories\PatientPackageRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientProcedureRepository;
use App\Repositories\PatientSubscriptionRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\SaleItemRepository;
use App\Repositories\SaleLogRepository;
use App\Repositories\SaleRepository;
use App\Repositories\AppointmentRepository;
use App\Repositories\ServiceCatalogRepository;
use App\Repositories\SubscriptionPlanRepository;
use App\Services\Auth\AuthService;
use App\Services\Scheduling\AppointmentService;
use App\Services\Scheduling\AvailabilityService;
use App\Services\Observability\SystemEvent;

final class SalesService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listSales(?int $professionalId = null, int $limit = 200, int $offset = 0, ?int $patientId = null, ?string $budgetStatus = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $limit = max(1, min($limit, 500));
        $offset = max(0, $offset);

        $repo = new SaleRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId, $limit, $professionalId, $offset, $patientId, $budgetStatus);
    }

    /** @return array{sale:array<string,mixed>,items:list<array<string,mixed>>,payments:list<array<string,mixed>>,logs:list<array<string,mixed>>}|null */
    public function getSale(int $saleId): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            return null;
        }

        // Enrich with patient name
        if ($sale['patient_id'] !== null) {
            $patRepo = new PatientRepository($pdo);
            $pat = $patRepo->findById($clinicId, (int)$sale['patient_id']);
            $sale['patient_name'] = $pat !== null ? (string)($pat['name'] ?? '') : '';
            $sale['patient_email'] = $pat !== null ? trim((string)($pat['email'] ?? '')) : '';
            $sale['patient_phone'] = $pat !== null ? trim((string)($pat['phone'] ?? '')) : '';
        } else {
            $sale['patient_name'] = '';
            $sale['patient_email'] = '';
            $sale['patient_phone'] = '';
        }

        $itemsRepo = new SaleItemRepository($pdo);
        $payRepo = new PaymentRepository($pdo);
        $logRepo = new SaleLogRepository($pdo);
        $pprocRepo = new PatientProcedureRepository($pdo);

        return [
            'sale' => $sale,
            'items' => $itemsRepo->listBySale($clinicId, $saleId),
            'payments' => $payRepo->listBySale($clinicId, $saleId),
            'logs' => $logRepo->listBySale($clinicId, $saleId, 200),
            'procedures' => $pprocRepo->listBySale($clinicId, $saleId, 200),
        ];
    }

    public function createSale(?int $patientId, string $origin, string $descontoStr, ?string $notes, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $origin = $origin === '' ? 'reception' : $origin;
        $allowedOrigins = ['reception', 'online', 'system'];
        if (!in_array($origin, $allowedOrigins, true)) {
            $origin = 'reception';
        }

        $desconto = $this->parseMoney($descontoStr);
        if ($desconto < 0) {
            $desconto = 0.0;
        }

        $pdo = $this->container->get(\PDO::class);

        if ($patientId !== null) {
            $patRepo = new PatientRepository($pdo);
            if ($patRepo->findById($clinicId, $patientId) === null) {
                throw new \RuntimeException('Paciente inv?lido.');
            }
        }

        $saleRepo = new SaleRepository($pdo);
        $saleId = $saleRepo->create($clinicId, $patientId, $origin, $notes, $desconto, $actorId);

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log($clinicId, $saleId, 'sales.create', ['patient_id' => $patientId, 'origin' => $origin, 'desconto' => $desconto], $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.sales.create', ['sale_id' => $saleId], $ip, $roleCodes, 'sale', $saleId, $userAgent);

        SystemEvent::dispatch($this->container, 'sale.created', [
            'sale_id' => $saleId,
            'patient_id' => $patientId,
            'origin' => $origin,
        ], 'sale', $saleId, $ip, $userAgent);

        return $saleId;
    }

    public function addItem(
        int $saleId,
        string $type,
        int $referenceId,
        ?int $professionalId,
        int $quantity,
        string $unitPriceStr,
        string $ip,
        ?string $userAgent = null
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $type = trim($type);
        $allowedTypes = ['procedure', 'package', 'subscription'];
        if (!in_array($type, $allowedTypes, true)) {
            throw new \RuntimeException('Tipo inv?lido.');
        }

        if ($referenceId <= 0) {
            throw new \RuntimeException('Refer?ncia inv?lida.');
        }

        if ($quantity <= 0) {
            throw new \RuntimeException('Quantidade inv?lida.');
        }

        $unitPrice = $this->parseMoney($unitPriceStr);
        if ($unitPrice < 0) {
            throw new \RuntimeException('Valor inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            throw new \RuntimeException('Venda inv?lida.');
        }

        if ((string)$sale['status'] !== 'open') {
            throw new \RuntimeException('A venda n?o est? aberta.');
        }

        if ($professionalId !== null) {
            $profRepo = new ProfessionalRepository($pdo);
            if ($profRepo->findById($clinicId, $professionalId) === null) {
                throw new \RuntimeException('Profissional inv?lido.');
            }
        }

        $resolvedUnitPrice = $unitPrice;

        if ($type === 'procedure') {
            $svcRepo = new ServiceCatalogRepository($pdo);
            $svc = $svcRepo->findById($clinicId, $referenceId);
            if ($svc === null) {
                throw new \RuntimeException('Servi?o inv?lido.');
            }
            if ($resolvedUnitPrice == 0.0 && isset($svc['price_cents']) && $svc['price_cents'] !== null) {
                $resolvedUnitPrice = ((float)$svc['price_cents']) / 100.0;
            }
        }

        if ($type === 'package') {
            $pkgRepo = new PackageRepository($pdo);
            $pkg = $pkgRepo->findById($clinicId, $referenceId);
            if ($pkg === null) {
                throw new \RuntimeException('Pacote inv?lido.');
            }
            if ($resolvedUnitPrice == 0.0) {
                $resolvedUnitPrice = (float)$pkg['price'];
            }
        }

        if ($type === 'subscription') {
            $planRepo = new SubscriptionPlanRepository($pdo);
            $plan = $planRepo->findById($clinicId, $referenceId);
            if ($plan === null) {
                throw new \RuntimeException('Plano inv?lido.');
            }
            if ($resolvedUnitPrice == 0.0) {
                $resolvedUnitPrice = (float)$plan['price'];
            }
        }

        $subtotal = round($resolvedUnitPrice * $quantity, 2);

        $itemRepo = new SaleItemRepository($pdo);
        $itemId = $itemRepo->create(
            $clinicId,
            $saleId,
            $type,
            $referenceId,
            $professionalId,
            $quantity,
            number_format($resolvedUnitPrice, 2, '.', ''),
            number_format($subtotal, 2, '.', '')
        );

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log(
            $clinicId,
            $saleId,
            'sale_items.create',
            [
                'sale_item_id' => $itemId,
                'type' => $type,
                'reference_id' => $referenceId,
                'professional_id' => $professionalId,
                'quantity' => $quantity,
                'unit_price' => $resolvedUnitPrice,
                'subtotal' => $subtotal,
            ],
            $actorId,
            $ip
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.sales.update', ['sale_id' => $saleId, 'sale_item_id' => $itemId], $ip, $roleCodes, 'sale', $saleId, $userAgent);

        $this->syncDerivedEntitiesFromItem($clinicId, $saleId, $itemId, $actorId, $ip);
        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);

        return $itemId;
    }

    public function removeItem(int $saleId, int $itemId, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            throw new \RuntimeException('Venda inválida.');
        }

        if ((string)$sale['status'] !== 'open') {
            throw new \RuntimeException('Não é possível remover itens de um orçamento que não está aberto.');
        }

        $itemRepo = new SaleItemRepository($pdo);
        $itemRepo->softDelete($clinicId, $itemId);

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log($clinicId, $saleId, 'sale_items.delete', ['sale_item_id' => $itemId], $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.sales.update', ['sale_id' => $saleId, 'removed_item_id' => $itemId], $ip, $roleCodes, 'sale', $saleId, $userAgent);

        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);
    }

    public function addPayment(int $saleId, string $method, string $amountStr, string $status, string $feesStr, ?string $gatewayRef, string $ip, ?string $userAgent = null, ?string $paidAtDate = null, int $installments = 1): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $method = trim($method);
        $allowedMethods = ['pix', 'credit_card', 'debit_card', 'cash', 'boleto', 'card'];
        if (!in_array($method, $allowedMethods, true)) {
            throw new \RuntimeException('Método inválido.');
        }

        $status = trim($status);
        $allowedStatuses = ['pending', 'paid'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $totalAmount = $this->parseMoney($amountStr);
        if ($totalAmount <= 0) {
            throw new \RuntimeException('Valor inválido.');
        }

        $fees = $this->parseMoney($feesStr);
        if ($fees < 0) {
            $fees = 0.0;
        }

        $pdo = $this->container->get(\PDO::class);
        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            throw new \RuntimeException('Venda inválida.');
        }

        if ((string)$sale['status'] === 'cancelled') {
            throw new \RuntimeException('Venda cancelada.');
        }

        $installments = max(1, min(48, $installments));
        $payRepo = new PaymentRepository($pdo);
        $saleLog = new SaleLogRepository($pdo);
        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;

        $firstPaymentId = 0;

        // Calculate installment amount (last one absorbs rounding)
        $installmentAmount = floor($totalAmount / $installments * 100) / 100;
        $lastInstallmentAmount = round($totalAmount - ($installmentAmount * ($installments - 1)), 2);

        // Parse start date
        $startDate = new \DateTimeImmutable('now');
        if ($paidAtDate !== null && trim($paidAtDate) !== '') {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', trim($paidAtDate));
            if ($parsed !== false) {
                $startDate = $parsed;
            }
        }

        for ($i = 1; $i <= $installments; $i++) {
            $isFirst = ($i === 1);
            $isLast = ($i === $installments);
            $amt = $isLast ? $lastInstallmentAmount : $installmentAmount;

            // Due date: first = start date, rest = +N months
            $dueDate = $startDate->modify('+' . ($i - 1) . ' months');

            // Status: first installment uses selected status, rest are pending
            $instStatus = $isFirst ? $status : 'pending';

            $paidAt = null;
            if ($instStatus === 'paid') {
                $paidAt = $dueDate->format('Y-m-d') . ' ' . date('H:i:s');
            }

            // Gateway ref: installment label
            $instRef = $installments > 1 ? ($i . '/' . $installments) : $gatewayRef;

            $paymentId = $payRepo->create(
                $clinicId,
                $saleId,
                $method,
                number_format($amt, 2, '.', ''),
                $instStatus,
                number_format($isFirst ? $fees : 0.0, 2, '.', ''),
                $instRef,
                $paidAt,
                $actorId
            );

            if ($isFirst) {
                $firstPaymentId = $paymentId;
            }

            $saleLog->log($clinicId, $saleId, 'payments.create', [
                'payment_id' => $paymentId,
                'method' => $method,
                'amount' => $amt,
                'status' => $instStatus,
                'installment' => $i . '/' . $installments,
            ], $actorId, $ip);
        }

        $audit->log($actorId, $clinicId, 'finance.payments.create', [
            'sale_id' => $saleId,
            'payment_id' => $firstPaymentId,
            'installments' => $installments,
            'total_amount' => $totalAmount,
        ], $ip, $roleCodes, 'sale', $saleId, $userAgent);

        SystemEvent::dispatch($this->container, 'payment.created', [
            'sale_id' => $saleId,
            'payment_id' => $firstPaymentId,
            'method' => $method,
            'amount' => $totalAmount,
            'status' => $status,
            'installments' => $installments,
        ], 'payment', $firstPaymentId, $ip, $userAgent);

        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);

        return $firstPaymentId;
    }

    public function refundPayment(int $paymentId, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $payRepo = new PaymentRepository($pdo);
        $payment = $payRepo->findById($clinicId, $paymentId);
        if ($payment === null) {
            throw new \RuntimeException('Pagamento inv?lido.');
        }

        (new DataVersionRepository($pdo))->record(
            $clinicId,
            'payment',
            $paymentId,
            'finance.payments.refund',
            $payment,
            $actorId,
            $ip,
            $userAgent
        );

        $status = (string)$payment['status'];
        if ($status === 'refunded') {
            return;
        }

        $saleId = (int)$payment['sale_id'];

        $payRepo->updateStatus($clinicId, $paymentId, 'refunded', null);

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log(
            $clinicId,
            $saleId,
            'payments.refund',
            [
                'payment_id' => $paymentId,
                'previous_status' => $status,
            ],
            $actorId,
            $ip
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.payments.refund', ['sale_id' => $saleId, 'payment_id' => $paymentId], $ip, $roleCodes, 'sale', $saleId, $userAgent);

        SystemEvent::dispatch($this->container, 'payment.refunded', [
            'sale_id' => $saleId,
            'payment_id' => $paymentId,
        ], 'payment', $paymentId, $ip, $userAgent);

        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);
    }

    public function updatePayment(int $paymentId, string $method, string $amountStr, string $status, string $feesStr, ?string $gatewayRef, string $ip, ?string $userAgent = null, ?string $paidAtDate = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $payRepo = new PaymentRepository($pdo);
        $payment = $payRepo->findById($clinicId, $paymentId);
        if ($payment === null) {
            throw new \RuntimeException('Pagamento inválido.');
        }

        $saleId = (int)$payment['sale_id'];

        $method = trim($method);
        $allowedMethods = ['pix', 'credit_card', 'debit_card', 'cash', 'boleto', 'card'];
        if (!in_array($method, $allowedMethods, true)) {
            throw new \RuntimeException('Método inválido.');
        }

        $status = trim($status);
        $allowedStatuses = ['pending', 'paid', 'refunded'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $amount = $this->parseMoney($amountStr);
        if ($amount <= 0) {
            throw new \RuntimeException('Valor inválido.');
        }

        $fees = $this->parseMoney($feesStr);
        if ($fees < 0) $fees = 0.0;

        $paidAt = null;
        if ($status === 'paid') {
            if ($paidAtDate !== null && trim($paidAtDate) !== '') {
                $paidAt = trim($paidAtDate) . ' ' . date('H:i:s');
            } else {
                $paidAt = (string)($payment['paid_at'] ?? '') !== '' ? (string)$payment['paid_at'] : (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            }
        }

        (new DataVersionRepository($pdo))->record($clinicId, 'payment', $paymentId, 'finance.payments.update', $payment, $actorId, $ip, $userAgent);

        $payRepo->updateFull(
            $clinicId,
            $paymentId,
            $method,
            number_format($amount, 2, '.', ''),
            $status,
            number_format($fees, 2, '.', ''),
            $gatewayRef,
            $paidAt
        );

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log($clinicId, $saleId, 'payments.update', ['payment_id' => $paymentId, 'method' => $method, 'amount' => $amount, 'status' => $status], $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.payments.update', ['sale_id' => $saleId, 'payment_id' => $paymentId], $ip, $roleCodes, 'sale', $saleId, $userAgent);

        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);
    }

    public function deletePayment(int $paymentId, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $payRepo = new PaymentRepository($pdo);
        $payment = $payRepo->findById($clinicId, $paymentId);
        if ($payment === null) {
            throw new \RuntimeException('Pagamento inválido.');
        }

        $saleId = (int)$payment['sale_id'];

        (new DataVersionRepository($pdo))->record($clinicId, 'payment', $paymentId, 'finance.payments.delete', $payment, $actorId, $ip, $userAgent);

        $payRepo->softDelete($clinicId, $paymentId);

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log($clinicId, $saleId, 'payments.delete', ['payment_id' => $paymentId], $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.payments.delete', ['sale_id' => $saleId, 'payment_id' => $paymentId], $ip, $roleCodes, 'sale', $saleId, $userAgent);

        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);
    }

    public function cancelSale(int $saleId, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            throw new \RuntimeException('Venda inv?lida.');
        }

        (new DataVersionRepository($pdo))->record(
            $clinicId,
            'sale',
            $saleId,
            'finance.sales.cancel',
            $sale,
            $actorId,
            $ip,
            $userAgent
        );

        if ((string)$sale['status'] === 'cancelled') {
            return;
        }

        $saleRepo->updateStatus($clinicId, $saleId, 'cancelled');

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log($clinicId, $saleId, 'sales.cancel', [], $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.sales.cancel', ['sale_id' => $saleId], $ip, $roleCodes, 'sale', $saleId, $userAgent);
    }

    public function applyDiscount(int $saleId, string $descontoStr, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $desconto = $this->parseMoney($descontoStr);
        if ($desconto < 0) $desconto = 0.0;

        $pdo = $this->container->get(\PDO::class);
        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            throw new \RuntimeException('Venda inválida.');
        }

        // Atualiza desconto e recalcula totais
        $stmt = $pdo->prepare("
            UPDATE sales SET desconto = :desconto, updated_at = NOW()
            WHERE id = :id AND clinic_id = :clinic_id AND deleted_at IS NULL LIMIT 1
        ");
        $stmt->execute(['desconto' => $desconto, 'id' => $saleId, 'clinic_id' => $clinicId]);

        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);

        (new SaleLogRepository($pdo))->log($clinicId, $saleId, 'sales.discount', ['desconto' => $desconto], $actorId, $ip);
    }

    public function setBudgetStatus(int $saleId, string $budgetStatus, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $budgetStatus = trim($budgetStatus);
        $allowed = ['draft', 'sent', 'approved', 'rejected', 'standby', 'completed'];
        if (!in_array($budgetStatus, $allowed, true)) {
            throw new \RuntimeException('Status inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            throw new \RuntimeException('Venda inv?lida.');
        }

        if ((string)$sale['status'] === 'cancelled') {
            throw new \RuntimeException('Venda cancelada.');
        }

        $from = (string)($sale['budget_status'] ?? 'draft');
        if ($from === $budgetStatus) {
            return;
        }

        $saleRepo->updateBudgetStatus($clinicId, $saleId, $budgetStatus);

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log($clinicId, $saleId, 'sales.budget_status', ['from' => $from, 'to' => $budgetStatus], $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.sales.update', ['sale_id' => $saleId, 'budget_status' => $budgetStatus], $ip, $roleCodes, 'sale', $saleId, $userAgent);

        SystemEvent::dispatch($this->container, 'sale.budget_status_updated', [
            'sale_id' => $saleId,
            'from' => $from,
            'to' => $budgetStatus,
        ], 'sale', $saleId, $ip, $userAgent);

        if ($budgetStatus === 'approved') {
            $this->ensurePatientProcedurePlanFromSale($clinicId, $saleId, $actorId, $ip);
        }
    }

    private function ensurePatientProcedurePlanFromSale(int $clinicId, int $saleId, int $actorId, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);

        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            return;
        }

        $patientId = $sale['patient_id'] !== null ? (int)$sale['patient_id'] : null;
        if ($patientId === null) {
            return;
        }

        $itemsRepo = new SaleItemRepository($pdo);
        $items = $itemsRepo->listBySale($clinicId, $saleId);

        $pprocRepo = new PatientProcedureRepository($pdo);
        $created = 0;

        foreach ($items as $it) {
            if ((string)($it['type'] ?? '') !== 'procedure') {
                continue;
            }

            $serviceId = (int)($it['reference_id'] ?? 0);
            $saleItemId = (int)($it['id'] ?? 0);
            if ($serviceId <= 0 || $saleItemId <= 0) {
                continue;
            }

            $professionalId = isset($it['professional_id']) && $it['professional_id'] !== null ? (int)$it['professional_id'] : null;
            $totalSessions = (int)($it['quantity'] ?? 1);

            $pprocRepo->createIfNotExists($clinicId, $patientId, $serviceId, $professionalId, $saleId, $saleItemId, $totalSessions);
            $created++;
        }

        if ($created > 0) {
            $saleLog = new SaleLogRepository($pdo);
            $saleLog->log($clinicId, $saleId, 'patient_procedures.ensure_from_sale', ['count' => $created], $actorId, $ip);
        }
    }

    /** @return array{created:int,skipped:int,errors:list<string>} */
    public function generateAppointmentsFromApprovedBudget(int $saleId, string $startDateYmd, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $startDateYmd);
        if ($date === false) {
            throw new \RuntimeException('Data inv?lida.');
        }

        $pdo = $this->container->get(\PDO::class);

        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            throw new \RuntimeException('Venda inv?lida.');
        }

        if ((string)$sale['status'] === 'cancelled') {
            throw new \RuntimeException('Venda cancelada.');
        }

        if ((string)($sale['budget_status'] ?? 'draft') !== 'approved') {
            throw new \RuntimeException('Orçamento precisa estar aprovado.');
        }

        $patientId = $sale['patient_id'] !== null ? (int)$sale['patient_id'] : null;
        if ($patientId === null) {
            throw new \RuntimeException('Paciente é obrigatório.');
        }

        $pprocRepo = new PatientProcedureRepository($pdo);
        $procedures = $pprocRepo->listBySale($clinicId, $saleId, 500);
        if ($procedures === []) {
            return ['created' => 0, 'skipped' => 0, 'errors' => []];
        }

        $availability = new AvailabilityService($this->container);
        $apptSvc = new AppointmentService($this->container);
        $apptRepo = new AppointmentRepository($pdo);

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($procedures as $pp) {
            $ppId = (int)($pp['id'] ?? 0);
            $serviceId = (int)($pp['service_id'] ?? 0);
            $professionalId = isset($pp['professional_id']) && $pp['professional_id'] !== null ? (int)$pp['professional_id'] : 0;
            $total = (int)($pp['total_sessions'] ?? 0);
            $used = (int)($pp['used_sessions'] ?? 0);

            if ($ppId <= 0 || $serviceId <= 0 || $total <= 0) {
                $skipped++;
                continue;
            }

            if ($professionalId <= 0) {
                $errors[] = 'Procedimento planejado #' . $ppId . ': profissional não definido.';
                continue;
            }

            $remaining = max(0, $total - $used);
            if ($remaining === 0) {
                $skipped++;
                continue;
            }

            $cursor = $date;
            for ($i = 0; $i < $remaining; $i++) {
                $scheduled = false;

                for ($attemptDay = 0; $attemptDay < 90; $attemptDay++) {
                    $ymd = $cursor->format('Y-m-d');

                    $slots = $availability->listAvailableSlots($serviceId, $ymd, $professionalId, 15, null);
                    if ($slots !== []) {
                        $startAt = (string)($slots[0]['start_at'] ?? '');
                        if ($startAt !== '') {
                            try {
                                $apptId = $apptSvc->create($serviceId, $professionalId, $startAt, 'system', $patientId, null, null, null, 'Gerado do orçamento #' . (int)$saleId, $ip);
                                $apptRepo->setPatientProcedureId($clinicId, $apptId, $ppId);
                                $pprocRepo->addUsedSessions($clinicId, $ppId, 1);
                                $created++;
                                $scheduled = true;

                                $cursor = $cursor->modify('+1 day');
                                break;
                            } catch (\RuntimeException $e) {
                                $errors[] = 'Falha ao criar agendamento (procedimento #' . $ppId . '): ' . $e->getMessage();
                            }
                        }
                    }

                    $cursor = $cursor->modify('+1 day');
                }

                if (!$scheduled) {
                    $errors[] = 'Sem disponibilidade para procedimento #' . $ppId . ' (até 90 dias).';
                    break;
                }
            }
        }

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log($clinicId, $saleId, 'appointments.generate_from_budget', ['created' => $created, 'skipped' => $skipped, 'errors' => $errors], $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'finance.sales.update', ['sale_id' => $saleId, 'generated_appointments' => $created], $ip, $roleCodes, 'sale', $saleId, $userAgent);

        SystemEvent::dispatch($this->container, 'sale.appointments_generated', [
            'sale_id' => $saleId,
            'created' => $created,
        ], 'sale', $saleId, $ip, $userAgent);

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
    }

    /** @return list<array<string,mixed>> */
    public function listReferenceProfessionals(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $repo = new ProfessionalRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId);
    }

    /** @return list<array<string,mixed>> */
    public function listServices(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $repo = new ServiceCatalogRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId);
    }

    /** @return list<array<string,mixed>> */
    public function listPackages(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $repo = new PackageRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId);
    }

    /** @return list<array<string,mixed>> */
    public function listSubscriptionPlans(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $repo = new SubscriptionPlanRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId);
    }

    private function parseMoney(string $raw): float
    {
        $s = trim($raw);
        if ($s === '') {
            return 0.0;
        }

        $s = str_replace(['R$', ' '], '', $s);

        // Handle Brazilian format: 1.140,00 (dot=thousands, comma=decimal)
        // If both dot and comma exist, it's Brazilian format
        if (str_contains($s, ',') && str_contains($s, '.')) {
            // Check if comma comes after last dot → Brazilian format (1.140,00)
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
                // Brazilian: remove dots (thousands), replace comma with dot (decimal)
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // US format (1,140.00): remove commas (thousands)
                $s = str_replace(',', '', $s);
            }
        } elseif (str_contains($s, ',') && !str_contains($s, '.')) {
            // Only comma, no dot: treat comma as decimal (140,00 → 140.00)
            $s = str_replace(',', '.', $s);
        }

        $s = preg_replace('/[^0-9.\-]/', '', $s);
        $s = $s === null ? '' : $s;

        if ($s === '' || $s === '-' || $s === '.') {
            return 0.0;
        }

        return (float)$s;
    }

    private function syncDerivedEntitiesFromItem(int $clinicId, int $saleId, int $saleItemId, int $actorId, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);

        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            return;
        }

        $patientId = $sale['patient_id'] !== null ? (int)$sale['patient_id'] : null;
        if ($patientId === null) {
            return;
        }

        $itemsRepo = new SaleItemRepository($pdo);
        $items = $itemsRepo->listBySale($clinicId, $saleId);

        $target = null;
        foreach ($items as $it) {
            if ((int)$it['id'] === $saleItemId) {
                $target = $it;
                break;
            }
        }

        if ($target === null) {
            return;
        }

        $type = (string)$target['type'];
        $refId = (int)$target['reference_id'];
        $qty = (int)$target['quantity'];

        if ($type === 'package') {
            $pkgRepo = new PackageRepository($pdo);
            $pkg = $pkgRepo->findById($clinicId, $refId);
            if ($pkg === null) {
                return;
            }

            $totalSessions = (int)$pkg['total_sessions'];
            if ($totalSessions <= 0) {
                $totalSessions = 1;
            }

            $validityDays = (int)$pkg['validity_days'];
            $validUntil = null;
            if ($validityDays > 0) {
                $validUntil = (new \DateTimeImmutable('now'))->modify('+' . $validityDays . ' days')->format('Y-m-d');
            }

            $ppRepo = new PatientPackageRepository($pdo);
            for ($i = 0; $i < $qty; $i++) {
                $ppRepo->create($clinicId, $patientId, $refId, $saleId, $saleItemId, $totalSessions, $validUntil);
            }

            $saleLog = new SaleLogRepository($pdo);
            $saleLog->log($clinicId, $saleId, 'patient_packages.create', ['sale_item_id' => $saleItemId, 'patient_id' => $patientId, 'package_id' => $refId, 'quantity' => $qty], $actorId, $ip);
        }

        if ($type === 'subscription') {
            $planRepo = new SubscriptionPlanRepository($pdo);
            $plan = $planRepo->findById($clinicId, $refId);
            if ($plan === null) {
                return;
            }

            $months = (int)$plan['interval_months'];
            if ($months <= 0) {
                $months = 1;
            }

            $startedAt = (new \DateTimeImmutable('now'))->format('Y-m-d');
            $endsAt = (new \DateTimeImmutable('now'))->modify('+' . $months . ' months')->format('Y-m-d');

            $psRepo = new PatientSubscriptionRepository($pdo);
            for ($i = 0; $i < $qty; $i++) {
                $psRepo->create($clinicId, $patientId, $refId, $saleId, $saleItemId, $startedAt, $endsAt);
            }

            $saleLog = new SaleLogRepository($pdo);
            $saleLog->log($clinicId, $saleId, 'patient_subscriptions.create', ['sale_item_id' => $saleItemId, 'patient_id' => $patientId, 'plan_id' => $refId, 'quantity' => $qty], $actorId, $ip);
        }
    }

    private function recalcTotalsAndStatus(int $clinicId, int $saleId, int $actorId, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);

        $saleRepo = new SaleRepository($pdo);
        $sale = $saleRepo->findById($clinicId, $saleId);
        if ($sale === null) {
            return;
        }

        $itemsRepo = new SaleItemRepository($pdo);
        $items = $itemsRepo->listBySale($clinicId, $saleId);

        $totalBruto = 0.0;
        foreach ($items as $it) {
            $totalBruto += (float)$it['subtotal'];
        }

        $desconto = isset($sale['desconto']) ? (float)$sale['desconto'] : 0.0;
        if ($desconto < 0) {
            $desconto = 0.0;
        }

        $totalLiquido = max(0.0, $totalBruto - $desconto);

        $saleRepo->updateTotals(
            $clinicId,
            $saleId,
            number_format($totalBruto, 2, '.', ''),
            number_format($desconto, 2, '.', ''),
            number_format($totalLiquido, 2, '.', '')
        );

        $payRepo = new PaymentRepository($pdo);
        $payments = $payRepo->listBySale($clinicId, $saleId);

        $paid = 0.0;
        $hasAnyPayment = false;
        foreach ($payments as $p) {
            $hasAnyPayment = true;
            if ((string)$p['status'] === 'paid') {
                $paid += (float)$p['amount'];
            }
        }

        $newStatus = (string)$sale['status'];
        if ($newStatus !== 'cancelled') {
            if ($totalLiquido > 0.0 && $paid + 0.00001 >= $totalLiquido) {
                $newStatus = 'paid';
            } else {
                $newStatus = 'open';
            }

            if ($newStatus !== (string)$sale['status']) {
                $saleRepo->updateStatus($clinicId, $saleId, $newStatus);

                $saleLog = new SaleLogRepository($pdo);
                $saleLog->log($clinicId, $saleId, 'sales.status', ['from' => (string)$sale['status'], 'to' => $newStatus], $actorId, $ip);
            }
        }

        // Auto-promote budget_status to 'approved' when payments exist
        $currentBudget = (string)($sale['budget_status'] ?? 'draft');
        if ($hasAnyPayment && in_array($currentBudget, ['draft', 'sent', 'standby'], true)) {
            $saleRepo->updateBudgetStatus($clinicId, $saleId, 'approved');

            $saleLog = new SaleLogRepository($pdo);
            $saleLog->log($clinicId, $saleId, 'sales.budget_status', ['from' => $currentBudget, 'to' => 'approved', 'reason' => 'auto_payment'], $actorId, $ip);
            $currentBudget = 'approved';
        }

        // Auto-promote to 'completed' when fully paid
        if ($newStatus === 'paid' && $currentBudget !== 'completed') {
            $saleRepo->updateBudgetStatus($clinicId, $saleId, 'completed');

            $saleLog = new SaleLogRepository($pdo);
            $saleLog->log($clinicId, $saleId, 'sales.budget_status', ['from' => $currentBudget, 'to' => 'completed', 'reason' => 'fully_paid'], $actorId, $ip);
        }

        // Revert from 'completed' if no longer fully paid (e.g. payment deleted/refunded)
        if ($newStatus !== 'paid' && $currentBudget === 'completed') {
            $saleRepo->updateBudgetStatus($clinicId, $saleId, 'approved');

            $saleLog = new SaleLogRepository($pdo);
            $saleLog->log($clinicId, $saleId, 'sales.budget_status', ['from' => 'completed', 'to' => 'approved', 'reason' => 'no_longer_fully_paid'], $actorId, $ip);
        }
    }
}
