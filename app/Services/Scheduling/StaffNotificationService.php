<?php

declare(strict_types=1);

namespace App\Services\Scheduling;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\UserNotificationRepository;

final class StaffNotificationService
{
    public function __construct(private readonly Container $container) {}

    public function notifyProfessionalAppointmentCheckedIn(int $clinicId, int $appointmentId): void
    {
        $pdo = $this->container->get(\PDO::class);

        $apptRepo = new AppointmentRepository($pdo);
        $appt = $apptRepo->findDetailedById($clinicId, $appointmentId);
        if ($appt === null) {
            return;
        }

        $professionalId = (int)($appt['professional_id'] ?? 0);
        if ($professionalId <= 0) {
            return;
        }

        $profRepo = new ProfessionalRepository($pdo);
        $prof = $profRepo->findById($clinicId, $professionalId);
        if ($prof === null) {
            return;
        }

        $userId = isset($prof['user_id']) && $prof['user_id'] !== null ? (int)$prof['user_id'] : 0;
        if ($userId <= 0) {
            return;
        }

        $patientName = trim((string)($appt['patient_name'] ?? ''));
        $serviceName = trim((string)($appt['service_name'] ?? ''));
        $startAt = (string)($appt['start_at'] ?? '');
        $hm = $startAt !== '' ? substr($startAt, 11, 5) : '';

        $title = 'Paciente chegou';
        $body = ($patientName !== '' ? $patientName : 'Paciente') . ' chegou para ' . ($serviceName !== '' ? $serviceName : 'consulta') . ($hm !== '' ? (' (' . $hm . ')') : '') . '.';

        (new UserNotificationRepository($pdo))->create(
            $clinicId,
            $userId,
            'in_app',
            'appointment_checked_in',
            $title,
            $body,
            'appointment',
            $appointmentId
        );
    }
}
