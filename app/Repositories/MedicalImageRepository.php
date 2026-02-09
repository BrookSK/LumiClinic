<?php

declare(strict_types=1);

namespace App\Repositories;

final class MedicalImageRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 200): array
    {
        $sql = "
            SELECT
                mi.id, mi.clinic_id, mi.patient_id, mi.medical_record_id, mi.professional_id,
                mi.kind, mi.comparison_key, mi.taken_at, mi.procedure_type,
                mi.storage_path, mi.original_filename, mi.mime_type, mi.size_bytes,
                mi.created_by_user_id, mi.created_at
            FROM medical_images mi
            WHERE mi.clinic_id = :clinic_id
              AND mi.patient_id = :patient_id
              AND mi.deleted_at IS NULL
            ORDER BY mi.created_at DESC, mi.id DESC
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

    /** @return list<array{comparison_key:string,before_id:int,after_id:int,procedure_type:?string,taken_at:?string,created_at:string}> */
    public function listComparisonPairsByPatient(int $clinicId, int $patientId, int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));

        $sql = "
            SELECT
                a.comparison_key AS comparison_key,
                b.id AS before_id,
                a.id AS after_id,
                a.procedure_type AS procedure_type,
                a.taken_at AS taken_at,
                a.created_at AS created_at
            FROM medical_images a
            JOIN medical_images b
              ON b.clinic_id = a.clinic_id
             AND b.patient_id = a.patient_id
             AND b.deleted_at IS NULL
             AND b.kind = 'before'
             AND b.comparison_key = a.comparison_key
            WHERE a.clinic_id = :clinic_id
              AND a.patient_id = :patient_id
              AND a.deleted_at IS NULL
              AND a.kind = 'after'
              AND a.comparison_key IS NOT NULL
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array{comparison_key:string,before_id:int,after_id:int,procedure_type:?string,taken_at:?string,created_at:string}> */
        return $stmt->fetchAll();
    }

    public function createFromPatientUpload(
        int $clinicId,
        int $patientId,
        string $kind,
        ?string $takenAt,
        ?string $procedureType,
        string $storagePath,
        ?string $originalFilename,
        ?string $mimeType,
        ?int $sizeBytes,
        int $approvedByUserId,
        int $patientUploadId
    ): int {
        $sql = "
            INSERT INTO medical_images (
                clinic_id, patient_id, medical_record_id, professional_id,
                kind, taken_at, procedure_type,
                storage_path, original_filename, mime_type, size_bytes,
                patient_visibility_status,
                approved_at, approved_by_user_id,
                uploaded_by_patient_upload_id,
                created_by_user_id,
                created_at
            ) VALUES (
                :clinic_id, :patient_id, NULL, NULL,
                :kind, :taken_at, :procedure_type,
                :storage_path, :original_filename, :mime_type, :size_bytes,
                'visible',
                NOW(), :approved_by_user_id,
                :uploaded_by_patient_upload_id,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'kind' => $kind,
            'taken_at' => ($takenAt === '' ? null : $takenAt),
            'procedure_type' => ($procedureType === '' ? null : $procedureType),
            'storage_path' => $storagePath,
            'original_filename' => ($originalFilename === '' ? null : $originalFilename),
            'mime_type' => ($mimeType === '' ? null : $mimeType),
            'size_bytes' => $sizeBytes,
            'approved_by_user_id' => $approvedByUserId,
            'uploaded_by_patient_upload_id' => $patientUploadId,
            'created_by_user_id' => $approvedByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function listVisibleToPatient(int $clinicId, int $patientId, int $limit = 200): array
    {
        $sql = "
            SELECT
                mi.id, mi.clinic_id, mi.patient_id, mi.medical_record_id, mi.professional_id,
                mi.kind, mi.taken_at, mi.procedure_type,
                mi.storage_path, mi.original_filename, mi.mime_type, mi.size_bytes,
                mi.created_by_user_id, mi.created_at
            FROM medical_images mi
            WHERE mi.clinic_id = :clinic_id
              AND mi.patient_id = :patient_id
              AND mi.deleted_at IS NULL
              AND mi.patient_visibility_status = 'visible'
            ORDER BY mi.created_at DESC, mi.id DESC
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

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                mi.id, mi.clinic_id, mi.patient_id, mi.medical_record_id, mi.professional_id,
                mi.kind, mi.comparison_key, mi.taken_at, mi.procedure_type,
                mi.storage_path, mi.original_filename, mi.mime_type, mi.size_bytes,
                mi.created_by_user_id, mi.created_at
            FROM medical_images mi
            WHERE mi.id = :id
              AND mi.clinic_id = :clinic_id
              AND mi.deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByIdForPatient(int $clinicId, int $patientId, int $id): ?array
    {
        $sql = "
            SELECT
                mi.id, mi.clinic_id, mi.patient_id, mi.medical_record_id, mi.professional_id,
                mi.kind, mi.comparison_key, mi.taken_at, mi.procedure_type,
                mi.storage_path, mi.original_filename, mi.mime_type, mi.size_bytes,
                mi.created_by_user_id, mi.created_at
            FROM medical_images mi
            WHERE mi.id = :id
              AND mi.clinic_id = :clinic_id
              AND mi.patient_id = :patient_id
              AND mi.deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        int $clinicId,
        int $patientId,
        ?int $medicalRecordId,
        ?int $professionalId,
        string $kind,
        ?string $comparisonKey,
        ?string $takenAt,
        ?string $procedureType,
        string $storagePath,
        ?string $originalFilename,
        ?string $mimeType,
        ?int $sizeBytes,
        int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO medical_images (
                clinic_id, patient_id, medical_record_id, professional_id,
                kind, comparison_key, taken_at, procedure_type,
                storage_path, original_filename, mime_type, size_bytes,
                created_by_user_id,
                created_at
            )
            VALUES (
                :clinic_id, :patient_id, :medical_record_id, :professional_id,
                :kind, :comparison_key, :taken_at, :procedure_type,
                :storage_path, :original_filename, :mime_type, :size_bytes,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'medical_record_id' => $medicalRecordId,
            'professional_id' => $professionalId,
            'kind' => $kind,
            'comparison_key' => ($comparisonKey === '' ? null : $comparisonKey),
            'taken_at' => ($takenAt === '' ? null : $takenAt),
            'procedure_type' => ($procedureType === '' ? null : $procedureType),
            'storage_path' => $storagePath,
            'original_filename' => ($originalFilename === '' ? null : $originalFilename),
            'mime_type' => ($mimeType === '' ? null : $mimeType),
            'size_bytes' => $sizeBytes,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
