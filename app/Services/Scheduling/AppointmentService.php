<?php

declare(strict_types=1);

namespace App\Services\Scheduling;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\SchedulingBlockRepository;
use App\Repositories\ServiceCatalogRepository;
use App\Services\Auth\AuthService;

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
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $serviceRepo = new ServiceCatalogRepository($pdo);
        $service = $serviceRepo->findById($clinicId, $serviceId);
        if ($service === null) {
            throw new \RuntimeException('Serviço inválido.');
        }

        $durationMinutes = (int)$service['duration_minutes'];
        if ($durationMinutes <= 0) {
            throw new \RuntimeException('Serviço inválido.');
        }

        $profRepo = new ProfessionalRepository($pdo);
        $prof = $profRepo->findById($clinicId, $professionalId);
        if ($prof === null) {
            throw new \RuntimeException('Profissional inválido.');
        }

        if ($patientId !== null) {
            $patientRepo = new PatientRepository($pdo);
            $patient = $patientRepo->findById($clinicId, $patientId);
            if ($patient === null) {
                throw new \RuntimeException('Paciente inválido.');
            }
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        if ($start === false) {
            throw new \RuntimeException('Data/hora inválida.');
        }
        $end = $start->modify('+' . $durationMinutes . ' minutes');

        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        $blocksRepo = new SchedulingBlockRepository($pdo);
        $apptRepo = new AppointmentRepository($pdo);

        try {
            $pdo->beginTransaction();

            $blocks = $blocksRepo->listOverlapping($clinicId, $professionalId, $startStr, $endStr);
            if ($blocks !== []) {
                throw new \RuntimeException('Horário indisponível (bloqueio).');
            }

            $conflicts = $apptRepo->listOverlappingForUpdate($clinicId, $professionalId, $startStr, $endStr);
            if ($conflicts !== []) {
                throw new \RuntimeException('Conflito de horário.');
            }

            $id = $apptRepo->create(
                $clinicId,
                $professionalId,
                $serviceId,
                $patientId,
                $startStr,
                $endStr,
                'scheduled',
                $origin,
                $notes,
                $userId
            );

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
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $repo = new AppointmentRepository($pdo);
        $repo->updateStatus($clinicId, $appointmentId, 'cancelled');

        $audit = new AuditLogRepository($pdo);
        $audit->log($userId, $clinicId, 'scheduling.appointment_cancel', [
            'appointment_id' => $appointmentId,
        ], $ip);
    }

    public function updateStatus(int $appointmentId, string $status, string $ip): void
    {
        $allowed = ['scheduled', 'confirmed', 'completed', 'no_show', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $repo = new AppointmentRepository($pdo);
        $current = $repo->findById($clinicId, $appointmentId);
        if ($current === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $repo->updateStatus($clinicId, $appointmentId, $status);

        $audit = new AuditLogRepository($pdo);
        $audit->log($userId, $clinicId, 'scheduling.appointment_status_update', [
            'appointment_id' => $appointmentId,
            'from' => (string)$current['status'],
            'to' => $status,
        ], $ip);
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
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $apptRepo = new AppointmentRepository($pdo);
        $current = $apptRepo->findById($clinicId, $appointmentId);
        if ($current === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $serviceRepo = new ServiceCatalogRepository($pdo);
        $service = $serviceRepo->findById($clinicId, $serviceId);
        if ($service === null) {
            throw new \RuntimeException('Serviço inválido.');
        }

        $durationMinutes = (int)$service['duration_minutes'];
        if ($durationMinutes <= 0) {
            throw new \RuntimeException('Serviço inválido.');
        }

        $profRepo = new ProfessionalRepository($pdo);
        $prof = $profRepo->findById($clinicId, $professionalId);
        if ($prof === null) {
            throw new \RuntimeException('Profissional inválido.');
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        if ($start === false) {
            throw new \RuntimeException('Data/hora inválida.');
        }
        $end = $start->modify('+' . $durationMinutes . ' minutes');

        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        $blocksRepo = new SchedulingBlockRepository($pdo);

        try {
            $pdo->beginTransaction();

            $blocks = $blocksRepo->listOverlapping($clinicId, $professionalId, $startStr, $endStr);
            if ($blocks !== []) {
                throw new \RuntimeException('Horário indisponível (bloqueio).');
            }

            $conflicts = $apptRepo->listOverlappingForUpdateExcludingAppointment($clinicId, $professionalId, $startStr, $endStr, $appointmentId);
            if ($conflicts !== []) {
                throw new \RuntimeException('Conflito de horário.');
            }

            $apptRepo->updateTimeProfessionalAndService($clinicId, $appointmentId, $professionalId, $serviceId, $startStr, $endStr);

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

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
