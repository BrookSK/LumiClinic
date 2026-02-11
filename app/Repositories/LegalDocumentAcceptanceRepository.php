<?php

declare(strict_types=1);

namespace App\Repositories;

final class LegalDocumentAcceptanceRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<int> */
    public function listAcceptedDocumentIdsByPatientUser(int $clinicId, int $patientUserId): array
    {
        $sql = "
            SELECT document_id
            FROM legal_document_acceptances
            WHERE clinic_id = :clinic_id
              AND patient_user_id = :patient_user_id
            ORDER BY accepted_at DESC
            LIMIT 500
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_user_id' => $patientUserId]);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $out[] = (int)($r['document_id'] ?? 0);
        }
        return array_values(array_filter($out, static fn($v) => $v > 0));
    }

    public function createForPatientUser(int $clinicId, int $documentId, int $patientUserId, string $acceptedAt, string $ip, ?string $userAgent): int
    {
        $sql = "
            INSERT INTO legal_document_acceptances (
                clinic_id, document_id,
                patient_user_id,
                accepted_at, ip_address, user_agent,
                created_at
            ) VALUES (
                :clinic_id, :document_id,
                :patient_user_id,
                :accepted_at, :ip_address, :user_agent,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'document_id' => $documentId,
            'patient_user_id' => $patientUserId,
            'accepted_at' => $acceptedAt,
            'ip_address' => ($ip === '' ? null : $ip),
            'user_agent' => ($userAgent !== null && trim($userAgent) !== '' ? trim($userAgent) : null),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<int> */
    public function listAcceptedDocumentIdsByUser(int $clinicId, int $userId): array
    {
        $sql = "
            SELECT document_id
            FROM legal_document_acceptances
            WHERE clinic_id = :clinic_id
              AND user_id = :user_id
            ORDER BY accepted_at DESC
            LIMIT 500
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $out[] = (int)($r['document_id'] ?? 0);
        }
        return array_values(array_filter($out, static fn($v) => $v > 0));
    }

    public function createForUser(int $clinicId, int $documentId, int $userId, string $acceptedAt, string $ip, ?string $userAgent): int
    {
        $sql = "
            INSERT INTO legal_document_acceptances (
                clinic_id, document_id,
                user_id,
                accepted_at, ip_address, user_agent,
                created_at
            ) VALUES (
                :clinic_id, :document_id,
                :user_id,
                :accepted_at, :ip_address, :user_agent,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'document_id' => $documentId,
            'user_id' => $userId,
            'accepted_at' => $acceptedAt,
            'ip_address' => ($ip === '' ? null : $ip),
            'user_agent' => ($userAgent !== null && trim($userAgent) !== '' ? trim($userAgent) : null),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listPortalAcceptanceSummaryByClinic(int $clinicId, int $limit = 300): array
    {
        $limit = max(1, min(1000, $limit));

        $sql = "
            SELECT
                p.id AS patient_id,
                p.name AS patient_name,
                pu.id AS patient_user_id,
                pu.email AS portal_email,
                (
                    SELECT COUNT(*)
                    FROM legal_documents d
                    WHERE d.clinic_id = :clinic_id
                      AND d.scope = 'patient_portal'
                      AND d.status = 'active'
                      AND d.deleted_at IS NULL
                      AND d.is_required = 1
                ) AS required_total,
                (
                    SELECT COUNT(DISTINCT a.document_id)
                    FROM legal_document_acceptances a
                    INNER JOIN legal_documents d ON d.id = a.document_id
                    WHERE a.clinic_id = :clinic_id
                      AND a.patient_user_id = pu.id
                      AND d.scope = 'patient_portal'
                      AND d.status = 'active'
                      AND d.deleted_at IS NULL
                      AND d.is_required = 1
                ) AS required_accepted,
                (
                    SELECT MAX(a.accepted_at)
                    FROM legal_document_acceptances a
                    WHERE a.clinic_id = :clinic_id
                      AND a.patient_user_id = pu.id
                ) AS last_accepted_at
            FROM patients p
            LEFT JOIN patient_users pu
              ON pu.patient_id = p.id
             AND pu.clinic_id = p.clinic_id
             AND pu.deleted_at IS NULL
            WHERE p.clinic_id = :clinic_id
              AND p.deleted_at IS NULL
            ORDER BY p.name ASC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listByPatientUser(int $clinicId, int $patientUserId, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));

        $sql = "
            SELECT
                a.id, a.document_id, a.accepted_at,
                d.title, d.is_required
            FROM legal_document_acceptances a
            INNER JOIN legal_documents d ON d.id = a.document_id
            WHERE a.clinic_id = :clinic_id
              AND a.patient_user_id = :patient_user_id
            ORDER BY a.accepted_at DESC, a.id DESC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_user_id' => $patientUserId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listClinicOwnerAcceptanceSummary(int $limit = 300): array
    {
        $limit = max(1, min(1000, $limit));

        $sql = "
            SELECT
                c.id AS clinic_id,
                c.name AS clinic_name,
                ou.id AS owner_user_id,
                ou.name AS owner_name,
                ou.email AS owner_email,
                (
                    SELECT COUNT(*)
                    FROM legal_documents d
                    WHERE d.scope = 'clinic_owner'
                      AND d.status = 'active'
                      AND d.deleted_at IS NULL
                      AND d.is_required = 1
                      AND (d.clinic_id IS NULL OR d.clinic_id = c.id)
                ) AS required_total,
                (
                    SELECT COUNT(DISTINCT a.document_id)
                    FROM legal_document_acceptances a
                    INNER JOIN legal_documents d ON d.id = a.document_id
                    WHERE a.user_id = ou.id
                      AND d.scope = 'clinic_owner'
                      AND d.status = 'active'
                      AND d.deleted_at IS NULL
                      AND d.is_required = 1
                      AND (d.clinic_id IS NULL OR d.clinic_id = c.id)
                ) AS required_accepted,
                (
                    SELECT MAX(a.accepted_at)
                    FROM legal_document_acceptances a
                    INNER JOIN legal_documents d ON d.id = a.document_id
                    WHERE a.user_id = ou.id
                      AND d.scope = 'clinic_owner'
                      AND (d.clinic_id IS NULL OR d.clinic_id = c.id)
                ) AS last_accepted_at
            FROM clinics c
            LEFT JOIN users ou
              ON ou.clinic_id = c.id
             AND ou.deleted_at IS NULL
             AND EXISTS (
                SELECT 1
                FROM user_roles ur
                INNER JOIN roles r ON r.id = ur.role_id
                WHERE ur.clinic_id = c.id
                  AND ur.user_id = ou.id
                  AND ur.deleted_at IS NULL
                  AND r.deleted_at IS NULL
                  AND r.code = 'owner'
             )
            WHERE c.deleted_at IS NULL
            ORDER BY c.name ASC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->query($sql);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
