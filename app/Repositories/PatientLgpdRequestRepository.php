<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientLgpdRequestRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(int $clinicId, int $patientId, string $type, ?string $note): int
    {
        $sql = "
            INSERT INTO patient_lgpd_requests (
                clinic_id, patient_id,
                type, status,
                note,
                created_at
            ) VALUES (
                :clinic_id, :patient_id,
                :type, 'pending',
                :note,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'type' => $type,
            'note' => ($note === '' ? null : $note),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 50): array
    {
        $sql = "
            SELECT id, type, status, note, processed_at, processed_note, created_at
            FROM patient_lgpd_requests
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
}
