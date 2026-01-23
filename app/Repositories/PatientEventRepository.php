<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientEventRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @param array<string,mixed> $meta */
    public function create(int $clinicId, int $patientId, string $eventCode, ?string $referenceType, ?int $referenceId, array $meta): int
    {
        $sql = "
            INSERT INTO patient_events (
                clinic_id, patient_id,
                event_code, reference_type, reference_id,
                meta_json,
                created_at
            ) VALUES (
                :clinic_id, :patient_id,
                :event_code, :reference_type, :reference_id,
                :meta_json,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'event_code' => $eventCode,
            'reference_type' => ($referenceType === '' ? null : $referenceType),
            'reference_id' => $referenceId,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array{portal_logins:int,appointment_confirms:int} */
    public function summarizeSimple(int $clinicId, int $patientId): array
    {
        $sql = "
            SELECT
                SUM(CASE WHEN event_code='portal_login' THEN 1 ELSE 0 END) AS portal_logins,
                SUM(CASE WHEN event_code='appointment_confirmed' THEN 1 ELSE 0 END) AS appointment_confirms
            FROM patient_events
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $row = $stmt->fetch() ?: [];

        return [
            'portal_logins' => (int)($row['portal_logins'] ?? 0),
            'appointment_confirms' => (int)($row['appointment_confirms'] ?? 0),
        ];
    }
}
