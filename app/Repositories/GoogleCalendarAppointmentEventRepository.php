<?php

declare(strict_types=1);

namespace App\Repositories;

final class GoogleCalendarAppointmentEventRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findByClinicAppointment(int $clinicId, int $appointmentId): ?array
    {
        $stmt = $this->pdo->prepare("\n            SELECT id, clinic_id, appointment_id, token_id, google_event_id, google_calendar_id, last_synced_at, last_error, created_at, updated_at, deleted_at\n            FROM google_calendar_appointment_events\n            WHERE clinic_id = :clinic_id\n              AND appointment_id = :appointment_id\n              AND deleted_at IS NULL\n            LIMIT 1\n        ");
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(
        int $clinicId,
        int $appointmentId,
        int $tokenId,
        ?string $googleEventId,
        ?string $googleCalendarId,
        ?string $lastSyncedAt,
        ?string $lastError
    ): int {
        $existing = $this->findByClinicAppointment($clinicId, $appointmentId);
        if ($existing !== null) {
            $stmt = $this->pdo->prepare("\n                UPDATE google_calendar_appointment_events\n                SET token_id = :token_id,\n                    google_event_id = :google_event_id,\n                    google_calendar_id = :google_calendar_id,\n                    last_synced_at = :last_synced_at,\n                    last_error = :last_error,\n                    updated_at = NOW()\n                WHERE id = :id\n                LIMIT 1\n            ");
            $stmt->execute([
                'id' => (int)$existing['id'],
                'token_id' => $tokenId,
                'google_event_id' => ($googleEventId === '' ? null : $googleEventId),
                'google_calendar_id' => ($googleCalendarId === '' ? null : $googleCalendarId),
                'last_synced_at' => ($lastSyncedAt === '' ? null : $lastSyncedAt),
                'last_error' => ($lastError === '' ? null : $lastError),
            ]);
            return (int)$existing['id'];
        }

        $stmt = $this->pdo->prepare("\n            INSERT INTO google_calendar_appointment_events (\n                clinic_id, appointment_id, token_id,\n                google_event_id, google_calendar_id,\n                last_synced_at, last_error,\n                created_at\n            ) VALUES (\n                :clinic_id, :appointment_id, :token_id,\n                :google_event_id, :google_calendar_id,\n                :last_synced_at, :last_error,\n                NOW()\n            )\n        ");
        $stmt->execute([
            'clinic_id' => $clinicId,
            'appointment_id' => $appointmentId,
            'token_id' => $tokenId,
            'google_event_id' => ($googleEventId === '' ? null : $googleEventId),
            'google_calendar_id' => ($googleCalendarId === '' ? null : $googleCalendarId),
            'last_synced_at' => ($lastSyncedAt === '' ? null : $lastSyncedAt),
            'last_error' => ($lastError === '' ? null : $lastError),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDeleteByClinicAppointment(int $clinicId, int $appointmentId): void
    {
        $stmt = $this->pdo->prepare("\n            UPDATE google_calendar_appointment_events\n            SET deleted_at = NOW(), updated_at = NOW()\n            WHERE clinic_id = :clinic_id\n              AND appointment_id = :appointment_id\n              AND deleted_at IS NULL\n            LIMIT 1\n        ");
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);
    }
}
