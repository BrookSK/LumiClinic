<?php

declare(strict_types=1);

namespace App\Repositories;

final class SignatureRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(
        int $clinicId,
        int $patientId,
        ?int $termAcceptanceId,
        ?int $medicalRecordId,
        string $storagePath,
        string $mimeType,
        int $signedByUserId,
        string $ip
    ): int {
        $sql = "
            INSERT INTO signatures (
                clinic_id, patient_id,
                term_acceptance_id, medical_record_id,
                storage_path, mime_type,
                signed_by_user_id, ip_address,
                created_at
            )
            VALUES (
                :clinic_id, :patient_id,
                :term_acceptance_id, :medical_record_id,
                :storage_path, :mime_type,
                :signed_by_user_id, :ip_address,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'term_acceptance_id' => $termAcceptanceId,
            'medical_record_id' => $medicalRecordId,
            'storage_path' => $storagePath,
            'mime_type' => $mimeType,
            'signed_by_user_id' => $signedByUserId,
            'ip_address' => ($ip === '' ? null : $ip),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, term_acceptance_id, medical_record_id,
                   storage_path, mime_type, signed_by_user_id, ip_address, created_at
            FROM signatures
            WHERE id = :id
              AND clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 100): array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, term_acceptance_id, medical_record_id,
                   storage_path, mime_type, signed_by_user_id, ip_address, created_at
            FROM signatures
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
            ORDER BY created_at DESC, id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }
}
