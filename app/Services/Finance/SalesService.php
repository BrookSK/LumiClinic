<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PackageRepository;
use App\Repositories\PatientPackageRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientSubscriptionRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\SaleItemRepository;
use App\Repositories\SaleLogRepository;
use App\Repositories\SaleRepository;
use App\Repositories\ServiceCatalogRepository;
use App\Repositories\SubscriptionPlanRepository;
use App\Services\Auth\AuthService;

final class SalesService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listSales(?int $professionalId = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $repo = new SaleRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId, 200, $professionalId);
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

        $itemsRepo = new SaleItemRepository($pdo);
        $payRepo = new PaymentRepository($pdo);
        $logRepo = new SaleLogRepository($pdo);

        return [
            'sale' => $sale,
            'items' => $itemsRepo->listBySale($clinicId, $saleId),
            'payments' => $payRepo->listBySale($clinicId, $saleId),
            'logs' => $logRepo->listBySale($clinicId, $saleId, 200),
        ];
    }

    public function createSale(?int $patientId, string $origin, string $descontoStr, ?string $notes, string $ip): int
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
        $audit->log($actorId, $clinicId, 'finance.sales.create', ['sale_id' => $saleId], $ip);

        return $saleId;
    }

    public function addItem(
        int $saleId,
        string $type,
        int $referenceId,
        ?int $professionalId,
        int $quantity,
        string $unitPriceStr,
        string $ip
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

        $this->syncDerivedEntitiesFromItem($clinicId, $saleId, $itemId, $actorId, $ip);
        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);

        return $itemId;
    }

    public function addPayment(int $saleId, string $method, string $amountStr, string $status, string $feesStr, ?string $gatewayRef, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $method = trim($method);
        $allowedMethods = ['pix', 'card', 'cash', 'boleto'];
        if (!in_array($method, $allowedMethods, true)) {
            throw new \RuntimeException('M?todo inv?lido.');
        }

        $status = trim($status);
        $allowedStatuses = ['pending', 'paid'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new \RuntimeException('Status inv?lido.');
        }

        $amount = $this->parseMoney($amountStr);
        if ($amount <= 0) {
            throw new \RuntimeException('Valor inv?lido.');
        }

        $fees = $this->parseMoney($feesStr);
        if ($fees < 0) {
            $fees = 0.0;
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

        $paidAt = null;
        if ($status === 'paid') {
            $paidAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        }

        $payRepo = new PaymentRepository($pdo);
        $paymentId = $payRepo->create(
            $clinicId,
            $saleId,
            $method,
            number_format($amount, 2, '.', ''),
            $status,
            number_format($fees, 2, '.', ''),
            $gatewayRef,
            $paidAt,
            $actorId
        );

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log(
            $clinicId,
            $saleId,
            'payments.create',
            [
                'payment_id' => $paymentId,
                'method' => $method,
                'amount' => $amount,
                'status' => $status,
                'fees' => $fees,
                'gateway_ref' => $gatewayRef,
            ],
            $actorId,
            $ip
        );

        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);

        return $paymentId;
    }

    public function refundPayment(int $paymentId, string $ip): void
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

        $this->recalcTotalsAndStatus($clinicId, $saleId, $actorId, $ip);
    }

    public function cancelSale(int $saleId, string $ip): void
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

        if ((string)$sale['status'] === 'cancelled') {
            return;
        }

        $saleRepo->updateStatus($clinicId, $saleId, 'cancelled');

        $saleLog = new SaleLogRepository($pdo);
        $saleLog->log($clinicId, $saleId, 'sales.cancel', [], $actorId, $ip);

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'finance.sales.cancel', ['sale_id' => $saleId], $ip);
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
        foreach ($payments as $p) {
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
    }
}
