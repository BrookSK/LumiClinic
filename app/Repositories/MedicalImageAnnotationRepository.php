<?php

declare(strict_types=1);

namespace App\Repositories;

final class MedicalImageAnnotationRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByImage(int $clinicId, int $medicalImageId, int $limit = 500): array
    {
        $limit = max(1, min(1000, $limit));

        $sql = "
            SELECT
                id, clinic_id, medical_image_id,
                payload_json, note,
                created_by_user_id,
                created_at
            FROM medical_image_annotations
            WHERE clinic_id = :clinic_id
              AND medical_image_id = :medical_image_id
              AND deleted_at IS NULL
            ORDER BY id ASC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'medical_image_id' => $medicalImageId,
        ]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(int $clinicId, int $medicalImageId, string $payloadJson, ?string $note, ?int $createdByUserId): int
    {
        $sql = "
            INSERT INTO medical_image_annotations (
                clinic_id, medical_image_id,
                payload_json, note,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id, :medical_image_id,
                CAST(:payload_json AS JSON), :note,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'medical_image_id' => $medicalImageId,
            'payload_json' => $payloadJson,
            'note' => ($note === '' ? null : $note),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE medical_image_annotations
            SET deleted_at = NOW()
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
