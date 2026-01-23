<?php

declare(strict_types=1);

namespace App\Repositories;

final class ComplianceControlRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId, int $limit = 500): array
    {
        $sql = "
            SELECT
                id, clinic_id, policy_id,
                code, title, description,
                status,
                owner_user_id,
                evidence_url,
                last_tested_at,
                created_at, updated_at
            FROM compliance_controls
            WHERE clinic_id = :clinic_id
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                id, clinic_id, policy_id,
                code, title, description,
                status,
                owner_user_id,
                evidence_url,
                last_tested_at,
                created_at, updated_at
            FROM compliance_controls
            WHERE clinic_id = :clinic_id
              AND id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        int $clinicId,
        ?int $policyId,
        string $code,
        string $title,
        ?string $description,
        string $status,
        ?int $ownerUserId,
        ?string $evidenceUrl,
        ?string $lastTestedAt
    ): int {
        $sql = "
            INSERT INTO compliance_controls (
                clinic_id, policy_id,
                code, title, description,
                status,
                owner_user_id,
                evidence_url,
                last_tested_at,
                created_at
            ) VALUES (
                :clinic_id, :policy_id,
                :code, :title, :description,
                :status,
                :owner_user_id,
                :evidence_url,
                :last_tested_at,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'policy_id' => $policyId,
            'code' => $code,
            'title' => $title,
            'description' => ($description === '' ? null : $description),
            'status' => $status,
            'owner_user_id' => $ownerUserId,
            'evidence_url' => ($evidenceUrl === '' ? null : $evidenceUrl),
            'last_tested_at' => $lastTestedAt,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(
        int $clinicId,
        int $id,
        string $title,
        ?string $description,
        string $status,
        ?int $ownerUserId,
        ?string $evidenceUrl,
        ?string $lastTestedAt,
        ?int $policyId
    ): void {
        $sql = "
            UPDATE compliance_controls
            SET title = :title,
                description = :description,
                status = :status,
                owner_user_id = :owner_user_id,
                evidence_url = :evidence_url,
                last_tested_at = :last_tested_at,
                policy_id = :policy_id,
                updated_at = NOW()
            WHERE clinic_id = :clinic_id
              AND id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
            'title' => $title,
            'description' => ($description === '' ? null : $description),
            'status' => $status,
            'owner_user_id' => $ownerUserId,
            'evidence_url' => ($evidenceUrl === '' ? null : $evidenceUrl),
            'last_tested_at' => $lastTestedAt,
            'policy_id' => $policyId,
        ]);
    }
}
