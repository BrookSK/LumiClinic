<?php

declare(strict_types=1);

namespace App\Repositories;

final class LegalDocumentVersionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findLatestByDocumentId(int $documentId): ?array
    {
        $sql = "
            SELECT id, clinic_id, document_id, version_number, title, body, hash_sha256, created_at
            FROM legal_document_versions
            WHERE document_id = :document_id
            ORDER BY version_number DESC, id DESC
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['document_id' => $documentId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @param list<int> $documentIds
     * @return array<int,array<string,mixed>> map document_id => version row
     */
    public function listLatestByDocumentIds(array $documentIds): array
    {
        $ids = array_values(array_unique(array_values(array_filter($documentIds, static fn($v) => is_int($v) && $v > 0))));
        if ($ids === []) {
            return [];
        }

        $in = [];
        $params = [];
        foreach ($ids as $i => $id) {
            $k = 'd' . $i;
            $in[] = ':' . $k;
            $params[$k] = $id;
        }

        $sql = "
            SELECT v1.*
            FROM legal_document_versions v1
            INNER JOIN (
                SELECT document_id, MAX(version_number) AS max_version
                FROM legal_document_versions
                WHERE document_id IN (" . implode(',', $in) . ")
                GROUP BY document_id
            ) x
              ON x.document_id = v1.document_id
             AND x.max_version = v1.version_number
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $r) {
            $docId = (int)($r['document_id'] ?? 0);
            if ($docId > 0) {
                $out[$docId] = $r;
            }
        }
        return $out;
    }

    public function create(int $clinicIdOrNull, int $documentId, int $versionNumber, string $title, string $body, string $hashSha256): int
    {
        $sql = "
            INSERT INTO legal_document_versions (
                clinic_id, document_id,
                version_number, title, body, hash_sha256,
                created_at
            ) VALUES (
                :clinic_id, :document_id,
                :version_number, :title, :body, :hash_sha256,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicIdOrNull > 0 ? $clinicIdOrNull : null,
            'document_id' => $documentId,
            'version_number' => $versionNumber,
            'title' => $title,
            'body' => $body,
            'hash_sha256' => $hashSha256,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function nextVersionNumber(int $documentId): int
    {
        $sql = "SELECT COALESCE(MAX(version_number), 0) AS v FROM legal_document_versions WHERE document_id = :document_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['document_id' => $documentId]);
        $row = $stmt->fetch();
        $cur = (int)($row['v'] ?? 0);

        return $cur + 1;
    }
}
