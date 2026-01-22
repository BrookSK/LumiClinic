<?php

declare(strict_types=1);

namespace App\Repositories;

final class MedicalRecordRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 200): array
    {
        $sql = "
            SELECT
                mr.id, mr.clinic_id, mr.patient_id, mr.professional_id,
                mr.attended_at, mr.procedure_type,
                mr.clinical_description, mr.clinical_evolution, mr.notes,
                mr.created_by_user_id,
                mr.created_at, mr.updated_at
            FROM medical_records mr
            WHERE mr.clinic_id = :clinic_id
              AND mr.patient_id = :patient_id
              AND mr.deleted_at IS NULL
            ORDER BY mr.attended_at DESC, mr.id DESC
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
                mr.id, mr.clinic_id, mr.patient_id, mr.professional_id,
                mr.attended_at, mr.procedure_type,
                mr.clinical_description, mr.clinical_evolution, mr.notes,
                mr.created_by_user_id,
                mr.created_at, mr.updated_at
            FROM medical_records mr
            WHERE mr.id = :id
              AND mr.clinic_id = :clinic_id
              AND mr.deleted_at IS NULL
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

    public function create(
        int $clinicId,
        int $patientId,
        ?int $professionalId,
        string $attendedAt,
        ?string $procedureType,
        ?string $clinicalDescription,
        ?string $clinicalEvolution,
        ?string $notes,
        int $createdByUserId
    ): int {
        $sql = "
            INSERT INTO medical_records (
                clinic_id, patient_id, professional_id,
                attended_at, procedure_type,
                clinical_description, clinical_evolution, notes,
                created_by_user_id,
                created_at
            )
            VALUES (
                :clinic_id, :patient_id, :professional_id,
                :attended_at, :procedure_type,
                :clinical_description, :clinical_evolution, :notes,
                :created_by_user_id,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'professional_id' => $professionalId,
            'attended_at' => $attendedAt,
            'procedure_type' => ($procedureType === '' ? null : $procedureType),
            'clinical_description' => ($clinicalDescription === '' ? null : $clinicalDescription),
            'clinical_evolution' => ($clinicalEvolution === '' ? null : $clinicalEvolution),
            'notes' => ($notes === '' ? null : $notes),
            'created_by_user_id' => $createdByUserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(
        int $clinicId,
        int $id,
        ?int $professionalId,
        string $attendedAt,
        ?string $procedureType,
        ?string $clinicalDescription,
        ?string $clinicalEvolution,
        ?string $notes
    ): void {
        $sql = "
            UPDATE medical_records
            SET
                professional_id = :professional_id,
                attended_at = :attended_at,
                procedure_type = :procedure_type,
                clinical_description = :clinical_description,
                clinical_evolution = :clinical_evolution,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'professional_id' => $professionalId,
            'attended_at' => $attendedAt,
            'procedure_type' => ($procedureType === '' ? null : $procedureType),
            'clinical_description' => ($clinicalDescription === '' ? null : $clinicalDescription),
            'clinical_evolution' => ($clinicalEvolution === '' ? null : $clinicalEvolution),
            'notes' => ($notes === '' ? null : $notes),
        ]);
    }

    public function createVersion(
        int $clinicId,
        int $medicalRecordId,
        int $versionNo,
        string $snapshotJson,
        int $editedByUserId,
        string $ip
    ): void {
        $sql = "
            INSERT INTO medical_record_versions (
                clinic_id, medical_record_id, version_no,
                snapshot_json, edited_by_user_id, ip_address,
                created_at
            )
            VALUES (
                :clinic_id, :medical_record_id, :version_no,
                CAST(:snapshot_json AS JSON), :edited_by_user_id, :ip_address,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'medical_record_id' => $medicalRecordId,
            'version_no' => $versionNo,
            'snapshot_json' => $snapshotJson,
            'edited_by_user_id' => $editedByUserId,
            'ip_address' => ($ip === '' ? null : $ip),
        ]);
    }

    public function nextVersionNo(int $clinicId, int $medicalRecordId): int
    {
        $sql = "
            SELECT COALESCE(MAX(version_no), 0) AS max_no
            FROM medical_record_versions
            WHERE clinic_id = :clinic_id
              AND medical_record_id = :medical_record_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'medical_record_id' => $medicalRecordId,
        ]);

        $row = $stmt->fetch();
        $max = is_array($row) && isset($row['max_no']) ? (int)$row['max_no'] : 0;

        return $max + 1;
    }
}
