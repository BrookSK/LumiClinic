<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientLgpdRequestRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(int $clinicId, int $patientId, string $type, ?string $note): int
    {
        $sql = "
            INSERT INTO patient_lgpd_requests (
                clinic_id, patient_id,
                type, status,
                note,
                created_at
            ) VALUES (
                :clinic_id, :patient_id,
                :type, 'pending',
                :note,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'type' => $type,
            'note' => ($note === '' ? null : $note),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 50): array
    {
        $sql = "
            SELECT id, type, status, note, processed_at, processed_note, created_at
            FROM patient_lgpd_requests
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
    public function listByClinic(int $clinicId, ?string $status, int $limit = 200): array
    {
        $where = " r.clinic_id = :clinic_id AND r.deleted_at IS NULL ";
        $params = ['clinic_id' => $clinicId];

        if ($status !== null && $status !== '') {
            $where .= " AND r.status = :status ";
            $params['status'] = $status;
        }

        $sql = "
            SELECT
                r.id, r.clinic_id, r.patient_id,
                p.name AS patient_name,
                r.type, r.status, r.note,
                r.processed_by_user_id, r.processed_at, r.processed_note,
                r.created_at
            FROM patient_lgpd_requests r
            INNER JOIN patients p ON p.id = r.patient_id AND p.clinic_id = r.clinic_id AND p.deleted_at IS NULL
            WHERE {$where}
            ORDER BY r.id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, type, status, note, processed_by_user_id, processed_at, processed_note, created_at
            FROM patient_lgpd_requests
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

    public function markProcessed(int $clinicId, int $id, int $processedByUserId, ?string $note): void
    {
        $sql = "
            UPDATE patient_lgpd_requests
            SET status = 'processed',
                processed_by_user_id = :processed_by_user_id,
                processed_at = NOW(),
                processed_note = :processed_note,
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
            'processed_by_user_id' => $processedByUserId,
            'processed_note' => ($note === '' ? null : $note),
        ]);
    }

    public function markRejected(int $clinicId, int $id, int $processedByUserId, ?string $note): void
    {
        $sql = "
            UPDATE patient_lgpd_requests
            SET status = 'rejected',
                processed_by_user_id = :processed_by_user_id,
                processed_at = NOW(),
                processed_note = :processed_note,
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
            'processed_by_user_id' => $processedByUserId,
            'processed_note' => ($note === '' ? null : $note),
        ]);
    }
}
