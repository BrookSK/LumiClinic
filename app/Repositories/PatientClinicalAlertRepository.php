<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientClinicalAlertRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 200): array
    {
        $sql = "
            SELECT
                id, clinic_id, patient_id,
                title, details, severity, active,
                resolved_at, resolved_by_user_id,
                created_by_user_id,
                created_at, updated_at
            FROM patient_clinical_alerts
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
            ORDER BY active DESC, id DESC
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
        string $title,
        ?string $details,
        string $severity,
        int $active,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO patient_clinical_alerts (
                clinic_id, patient_id,
                title, details, severity, active,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id, :patient_id,
                :title, :details, :severity, :active,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'title' => $title,
            'details' => ($details === '' ? null : $details),
            'severity' => $severity,
            'active' => $active,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE patient_clinical_alerts
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

    public function resolve(int $clinicId, int $id, ?int $resolvedByUserId): void
    {
        $sql = "
            UPDATE patient_clinical_alerts
            SET active = 0,
                resolved_at = NOW(),
                resolved_by_user_id = :resolved_by_user_id,
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
            'resolved_by_user_id' => $resolvedByUserId,
        ]);
    }
}
