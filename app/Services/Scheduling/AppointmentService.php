<?php

declare(strict_types=1);

namespace App\Services\Scheduling;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\AppointmentLogRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\SchedulingBlockRepository;
use App\Repositories\ServiceCatalogRepository;
use App\Services\Auth\AuthService;
use App\Services\Observability\SystemEvent;

final class AppointmentService
{
    public function __construct(private readonly Container $container) {}

    public function create(
        int $serviceId,
        int $professionalId,
        string $startAt,
        string $origin,
        ?int $patientId,
        ?string $notes,
        string $ip
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $serviceRepo = new ServiceCatalogRepository($pdo);
        $service = $serviceRepo->findById($clinicId, $serviceId);
        if ($service === null) {
            throw new \RuntimeException('Servi?o inv?lido.');
        }

        $durationMinutes = (int)$service['duration_minutes'];
        if ($durationMinutes <= 0) {
            throw new \RuntimeException('Servi?o inv?lido.');
        }

        $bufferBefore = isset($service['buffer_before_minutes']) ? (int)$service['buffer_before_minutes'] : 0;
        $bufferAfter = isset($service['buffer_after_minutes']) ? (int)$service['buffer_after_minutes'] : 0;
        $bufferBefore = max(0, $bufferBefore);
        $bufferAfter = max(0, $bufferAfter);

        $profRepo = new ProfessionalRepository($pdo);
        $prof = $profRepo->findById($clinicId, $professionalId);
        if ($prof === null) {
            throw new \RuntimeException('Profissional inv?lido.');
        }

        if ($patientId !== null) {
            $patientRepo = new PatientRepository($pdo);
            $patient = $patientRepo->findById($clinicId, $patientId);
            if ($patient === null) {
                throw new \RuntimeException('Paciente inv?lido.');
            }
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        if ($start === false) {
            throw new \RuntimeException('Data/hora inv?lida.');
        }
        $end = $start->modify('+' . $durationMinutes . ' minutes');

        $occupiedStart = $bufferBefore > 0 ? $start->modify('-' . $bufferBefore . ' minutes') : $start;
        $occupiedEnd = $bufferAfter > 0 ? $end->modify('+' . $bufferAfter . ' minutes') : $end;

        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        $occupiedStartStr = $occupiedStart->format('Y-m-d H:i:s');
        $occupiedEndStr = $occupiedEnd->format('Y-m-d H:i:s');

        $blocksRepo = new SchedulingBlockRepository($pdo);
        $apptRepo = new AppointmentRepository($pdo);

        try {
            $pdo->beginTransaction();

            $blocks = $blocksRepo->listOverlapping($clinicId, $professionalId, $occupiedStartStr, $occupiedEndStr);
            if ($blocks !== []) {
                throw new \RuntimeException('Hor?rio indispon?vel (bloqueio).');
            }

            $conflicts = $apptRepo->listOverlappingForUpdate($clinicId, $professionalId, $occupiedStartStr, $occupiedEndStr);
            if ($conflicts !== []) {
                throw new \RuntimeException('Conflito de hor?rio.');
            }

            $id = $apptRepo->create(
                $clinicId,
                $professionalId,
                $serviceId,
                $patientId,
                $startStr,
                $endStr,
                $bufferBefore,
                $bufferAfter,
                'scheduled',
                $origin,
                $notes,
                $userId
            );

            $logs = new AppointmentLogRepository($pdo);
            $logs->log($clinicId, $id, 'create', null, [
                'professional_id' => $professionalId,
                'service_id' => $serviceId,
                'patient_id' => $patientId,
                'start_at' => $startStr,
                'end_at' => $endStr,
                'buffer_before_minutes' => $bufferBefore,
                'buffer_after_minutes' => $bufferAfter,
                'status' => 'scheduled',
                'origin' => $origin,
            ], $userId, $ip);

            $audit = new AuditLogRepository($pdo);
            $audit->log($userId, $clinicId, 'scheduling.appointment_create', [
                'appointment_id' => $id,
                'service_id' => $serviceId,
                'professional_id' => $professionalId,
                'patient_id' => $patientId,
                'start_at' => $startStr,
                'end_at' => $endStr,
                'origin' => $origin,
            ], $ip);

            SystemEvent::dispatch($this->container, 'appointment.created', [
                'appointment_id' => $id,
                'service_id' => $serviceId,
                'professional_id' => $professionalId,
                'patient_id' => $patientId,
                'start_at' => $startStr,
                'end_at' => $endStr,
                'origin' => $origin,
            ], 'appointment', $id, $ip, null);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function cancel(int $appointmentId, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $repo = new AppointmentRepository($pdo);
        $current = $repo->findById($clinicId, $appointmentId);
        if ($current === null) {
            throw new \RuntimeException('Agendamento inv?lido.');
        }
        $repo->updateStatus($clinicId, $appointmentId, 'cancelled');

        (new AppointmentLogRepository($pdo))->log(
            $clinicId,
            $appointmentId,
            'cancel',
            ['status' => (string)$current['status']],
            ['status' => 'cancelled'],
            $userId,
            $ip
        );

        $audit = new AuditLogRepository($pdo);
        $audit->log($userId, $clinicId, 'scheduling.appointment_cancel', [
            'appointment_id' => $appointmentId,
        ], $ip);

        SystemEvent::dispatch($this->container, 'appointment.cancelled', [
            'appointment_id' => $appointmentId,
        ], 'appointment', $appointmentId, $ip, null);
    }

    public function updateStatus(int $appointmentId, string $status, string $ip): void
    {
        $allowed = ['scheduled', 'confirmed', 'in_progress', 'completed', 'no_show', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            throw new \RuntimeException('Status inv?lido.');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $repo = new AppointmentRepository($pdo);
        $current = $repo->findById($clinicId, $appointmentId);
        if ($current === null) {
            throw new \RuntimeException('Agendamento inv?lido.');
        }

        $from = (string)$current['status'];
        if (!$this->canTransition($from, $status)) {
            throw new \RuntimeException('Transi??o de status inv?lida.');
        }

        $repo->updateStatus($clinicId, $appointmentId, $status);

        (new AppointmentLogRepository($pdo))->log(
            $clinicId,
            $appointmentId,
            'status_update',
            ['status' => $from],
            ['status' => $status],
            $userId,
            $ip
        );

        $audit = new AuditLogRepository($pdo);
        $audit->log($userId, $clinicId, 'scheduling.appointment_status_update', [
            'appointment_id' => $appointmentId,
            'from' => $from,
            'to' => $status,
        ], $ip);

        SystemEvent::dispatch($this->container, 'appointment.status_updated', [
            'appointment_id' => $appointmentId,
            'from' => $from,
            'to' => $status,
        ], 'appointment', $appointmentId, $ip, null);
    }

    private function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $terminal = ['cancelled', 'no_show', 'completed'];
        if (in_array($from, $terminal, true)) {
            return false;
        }

        $allowed = [
            'scheduled' => ['confirmed', 'in_progress', 'cancelled', 'no_show'],
            'confirmed' => ['in_progress', 'cancelled', 'no_show'],
            'in_progress' => ['completed', 'cancelled', 'no_show'],
        ];

        return isset($allowed[$from]) && in_array($to, $allowed[$from], true);
    }

    public function reschedule(
        int $appointmentId,
        int $serviceId,
        int $professionalId,
        string $startAt,
        string $ip
    ): void {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $apptRepo = new AppointmentRepository($pdo);
        $current = $apptRepo->findById($clinicId, $appointmentId);
        if ($current === null) {
            throw new \RuntimeException('Agendamento inv?lido.');
        }

        $serviceRepo = new ServiceCatalogRepository($pdo);
        $service = $serviceRepo->findById($clinicId, $serviceId);
        if ($service === null) {
            throw new \RuntimeException('Servi?o inv?lido.');
        }

        $durationMinutes = (int)$service['duration_minutes'];
        if ($durationMinutes <= 0) {
            throw new \RuntimeException('Servi?o inv?lido.');
        }

        $bufferBefore = isset($service['buffer_before_minutes']) ? (int)$service['buffer_before_minutes'] : 0;
        $bufferAfter = isset($service['buffer_after_minutes']) ? (int)$service['buffer_after_minutes'] : 0;
        $bufferBefore = max(0, $bufferBefore);
        $bufferAfter = max(0, $bufferAfter);

        $profRepo = new ProfessionalRepository($pdo);
        $prof = $profRepo->findById($clinicId, $professionalId);
        if ($prof === null) {
            throw new \RuntimeException('Profissional inv?lido.');
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        if ($start === false) {
            throw new \RuntimeException('Data/hora inv?lida.');
        }
        $end = $start->modify('+' . $durationMinutes . ' minutes');

        $occupiedStart = $bufferBefore > 0 ? $start->modify('-' . $bufferBefore . ' minutes') : $start;
        $occupiedEnd = $bufferAfter > 0 ? $end->modify('+' . $bufferAfter . ' minutes') : $end;

        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        $occupiedStartStr = $occupiedStart->format('Y-m-d H:i:s');
        $occupiedEndStr = $occupiedEnd->format('Y-m-d H:i:s');

        $blocksRepo = new SchedulingBlockRepository($pdo);

        try {
            $pdo->beginTransaction();

            $blocks = $blocksRepo->listOverlapping($clinicId, $professionalId, $occupiedStartStr, $occupiedEndStr);
            if ($blocks !== []) {
                throw new \RuntimeException('Hor?rio indispon?vel (bloqueio).');
            }

            $conflicts = $apptRepo->listOverlappingForUpdateExcludingAppointment($clinicId, $professionalId, $occupiedStartStr, $occupiedEndStr, $appointmentId);
            if ($conflicts !== []) {
                throw new \RuntimeException('Conflito de hor?rio.');
            }

            $apptRepo->updateTimeProfessionalAndService($clinicId, $appointmentId, $professionalId, $serviceId, $startStr, $endStr, $bufferBefore, $bufferAfter);

            (new AppointmentLogRepository($pdo))->log(
                $clinicId,
                $appointmentId,
                'reschedule',
                [
                    'professional_id' => (int)$current['professional_id'],
                    'service_id' => (int)$current['service_id'],
                    'start_at' => (string)$current['start_at'],
                    'end_at' => (string)$current['end_at'],
                    'buffer_before_minutes' => isset($current['buffer_before_minutes']) ? (int)$current['buffer_before_minutes'] : 0,
                    'buffer_after_minutes' => isset($current['buffer_after_minutes']) ? (int)$current['buffer_after_minutes'] : 0,
                ],
                [
                    'professional_id' => $professionalId,
                    'service_id' => $serviceId,
                    'start_at' => $startStr,
                    'end_at' => $endStr,
                    'buffer_before_minutes' => $bufferBefore,
                    'buffer_after_minutes' => $bufferAfter,
                ],
                $userId,
                $ip
            );

            $audit = new AuditLogRepository($pdo);
            $audit->log($userId, $clinicId, 'scheduling.appointment_reschedule', [
                'appointment_id' => $appointmentId,
                'from' => [
                    'professional_id' => (int)$current['professional_id'],
                    'service_id' => (int)$current['service_id'],
                    'start_at' => (string)$current['start_at'],
                    'end_at' => (string)$current['end_at'],
                ],
                'to' => [
                    'professional_id' => $professionalId,
                    'service_id' => $serviceId,
                    'start_at' => $startStr,
                    'end_at' => $endStr,
                ],
            ], $ip);

            SystemEvent::dispatch($this->container, 'appointment.rescheduled', [
                'appointment_id' => $appointmentId,
                'from' => [
                    'professional_id' => (int)$current['professional_id'],
                    'service_id' => (int)$current['service_id'],
                    'start_at' => (string)$current['start_at'],
                    'end_at' => (string)$current['end_at'],
                ],
                'to' => [
                    'professional_id' => $professionalId,
                    'service_id' => $serviceId,
                    'start_at' => $startStr,
                    'end_at' => $endStr,
                ],
            ], 'appointment', $appointmentId, $ip, null);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
