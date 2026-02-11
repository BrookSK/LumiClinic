<?php

declare(strict_types=1);

namespace App\Repositories;

final class LegalDocumentRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listActiveForPatientPortal(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, scope, target_role_code, title, body, is_required, status
            FROM legal_documents
            WHERE clinic_id = :clinic_id
              AND scope = 'patient_portal'
              AND status = 'active'
              AND deleted_at IS NULL
            ORDER BY is_required DESC, id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listForClinicOwnersAll(): array
    {
        $sql = "
            SELECT id, clinic_id, scope, title, is_required, status, created_at, updated_at
            FROM legal_documents
            WHERE scope = 'clinic_owner'
              AND deleted_at IS NULL
            ORDER BY clinic_id IS NULL DESC, id DESC
        ";

        $stmt = $this->pdo->query($sql);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listForClinicOwnersGlobal(): array
    {
        return $this->listForClinicOwnersByClinicId(null);
    }

    /** @return list<array<string,mixed>> */
    public function listByClinicForPatientPortal(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, scope, title, is_required, status, created_at, updated_at
            FROM legal_documents
            WHERE clinic_id = :clinic_id
              AND scope = 'patient_portal'
              AND deleted_at IS NULL
            ORDER BY id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT id, clinic_id, scope, target_role_code, title, body, is_required, status, created_at, updated_at
            FROM legal_documents
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createForPatientPortal(int $clinicId, string $title, string $body, bool $isRequired, string $status): int
    {
        $sql = "
            INSERT INTO legal_documents (
                clinic_id, scope,
                title, body,
                is_required, status,
                created_at
            ) VALUES (
                :clinic_id, 'patient_portal',
                :title, :body,
                :is_required, :status,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'title' => $title,
            'body' => $body,
            'is_required' => $isRequired ? 1 : 0,
            'status' => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateForPatientPortal(int $clinicId, int $id, string $title, string $body, bool $isRequired, string $status): void
    {
        $sql = "
            UPDATE legal_documents
               SET title = :title,
                   body = :body,
                   is_required = :is_required,
                   status = :status,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND scope = 'patient_portal'
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'title' => $title,
            'body' => $body,
            'is_required' => $isRequired ? 1 : 0,
            'status' => $status,
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function listByClinicForSystemUsers(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, scope, target_role_code, title, is_required, status, created_at, updated_at
            FROM legal_documents
            WHERE clinic_id = :clinic_id
              AND scope = 'system_user'
              AND deleted_at IS NULL
            ORDER BY id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listActiveForSystemUser(int $clinicId, ?array $roleCodes): array
    {
        $roleCodes = is_array($roleCodes) ? array_values(array_filter($roleCodes, static fn($v) => is_string($v) && trim($v) !== '')) : [];

        $sql = "
            SELECT id, clinic_id, scope, target_role_code, title, body, is_required, status
            FROM legal_documents
            WHERE clinic_id = :clinic_id
              AND scope = 'system_user'
              AND status = 'active'
              AND deleted_at IS NULL
              AND (target_role_code IS NULL OR target_role_code = ''";

        $params = ['clinic_id' => $clinicId];
        if ($roleCodes !== []) {
            $in = [];
            foreach ($roleCodes as $i => $code) {
                $k = 'r' . $i;
                $in[] = ':' . $k;
                $params[$k] = $code;
            }
            $sql .= " OR target_role_code IN (" . implode(',', $in) . ")";
        }

        $sql .= ")
            ORDER BY is_required DESC, id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function createForSystemUsers(int $clinicId, ?string $targetRoleCode, string $title, string $body, bool $isRequired, string $status): int
    {
        $sql = "
            INSERT INTO legal_documents (
                clinic_id, scope, target_role_code,
                title, body,
                is_required, status,
                created_at
            ) VALUES (
                :clinic_id, 'system_user', :target_role_code,
                :title, :body,
                :is_required, :status,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'target_role_code' => ($targetRoleCode !== null && trim($targetRoleCode) !== '' ? trim($targetRoleCode) : null),
            'title' => $title,
            'body' => $body,
            'is_required' => $isRequired ? 1 : 0,
            'status' => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateForSystemUsers(int $clinicId, int $id, ?string $targetRoleCode, string $title, string $body, bool $isRequired, string $status): void
    {
        $sql = "
            UPDATE legal_documents
               SET target_role_code = :target_role_code,
                   title = :title,
                   body = :body,
                   is_required = :is_required,
                   status = :status,
                   updated_at = NOW()
             WHERE id = :id
               AND clinic_id = :clinic_id
               AND scope = 'system_user'
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => $clinicId,
            'target_role_code' => ($targetRoleCode !== null && trim($targetRoleCode) !== '' ? trim($targetRoleCode) : null),
            'title' => $title,
            'body' => $body,
            'is_required' => $isRequired ? 1 : 0,
            'status' => $status,
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function listForClinicOwnersByClinicId(?int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, scope, title, is_required, status, created_at, updated_at
            FROM legal_documents
            WHERE scope = 'clinic_owner'
              AND deleted_at IS NULL
        ";

        $params = [];
        if ($clinicId === null) {
            $sql .= " AND clinic_id IS NULL ";
        } else {
            $sql .= " AND clinic_id = :clinic_id ";
            $params['clinic_id'] = $clinicId;
        }

        $sql .= " ORDER BY id DESC ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listActiveForClinicOwner(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, scope, target_role_code, title, body, is_required, status
            FROM legal_documents
            WHERE scope = 'clinic_owner'
              AND status = 'active'
              AND deleted_at IS NULL
              AND (clinic_id IS NULL OR clinic_id = :clinic_id)
            ORDER BY is_required DESC, id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    public function createForClinicOwners(?int $clinicId, string $title, string $body, bool $isRequired, string $status): int
    {
        $sql = "
            INSERT INTO legal_documents (
                clinic_id, scope,
                title, body,
                is_required, status,
                created_at
            ) VALUES (
                :clinic_id, 'clinic_owner',
                :title, :body,
                :is_required, :status,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => ($clinicId !== null && $clinicId > 0) ? $clinicId : null,
            'title' => $title,
            'body' => $body,
            'is_required' => $isRequired ? 1 : 0,
            'status' => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateForClinicOwners(int $id, ?int $clinicId, string $title, string $body, bool $isRequired, string $status): void
    {
        $sql = "
            UPDATE legal_documents
               SET clinic_id = :clinic_id,
                   title = :title,
                   body = :body,
                   is_required = :is_required,
                   status = :status,
                   updated_at = NOW()
             WHERE id = :id
               AND scope = 'clinic_owner'
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'clinic_id' => ($clinicId !== null && $clinicId > 0) ? $clinicId : null,
            'title' => $title,
            'body' => $body,
            'is_required' => $isRequired ? 1 : 0,
            'status' => $status,
        ]);
    }
}
