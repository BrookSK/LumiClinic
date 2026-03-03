<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientAllergyRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 200): array
    {
        $sql = "
            SELECT
                id, clinic_id, patient_id,
                type, trigger_name, reaction, severity, notes,
                created_by_user_id,
                created_at, updated_at
            FROM patient_allergies
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
        ]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $patientId,
        string $type,
        string $triggerName,
        ?string $reaction,
        ?string $severity,
        ?string $notes,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO patient_allergies (
                clinic_id, patient_id,
                type, trigger_name, reaction, severity, notes,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id, :patient_id,
                :type, :trigger_name, :reaction, :severity, :notes,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'type' => $type,
            'trigger_name' => $triggerName,
            'reaction' => ($reaction === '' ? null : $reaction),
            'severity' => ($severity === '' ? null : $severity),
            'notes' => ($notes === '' ? null : $notes),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE patient_allergies
            SET deleted_at = NOW(),
                updated_at = NOW()
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
        ]);
    }
}
