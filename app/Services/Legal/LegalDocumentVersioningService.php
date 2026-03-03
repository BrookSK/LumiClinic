<?php

declare(strict_types=1);

namespace App\Services\Legal;

use App\Core\Container\Container;
use App\Repositories\LegalDocumentRepository;
use App\Repositories\LegalDocumentVersionRepository;

final class LegalDocumentVersioningService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{version_id:int,version_number:int,hash_sha256:string} */
    public function ensureCurrentVersionForDocumentId(int $documentId): array
    {
        $pdo = $this->container->get(\PDO::class);

        $doc = (new LegalDocumentRepository($pdo))->findById($documentId);
        if ($doc === null) {
            throw new \RuntimeException('Documento inválido.');
        }

        $clinicId = $doc['clinic_id'] ?? null;
        $clinicIdOrNull = $clinicId !== null ? (int)$clinicId : 0;
        $title = (string)($doc['title'] ?? '');
        $body = (string)($doc['body'] ?? '');
        $hash = self::computeDocumentHashSha256($title, $body);

        $versions = new LegalDocumentVersionRepository($pdo);
        $latest = $versions->findLatestByDocumentId($documentId);

        if ($latest !== null && (string)($latest['hash_sha256'] ?? '') === $hash) {
            return [
                'version_id' => (int)($latest['id'] ?? 0),
                'version_number' => (int)($latest['version_number'] ?? 0),
                'hash_sha256' => $hash,
            ];
        }

        $next = $versions->nextVersionNumber($documentId);
        $newId = $versions->create($clinicIdOrNull, $documentId, $next, $title, $body, $hash);

        return [
            'version_id' => $newId,
            'version_number' => $next,
            'hash_sha256' => $hash,
        ];
    }

    public static function computeDocumentHashSha256(string $title, string $body): string
    {
        $payload = trim($title) . "\n\n" . trim($body);
        return hash('sha256', $payload);
    }
}
