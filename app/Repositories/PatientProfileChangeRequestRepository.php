<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientProfileChangeRequestRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(
        int $clinicId,
        int $patientId,
        ?int $patientUserId,
        array $requestedFields
    ): int {
        $sql = "
            INSERT INTO patient_profile_change_requests (
                clinic_id, patient_id, patient_user_id,
                status, requested_fields_json,
                created_at
            ) VALUES (
                :clinic_id, :patient_id, :patient_user_id,
                'pending', :requested_fields_json,
                NOW()
            )
        ";

        $json = (string)json_encode($requestedFields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === '' || $json === 'null') {
            $json = '{}';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'patient_user_id' => $patientUserId,
            'requested_fields_json' => $json,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function listByClinic(int $clinicId, ?string $status = null, int $limit = 200, int $offset = 0): array
    {
        $offset = max(0, $offset);
        $limit = max(1, min(500, $limit));

        $whereStatus = '';
        $params = ['clinic_id' => $clinicId];
        if ($status !== null && $status !== '') {
            $whereStatus = ' AND r.status = :status ';
            $params['status'] = $status;
        }

        $sql = "
            SELECT
                r.id, r.clinic_id, r.patient_id, r.patient_user_id,
                r.status, r.requested_fields_json,
                r.reviewed_by_user_id, r.reviewed_at, r.review_notes,
                r.created_at,
                p.name AS patient_name
            FROM patient_profile_change_requests r
            LEFT JOIN patients p ON p.id = r.patient_id AND p.clinic_id = r.clinic_id
            WHERE r.clinic_id = :clinic_id
              {$whereStatus}
            ORDER BY r.id DESC
            LIMIT {$limit}
            OFFSET {$offset}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, patient_user_id,
                   status, requested_fields_json,
                   reviewed_by_user_id, reviewed_at, review_notes,
                   created_at
            FROM patient_profile_change_requests
            WHERE id = :id
              AND clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findLatestPendingByPatient(int $clinicId, int $patientId): ?array
    {
        $sql = "
            SELECT id, clinic_id, patient_id, patient_user_id,
                   status, requested_fields_json,
                   created_at
            FROM patient_profile_change_requests
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND status = 'pending'
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function approve(int $clinicId, int $id, int $reviewedByUserId): void
    {
        $sql = "
            UPDATE patient_profile_change_requests
               SET status = 'approved',
                   reviewed_by_user_id = :reviewed_by_user_id,
                   reviewed_at = NOW(),
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND status = 'pending'
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'reviewed_by_user_id' => $reviewedByUserId,
        ]);
    }

    public function reject(int $clinicId, int $id, int $reviewedByUserId, ?string $notes): void
    {
        $sql = "
            UPDATE patient_profile_change_requests
               SET status = 'rejected',
                   reviewed_by_user_id = :reviewed_by_user_id,
                   reviewed_at = NOW(),
                   review_notes = :review_notes,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND status = 'pending'
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'reviewed_by_user_id' => $reviewedByUserId,
            'review_notes' => ($notes !== null && trim($notes) !== '' ? trim($notes) : null),
        ]);
    }
}
