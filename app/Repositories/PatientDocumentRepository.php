<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientDocumentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, clinic_id, patient_id, title, file_path, original_filename, mime_type, size_bytes, uploaded_by_user_id, created_at
            FROM patient_documents
            WHERE clinic_id = :clinic_id AND patient_id = :patient_id AND deleted_at IS NULL
            ORDER BY created_at DESC, id DESC
            LIMIT " . (int)$limit
        );
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function create(int $clinicId, int $patientId, string $title, string $filePath, ?string $originalFilename, ?string $mimeType, ?int $sizeBytes, ?int $uploadedByUserId): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO patient_documents (clinic_id, patient_id, title, file_path, original_filename, mime_type, size_bytes, uploaded_by_user_id, created_at)
            VALUES (:clinic_id, :patient_id, :title, :file_path, :original_filename, :mime_type, :size_bytes, :uploaded_by_user_id, NOW())
        ");
        $stmt->execute([
            'clinic_id'          => $clinicId,
            'patient_id'         => $patientId,
            'title'              => $title,
            'file_path'          => $filePath,
            'original_filename'  => $originalFilename,
            'mime_type'          => $mimeType,
            'size_bytes'         => $sizeBytes,
            'uploaded_by_user_id'=> $uploadedByUserId,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, clinic_id, patient_id, title, file_path, original_filename, mime_type, size_bytes, created_at
            FROM patient_documents
            WHERE id = :id AND clinic_id = :clinic_id AND deleted_at IS NULL LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE patient_documents SET deleted_at = NOW() WHERE id = :id AND clinic_id = :clinic_id AND deleted_at IS NULL LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }
}
