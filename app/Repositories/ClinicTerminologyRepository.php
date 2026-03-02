<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClinicTerminologyRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string, mixed>|null */
    public function findByClinicId(int $clinicId): ?array
    {
        $sql = "
            SELECT clinic_id, patient_label, appointment_label, professional_label
            FROM clinic_terminology
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function update(int $clinicId, string $patient, string $appointment, string $professional): void
    {
        $sql = "
            INSERT INTO clinic_terminology (clinic_id, patient_label, appointment_label, professional_label, created_at)
            VALUES (:clinic_id, :patient_label, :appointment_label, :professional_label, NOW())
            ON DUPLICATE KEY UPDATE
                patient_label = VALUES(patient_label),
                appointment_label = VALUES(appointment_label),
                professional_label = VALUES(professional_label),
                updated_at = NOW(),
                deleted_at = NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_label' => $patient,
            'appointment_label' => $appointment,
            'professional_label' => $professional,
        ]);
    }
}
