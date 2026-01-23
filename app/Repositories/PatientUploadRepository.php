<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientUploadRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(
        int $clinicId,
        int $patientId,
        ?int $patientUserId,
        string $kind,
        ?string $takenAt,
        ?string $note,
        string $storagePath,
        ?string $originalFilename,
        ?string $mimeType,
        ?int $sizeBytes
    ): int {
        $sql = "
            INSERT INTO patient_uploads (
                clinic_id, patient_id, patient_user_id,
                kind, taken_at, note,
                storage_path, original_filename, mime_type, size_bytes,
                status,
                created_at
            ) VALUES (
                :clinic_id, :patient_id, :patient_user_id,
                :kind, :taken_at, :note,
                :storage_path, :original_filename, :mime_type, :size_bytes,
                'pending',
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'patient_user_id' => $patientUserId,
            'kind' => $kind,
            'taken_at' => ($takenAt === '' ? null : $takenAt),
            'note' => ($note === '' ? null : $note),
            'storage_path' => $storagePath,
            'original_filename' => ($originalFilename === '' ? null : $originalFilename),
            'mime_type' => ($mimeType === '' ? null : $mimeType),
            'size_bytes' => $sizeBytes,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 50): array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, patient_user_id,
                   kind, taken_at, note,
                   status, moderated_at, moderation_note,
                   medical_image_id,
                   created_at
            FROM patient_uploads
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

    /** @return list<array<string,mixed>> */
    public function listPendingByClinic(int $clinicId, int $limit = 100): array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, patient_user_id,
                   kind, taken_at, note,
                   storage_path, original_filename, mime_type, size_bytes,
                   status, created_at
            FROM patient_uploads
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'pending'
            ORDER BY id ASC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findPendingForUpdate(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, patient_user_id,
                   kind, taken_at, note,
                   storage_path, original_filename, mime_type, size_bytes,
                   status
            FROM patient_uploads
            WHERE clinic_id = :clinic_id
              AND id = :id
              AND deleted_at IS NULL
              AND status = 'pending'
            LIMIT 1
            FOR UPDATE
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markApproved(int $clinicId, int $id, int $moderatedByUserId, ?string $moderationNote, int $medicalImageId): void
    {
        $sql = "
            UPDATE patient_uploads
            SET status = 'approved',
                moderated_by_user_id = :moderated_by_user_id,
                moderated_at = NOW(),
                moderation_note = :moderation_note,
                medical_image_id = :medical_image_id,
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
            'moderated_by_user_id' => $moderatedByUserId,
            'moderation_note' => ($moderationNote === '' ? null : $moderationNote),
            'medical_image_id' => $medicalImageId,
        ]);
    }

    public function markRejected(int $clinicId, int $id, int $moderatedByUserId, ?string $moderationNote): void
    {
        $sql = "
            UPDATE patient_uploads
            SET status = 'rejected',
                moderated_by_user_id = :moderated_by_user_id,
                moderated_at = NOW(),
                moderation_note = :moderation_note,
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
            'moderated_by_user_id' => $moderatedByUserId,
            'moderation_note' => ($moderationNote === '' ? null : $moderationNote),
        ]);
    }
}
