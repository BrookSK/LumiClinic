<?php

declare(strict_types=1);

namespace App\Repositories;

final class CompliancePolicyRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listByClinic(int $clinicId, int $limit = 200): array
    {
        $sql = "
            SELECT
                id, clinic_id,
                code, title, description,
                status, version,
                owner_user_id,
                reviewed_at, next_review_at,
                created_at, updated_at
            FROM compliance_policies
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
                id, clinic_id,
                code, title, description,
                status, version,
                owner_user_id,
                reviewed_at, next_review_at,
                created_at, updated_at
            FROM compliance_policies
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
        string $code,
        string $title,
        ?string $description,
        string $status,
        int $version,
        ?int $ownerUserId,
        ?string $reviewedAt,
        ?string $nextReviewAt
    ): int {
        $sql = "
            INSERT INTO compliance_policies (
                clinic_id,
                code, title, description,
                status, version,
                owner_user_id,
                reviewed_at, next_review_at,
                created_at
            ) VALUES (
                :clinic_id,
                :code, :title, :description,
                :status, :version,
                :owner_user_id,
                :reviewed_at, :next_review_at,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'code' => $code,
            'title' => $title,
            'description' => ($description === '' ? null : $description),
            'status' => $status,
            'version' => $version,
            'owner_user_id' => $ownerUserId,
            'reviewed_at' => $reviewedAt,
            'next_review_at' => $nextReviewAt,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(
        int $clinicId,
        int $id,
        string $title,
        ?string $description,
        string $status,
        int $version,
        ?int $ownerUserId,
        ?string $reviewedAt,
        ?string $nextReviewAt
    ): void {
        $sql = "
            UPDATE compliance_policies
            SET title = :title,
                description = :description,
                status = :status,
                version = :version,
                owner_user_id = :owner_user_id,
                reviewed_at = :reviewed_at,
                next_review_at = :next_review_at,
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
            'version' => $version,
            'owner_user_id' => $ownerUserId,
            'reviewed_at' => $reviewedAt,
            'next_review_at' => $nextReviewAt,
        ]);
    }
}
