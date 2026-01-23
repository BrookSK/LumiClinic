<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientAppointmentRequestRepository;
use App\Services\Portal\PortalNotificationService;
use App\Repositories\PatientEventRepository;

final class PortalAgendaService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{appointments:list<array<string,mixed>>,pending_requests:list<array<string,mixed>>} */
    public function agenda(int $clinicId, int $patientId): array
    {
        $pdo = $this->container->get(\PDO::class);

        $apptRepo = new AppointmentRepository($pdo);
        $reqRepo = new PatientAppointmentRequestRepository($pdo);

        return [
            'appointments' => $apptRepo->listUpcomingByPatient($clinicId, $patientId, 50),
            'pending_requests' => $reqRepo->listPendingByPatient($clinicId, $patientId, 50),
        ];
    }

    public function confirm(int $clinicId, int $patientId, int $appointmentId, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new AppointmentRepository($pdo);
        $audit = new AuditLogRepository($pdo);

        $appt = $repo->findByIdForPatient($clinicId, $patientId, $appointmentId);
        if ($appt === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $from = (string)$appt['status'];
        if (!in_array($from, ['scheduled', 'confirmed'], true)) {
            throw new \RuntimeException('Não é possível confirmar este agendamento.');
        }

        if ($from !== 'confirmed') {
            $repo->updateStatusForPatient($clinicId, $patientId, $appointmentId, 'confirmed');
        }

        $audit->log(null, $clinicId, 'portal.appointment_confirm', ['appointment_id' => $appointmentId, 'patient_id' => $patientId, 'from' => $from, 'to' => 'confirmed'], $ip);

        (new PortalNotificationService($this->container))->notifyAppointmentConfirmed($clinicId, $patientId, $appointmentId);

        (new PatientEventRepository($pdo))->create(
            $clinicId,
            $patientId,
            'appointment_confirmed',
            'appointment',
            $appointmentId,
            []
        );
    }

    public function requestCancel(int $clinicId, int $patientId, int $appointmentId, string $note, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);
        $apptRepo = new AppointmentRepository($pdo);
        $reqRepo = new PatientAppointmentRequestRepository($pdo);
        $audit = new AuditLogRepository($pdo);

        $appt = $apptRepo->findByIdForPatient($clinicId, $patientId, $appointmentId);
        if ($appt === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $status = (string)$appt['status'];
        if (in_array($status, ['cancelled', 'completed', 'no_show'], true)) {
            throw new \RuntimeException('Não é possível solicitar cancelamento.');
        }

        $reqId = $reqRepo->create($clinicId, $patientId, $appointmentId, 'cancel', null, $note);
        $audit->log(null, $clinicId, 'portal.appointment_cancel_request', ['request_id' => $reqId, 'appointment_id' => $appointmentId, 'patient_id' => $patientId], $ip);
    }

    public function requestReschedule(int $clinicId, int $patientId, int $appointmentId, string $requestedStartAt, string $note, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);
        $apptRepo = new AppointmentRepository($pdo);
        $reqRepo = new PatientAppointmentRequestRepository($pdo);
        $audit = new AuditLogRepository($pdo);

        $appt = $apptRepo->findByIdForPatient($clinicId, $patientId, $appointmentId);
        if ($appt === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $status = (string)$appt['status'];
        if (in_array($status, ['cancelled', 'completed', 'no_show'], true)) {
            throw new \RuntimeException('Não é possível solicitar reagendamento.');
        }

        $requestedStartAt = trim($requestedStartAt);
        if ($requestedStartAt === '') {
            throw new \RuntimeException('Informe a data/hora desejada.');
        }

        $reqId = $reqRepo->create($clinicId, $patientId, $appointmentId, 'reschedule', $requestedStartAt, $note);
        $audit->log(null, $clinicId, 'portal.appointment_reschedule_request', ['request_id' => $reqId, 'appointment_id' => $appointmentId, 'patient_id' => $patientId, 'requested_start_at' => $requestedStartAt], $ip);
    }
}
