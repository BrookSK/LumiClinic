<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClinicDomainRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findByDomain(string $domain): ?array
    {
        $sql = "
            SELECT id, clinic_id, domain, is_primary, created_at, updated_at
            FROM clinic_domains
            WHERE domain = :domain
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['domain' => $domain]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $clinicId, string $domain, bool $isPrimary = true): int
    {
        $sql = "
            INSERT INTO clinic_domains (clinic_id, domain, is_primary, created_at)
            VALUES (:clinic_id, :domain, :is_primary, NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'domain' => $domain,
            'is_primary' => $isPrimary ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
