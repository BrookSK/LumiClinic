<?php

declare(strict_types=1);

namespace App\Repositories;

final class PrescriptionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.id, p.clinic_id, p.patient_id, p.professional_id, p.medical_record_id,
                   p.title, p.body, p.issued_at, p.created_at,
                   pr.name AS professional_name
            FROM prescriptions p
            LEFT JOIN professionals pr ON pr.id = p.professional_id AND pr.clinic_id = p.clinic_id AND pr.deleted_at IS NULL
            WHERE p.clinic_id = :clinic_id
              AND p.patient_id = :patient_id
              AND p.deleted_at IS NULL
            ORDER BY p.issued_at DESC, p.id DESC
            LIMIT " . (int)$limit
        );
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.id, p.clinic_id, p.patient_id, p.professional_id, p.medical_record_id,
                   p.title, p.body, p.issued_at, p.created_at,
                   pr.name AS professional_name,
                   pat.name AS patient_name
            FROM prescriptions p
            LEFT JOIN professionals pr ON pr.id = p.professional_id AND pr.clinic_id = p.clinic_id AND pr.deleted_at IS NULL
            LEFT JOIN patients pat ON pat.id = p.patient_id AND pat.clinic_id = p.clinic_id AND pat.deleted_at IS NULL
            WHERE p.id = :id AND p.clinic_id = :clinic_id AND p.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(
        int $clinicId,
        int $patientId,
        ?int $professionalId,
        ?int $medicalRecordId,
        string $title,
        string $body,
        string $issuedAt,
        ?int $createdByUserId
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO prescriptions (
                clinic_id, patient_id, professional_id, medical_record_id,
                title, body, issued_at, created_by_user_id, created_at
            ) VALUES (
                :clinic_id, :patient_id, :professional_id, :medical_record_id,
                :title, :body, :issued_at, :created_by_user_id, NOW()
            )
        ");
        $stmt->execute([
            'clinic_id'          => $clinicId,
            'patient_id'         => $patientId,
            'professional_id'    => $professionalId,
            'medical_record_id'  => $medicalRecordId,
            'title'              => $title,
            'body'               => $body,
            'issued_at'          => $issuedAt,
            'created_by_user_id' => $createdByUserId,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $id, string $title, string $body, string $issuedAt): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE prescriptions
            SET title = :title, body = :body, issued_at = :issued_at, updated_at = NOW()
            WHERE id = :id AND clinic_id = :clinic_id AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId, 'title' => $title, 'body' => $body, 'issued_at' => $issuedAt]);
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE prescriptions SET deleted_at = NOW(), updated_at = NOW()
            WHERE id = :id AND clinic_id = :clinic_id AND deleted_at IS NULL LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }
}
