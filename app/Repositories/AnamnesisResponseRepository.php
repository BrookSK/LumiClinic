<?php

declare(strict_types=1);

namespace App\Repositories;

final class AnamnesisResponseRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $responseId): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, patient_id, template_id, professional_id,
                answers_json,
                created_by_user_id, created_at
            FROM anamnesis_responses
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $responseId,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 100): array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, template_id, professional_id, created_by_user_id, created_at
            FROM anamnesis_responses
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
            ORDER BY created_at DESC, id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
        ]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $patientId,
        int $templateId,
        ?int $professionalId,
        string $answersJson,
        int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO anamnesis_responses (
                clinic_id, patient_id, template_id, professional_id,
                answers_json,
                created_by_user_id,
                created_at
            )
            VALUES (
                :clinic_id, :patient_id, :template_id, :professional_id,
                :answers_json,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'template_id' => $templateId,
            'professional_id' => $professionalId,
            'answers_json' => $answersJson,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
