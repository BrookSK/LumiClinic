<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Core\Container\Container;
use App\Repositories\BillingEventRepository;
use App\Repositories\QueueJobRepository;
use App\Services\Bi\BiService;
use App\Services\Billing\BillingEventProcessorService;
use App\Services\Observability\MetricsService;
use App\Services\Observability\AlertEngineService;
use App\Services\Observability\ObservabilityRetentionService;
use App\Services\Portal\PortalNotificationService;

final class QueueService
{
    public function __construct(private readonly Container $container) {}

    public function enqueue(
        string $jobType,
        array $payload,
        ?int $clinicId = null,
        string $queue = 'default',
        ?string $runAt = null,
        int $maxAttempts = 10
    ): int {
        $repo = new QueueJobRepository($this->container->get(\PDO::class));
        return $repo->enqueue($clinicId, $jobType, $payload, $queue, $runAt, $maxAttempts);
    }

    public function handle(string $jobType, array $payload, ?int $clinicId): void
    {
        $jobType = trim($jobType);

        if ($jobType === 'noop') {
            return;
        }

        if ($jobType === 'test.noop') {
            return;
        }

        if ($jobType === 'test.throw') {
            throw new \RuntimeException('test.throw');
        }

        if ($jobType === 'portal.notify_appointment_confirmed') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para portal.notify_appointment_confirmed.');
            }

            $patientId = (int)($payload['patient_id'] ?? 0);
            $appointmentId = (int)($payload['appointment_id'] ?? 0);
            if ($patientId <= 0 || $appointmentId <= 0) {
                throw new \RuntimeException('Payload inválido para portal.notify_appointment_confirmed.');
            }

            (new PortalNotificationService($this->container))->notifyAppointmentConfirmed($clinicId, $patientId, $appointmentId);
            return;
        }

        if ($jobType === 'bi.refresh_executive') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para bi.refresh_executive.');
            }

            $from = trim((string)($payload['from'] ?? ''));
            $to = trim((string)($payload['to'] ?? ''));
            if ($from === '' || $to === '') {
                throw new \RuntimeException('Payload inválido para bi.refresh_executive.');
            }

            $ip = (string)($payload['ip'] ?? '');
            $userAgent = $payload['user_agent'] ?? null;
            $userAgent = $userAgent === null ? null : (string)$userAgent;

            (new BiService($this->container))->refreshExecutiveSnapshot($from, $to, $ip, $userAgent);
            return;
        }

        if ($jobType === 'billing.process_event') {
            $eventId = (int)($payload['billing_event_id'] ?? 0);
            if ($eventId <= 0) {
                throw new \RuntimeException('Payload inválido para billing.process_event.');
            }

            $repo = new BillingEventRepository($this->container->get(\PDO::class));
            $event = $repo->findById($eventId);
            if ($event === null) {
                return;
            }

            if ($event['processed_at'] !== null) {
                return;
            }

            (new BillingEventProcessorService($this->container))->process($event);
            $repo->markProcessed($eventId);
            return;
        }

        if ($jobType === 'metrics.daily') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para metrics.daily.');
            }

            $ref = trim((string)($payload['reference_date'] ?? ''));
            if ($ref === '') {
                $ref = (new \DateTimeImmutable('today'))->format('Y-m-d');
            }

            (new MetricsService($this->container))->computeDailyClinicMetrics($clinicId, $ref);
            return;
        }

        if ($jobType === 'alerts.evaluate') {
            $ref = trim((string)($payload['reference_date'] ?? ''));
            if ($ref === '') {
                $ref = (new \DateTimeImmutable('today'))->format('Y-m-d');
            }

            (new AlertEngineService($this->container))->evaluate($ref);
            return;
        }

        if ($jobType === 'observability.purge') {
            (new ObservabilityRetentionService($this->container))->purge();
            return;
        }

        if ($jobType === 'metrics.weekly' || $jobType === 'metrics.monthly') {
            if ($clinicId === null) {
                throw new \RuntimeException('clinic_id obrigatório para ' . $jobType . '.');
            }

            $ref = trim((string)($payload['reference_date'] ?? ''));
            if ($ref === '') {
                $ref = (new \DateTimeImmutable('today'))->format('Y-m-d');
            }

            (new MetricsService($this->container))->computeDailyClinicMetrics($clinicId, $ref);
            return;
        }

        throw new \RuntimeException('Job handler não registrado: ' . $jobType);
    }
}
