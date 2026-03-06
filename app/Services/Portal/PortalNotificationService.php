<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientNotificationRepository;

final class PortalNotificationService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function list(int $clinicId, int $patientId, string $ip): array
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientNotificationRepository($pdo);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.notifications.view', ['patient_id' => $patientId], $ip);

        return $repo->listLatestByPatient($clinicId, $patientId, 50);
    }

    public function markRead(int $clinicId, int $patientId, int $notificationId, string $ip): void
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientNotificationRepository($pdo);
        $repo->markRead($clinicId, $patientId, $notificationId);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.notifications.read', ['patient_id' => $patientId, 'notification_id' => $notificationId], $ip);
    }

    public function notifyAppointmentConfirmed(int $clinicId, int $patientId, int $appointmentId): void
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientNotificationRepository($pdo);

        $repo->create(
            $clinicId,
            $patientId,
            'in_app',
            'appointment_confirmed',
            'Consulta confirmada',
            'Sua consulta foi confirmada com sucesso.',
            'appointment',
            $appointmentId
        );
    }

    public function notifyAnamnesisRequest(int $clinicId, int $patientId, int $appointmentId, string $publicUrl, int $requestId): void
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientNotificationRepository($pdo);

        $safeUrl = trim($publicUrl) !== '' ? ('\n\nLink: ' . $publicUrl) : '';

        $repo->create(
            $clinicId,
            $patientId,
            'in_app',
            'anamnesis_request',
            'Anamnese disponível',
            'Por favor, preencha a anamnese antes da consulta.' . $safeUrl,
            'appointment_anamnesis_request',
            $requestId
        );
    }
}
