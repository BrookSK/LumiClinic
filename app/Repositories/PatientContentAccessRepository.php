<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientContentAccessRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function grant(int $clinicId, int $patientId, int $contentId, int $grantedByUserId): void
    {
        $sql = "
            INSERT INTO patient_content_access (
                clinic_id, patient_id, content_id,
                granted_by_user_id, granted_at,
                created_at
            ) VALUES (
                :clinic_id, :patient_id, :content_id,
                :granted_by_user_id, NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                granted_by_user_id = VALUES(granted_by_user_id),
                granted_at = VALUES(granted_at)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'content_id' => $contentId,
            'granted_by_user_id' => $grantedByUserId,
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function listForPatient(int $clinicId, int $patientId, int $limit = 200): array
    {
        $sql = "
            SELECT
                pc.id, pc.type, pc.title, pc.description, pc.url, pc.storage_path, pc.mime_type,
                pc.procedure_type, pc.audience, pc.created_at
            FROM patient_content_access pca
            INNER JOIN patient_contents pc
                    ON pc.id = pca.content_id
                   AND pc.clinic_id = pca.clinic_id
                   AND pc.deleted_at IS NULL
                   AND pc.status = 'active'
            WHERE pca.clinic_id = :clinic_id
              AND pca.patient_id = :patient_id
            ORDER BY pca.id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
