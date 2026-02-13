<?php

declare(strict_types=1);

namespace App\Repositories;

final class ConsultationAttachmentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByConsultation(int $clinicId, int $consultationId, int $limit = 200): array
    {
        $limit = max(1, min($limit, 500));

        $sql = "
            SELECT id, clinic_id, consultation_id, patient_id,
                   note,
                   storage_path, original_filename, mime_type, size_bytes,
                   created_by_user_id, created_at
            FROM consultation_attachments
            WHERE clinic_id = :clinic_id
              AND consultation_id = :consultation_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'consultation_id' => $consultationId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $consultationId,
        int $patientId,
        ?string $note,
        string $storagePath,
        ?string $originalFilename,
        ?string $mimeType,
        ?int $sizeBytes,
        ?int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO consultation_attachments (
                clinic_id,
                consultation_id, patient_id,
                note,
                storage_path, original_filename, mime_type, size_bytes,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id,
                :consultation_id, :patient_id,
                :note,
                :storage_path, :original_filename, :mime_type, :size_bytes,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'consultation_id' => $consultationId,
            'patient_id' => $patientId,
            'note' => ($note === '' ? null : $note),
            'storage_path' => $storagePath,
            'original_filename' => ($originalFilename === '' ? null : $originalFilename),
            'mime_type' => ($mimeType === '' ? null : $mimeType),
            'size_bytes' => $sizeBytes,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, consultation_id, patient_id,
                   note,
                   storage_path, original_filename, mime_type, size_bytes,
                   created_by_user_id, created_at
            FROM consultation_attachments
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
