<?php

declare(strict_types=1);

namespace App\Repositories;

final class ConsentAcceptanceRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 100): array
    {
        $sql = "
            SELECT ca.id, ca.clinic_id, ca.term_id, ca.patient_id, ca.procedure_type,
                   ca.accepted_by_user_id, ca.ip_address, ca.accepted_at, ca.created_at
            FROM consent_acceptances ca
            WHERE ca.clinic_id = :clinic_id
              AND ca.patient_id = :patient_id
            ORDER BY ca.accepted_at DESC, ca.id DESC
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

    public function create(
        int $clinicId,
        int $termId,
        int $patientId,
        string $procedureType,
        int $acceptedByUserId,
        string $ip,
        string $acceptedAt
    ): int {
        $sql = "
            INSERT INTO consent_acceptances (
                clinic_id, term_id, patient_id, procedure_type,
                accepted_by_user_id, ip_address, accepted_at,
                created_at
            )
            VALUES (
                :clinic_id, :term_id, :patient_id, :procedure_type,
                :accepted_by_user_id, :ip_address, :accepted_at,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'term_id' => $termId,
            'patient_id' => $patientId,
            'procedure_type' => $procedureType,
            'accepted_by_user_id' => $acceptedByUserId,
            'ip_address' => ($ip === '' ? null : $ip),
            'accepted_at' => $acceptedAt,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT ca.id, ca.clinic_id, ca.term_id, ca.patient_id, ca.procedure_type,
                   ca.accepted_by_user_id, ca.ip_address, ca.accepted_at, ca.created_at
            FROM consent_acceptances ca
            WHERE ca.id = :id
              AND ca.clinic_id = :clinic_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
