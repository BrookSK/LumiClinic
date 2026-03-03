<?php

declare(strict_types=1);

namespace App\Repositories;

final class LegalDocumentSignatureRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function existsForPatientUser(int $documentVersionId, int $patientUserId): bool
    {
        $sql = "
            SELECT 1
            FROM legal_document_signatures
            WHERE document_version_id = :document_version_id
              AND patient_user_id = :patient_user_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'document_version_id' => $documentVersionId,
            'patient_user_id' => $patientUserId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listByClinic(int $clinicId, string $scope = 'all', int $limit = 200): array
    {
        $limit = max(50, min(1000, $limit));
        $scope = trim($scope);
        if (!in_array($scope, ['all', 'patient_portal', 'system_user', 'clinic_owner'], true)) {
            $scope = 'all';
        }

        $sql = "
            SELECT
                s.id, s.clinic_id, s.document_id, s.document_version_id,
                s.patient_user_id, s.user_id,
                s.method, s.signature_hash_sha256,
                s.signed_at, s.ip_address, s.user_agent,
                d.scope, d.title AS document_title,
                v.version_number, v.hash_sha256 AS document_hash_sha256,
                p.id AS patient_id, p.name AS patient_name,
                pu.email AS patient_user_email,
                u.name AS user_name, u.email AS user_email
            FROM legal_document_signatures s
            INNER JOIN legal_documents d ON d.id = s.document_id
            INNER JOIN legal_document_versions v ON v.id = s.document_version_id
            LEFT JOIN patient_users pu ON pu.id = s.patient_user_id
            LEFT JOIN patients p ON p.id = pu.patient_id
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.clinic_id = :clinic_id
        ";

        $params = ['clinic_id' => $clinicId];
        if ($scope !== 'all') {
            $sql .= " AND d.scope = :scope ";
            $params['scope'] = $scope;
        }

        $sql .= " ORDER BY s.signed_at DESC, s.id DESC LIMIT {$limit} ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $clinicId, int $id): ?array
    {
        $sql = "
            SELECT
                s.id, s.clinic_id, s.document_id, s.document_version_id,
                s.patient_user_id, s.user_id,
                s.method, s.signature_data_url, s.signature_hash_sha256,
                s.signed_at, s.ip_address, s.user_agent,
                d.scope, d.title AS document_title,
                v.version_number, v.title AS version_title, v.body AS version_body, v.hash_sha256 AS document_hash_sha256,
                p.id AS patient_id, p.name AS patient_name,
                pu.email AS patient_user_email,
                u.name AS user_name, u.email AS user_email
            FROM legal_document_signatures s
            INNER JOIN legal_documents d ON d.id = s.document_id
            INNER JOIN legal_document_versions v ON v.id = s.document_version_id
            LEFT JOIN patient_users pu ON pu.id = s.patient_user_id
            LEFT JOIN patients p ON p.id = pu.patient_id
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.clinic_id = :clinic_id
              AND s.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function existsForUser(int $documentVersionId, int $userId): bool
    {
        $sql = "
            SELECT 1
            FROM legal_document_signatures
            WHERE document_version_id = :document_version_id
              AND user_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'document_version_id' => $documentVersionId,
            'user_id' => $userId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    /** @return list<int> */
    public function listSignedVersionIdsByPatientUser(int $clinicId, int $patientUserId): array
    {
        $sql = "
            SELECT document_version_id
            FROM legal_document_signatures
            WHERE clinic_id = :clinic_id
              AND patient_user_id = :patient_user_id
            ORDER BY signed_at DESC
            LIMIT 1000
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_user_id' => $patientUserId]);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $out[] = (int)($r['document_version_id'] ?? 0);
        }
        return array_values(array_filter($out, static fn($v) => $v > 0));
    }

    /** @return list<int> */
    public function listSignedVersionIdsByUser(int $clinicId, int $userId): array
    {
        $sql = "
            SELECT document_version_id
            FROM legal_document_signatures
            WHERE clinic_id = :clinic_id
              AND user_id = :user_id
            ORDER BY signed_at DESC
            LIMIT 1000
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $out[] = (int)($r['document_version_id'] ?? 0);
        }
        return array_values(array_filter($out, static fn($v) => $v > 0));
    }

    public function createForPatientUser(int $clinicId, int $documentId, int $documentVersionId, int $patientUserId, string $method, string $signatureDataUrl, string $signatureHashSha256, string $signedAt, string $ip, ?string $userAgent): int
    {
        $sql = "
            INSERT INTO legal_document_signatures (
                clinic_id,
                document_id, document_version_id,
                patient_user_id,
                method, signature_data_url, signature_hash_sha256,
                signed_at, ip_address, user_agent,
                created_at
            ) VALUES (
                :clinic_id,
                :document_id, :document_version_id,
                :patient_user_id,
                :method, :signature_data_url, :signature_hash_sha256,
                :signed_at, :ip_address, :user_agent,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'document_id' => $documentId,
            'document_version_id' => $documentVersionId,
            'patient_user_id' => $patientUserId,
            'method' => $method,
            'signature_data_url' => $signatureDataUrl,
            'signature_hash_sha256' => $signatureHashSha256,
            'signed_at' => $signedAt,
            'ip_address' => ($ip === '' ? null : $ip),
            'user_agent' => ($userAgent !== null && trim($userAgent) !== '' ? trim($userAgent) : null),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function createForUser(int $clinicId, int $documentId, int $documentVersionId, int $userId, string $method, string $signatureDataUrl, string $signatureHashSha256, string $signedAt, string $ip, ?string $userAgent): int
    {
        $sql = "
            INSERT INTO legal_document_signatures (
                clinic_id,
                document_id, document_version_id,
                user_id,
                method, signature_data_url, signature_hash_sha256,
                signed_at, ip_address, user_agent,
                created_at
            ) VALUES (
                :clinic_id,
                :document_id, :document_version_id,
                :user_id,
                :method, :signature_data_url, :signature_hash_sha256,
                :signed_at, :ip_address, :user_agent,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'document_id' => $documentId,
            'document_version_id' => $documentVersionId,
            'user_id' => $userId,
            'method' => $method,
            'signature_data_url' => $signatureDataUrl,
            'signature_hash_sha256' => $signatureHashSha256,
            'signed_at' => $signedAt,
            'ip_address' => ($ip === '' ? null : $ip),
            'user_agent' => ($userAgent !== null && trim($userAgent) !== '' ? trim($userAgent) : null),
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
