<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientNotificationRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(
        int $clinicId,
        int $patientId,
        string $channel,
        string $type,
        string $title,
        string $body,
        ?string $referenceType,
        ?int $referenceId
    ): int {
        $sql = "
            INSERT INTO patient_notifications (
                clinic_id, patient_id,
                channel, type,
                title, body,
                reference_type, reference_id,
                read_at,
                created_at
            ) VALUES (
                :clinic_id, :patient_id,
                :channel, :type,
                :title, :body,
                :reference_type, :reference_id,
                NULL,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'channel' => $channel,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'reference_type' => ($referenceType === '' ? null : $referenceType),
            'reference_id' => $referenceId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listLatestByPatient(int $clinicId, int $patientId, int $limit = 20): array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, channel, type, title, body, reference_type, reference_id, read_at, created_at
            FROM patient_notifications
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function markRead(int $clinicId, int $patientId, int $id): void
    {
        $sql = "
            UPDATE patient_notifications
            SET read_at = NOW()
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND id = :id
              AND deleted_at IS NULL
              AND read_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'id' => $id,
        ]);
    }
}
