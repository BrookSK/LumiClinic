<?php

declare(strict_types=1);

namespace App\Repositories;

final class MedicalRecordAudioNoteRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(
        int $clinicId,
        int $patientId,
        ?int $medicalRecordId,
        ?int $appointmentId,
        ?int $professionalId,
        string $storagePath,
        ?string $originalFilename,
        ?string $mimeType,
        ?int $sizeBytes,
        int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO medical_record_audio_notes (
                clinic_id, patient_id, medical_record_id, appointment_id, professional_id,
                storage_path, original_filename, mime_type, size_bytes,
                status, transcript_text, transcribed_at,
                created_by_user_id, created_at
            ) VALUES (
                :clinic_id, :patient_id, :medical_record_id, :appointment_id, :professional_id,
                :storage_path, :original_filename, :mime_type, :size_bytes,
                'uploaded', NULL, NULL,
                :created_by_user_id, NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'medical_record_id' => $medicalRecordId,
            'appointment_id' => $appointmentId,
            'professional_id' => $professionalId,
            'storage_path' => $storagePath,
            'original_filename' => ($originalFilename === '' ? null : $originalFilename),
            'mime_type' => ($mimeType === '' ? null : $mimeType),
            'size_bytes' => $sizeBytes,
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function setTranscript(int $clinicId, int $id, string $status, ?string $transcriptText): void
    {
        $stmt = $this->pdo->prepare("\n            UPDATE medical_record_audio_notes\n            SET status = :status,\n                transcript_text = :transcript_text,\n                transcribed_at = NOW()\n            WHERE id = :id\n              AND clinic_id = :clinic_id\n              AND deleted_at IS NULL\n            LIMIT 1\n        ");

        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'status' => $status,
            'transcript_text' => ($transcriptText === '' ? null : $transcriptText),
        ]);
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, medical_record_id, appointment_id, professional_id,
                   storage_path, original_filename, mime_type, size_bytes,
                   status, transcript_text, transcribed_at,
                   created_by_user_id, created_at
            FROM medical_record_audio_notes
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
