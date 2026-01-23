<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientContentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveByClinic(int $clinicId, int $limit = 200): array
    {
        $sql = "
            SELECT id, clinic_id, type, title, description, url, storage_path, mime_type, procedure_type, audience, status, created_at
            FROM patient_contents
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        string $type,
        string $title,
        ?string $description,
        ?string $url,
        ?string $procedureType,
        ?string $audience,
        int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO patient_contents (
                clinic_id,
                type, title, description,
                url, storage_path, mime_type,
                procedure_type, audience,
                status,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :type, :title, :description,
                :url, NULL, NULL,
                :procedure_type, :audience,
                'active',
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'type' => $type,
            'title' => $title,
            'description' => ($description === '' ? null : $description),
            'url' => ($url === '' ? null : $url),
            'procedure_type' => ($procedureType === '' ? null : $procedureType),
            'audience' => ($audience === '' ? null : $audience),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
