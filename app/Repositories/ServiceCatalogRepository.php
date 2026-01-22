<?php

declare(strict_types=1);

namespace App\Repositories;

final class ServiceCatalogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listActiveByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, name, duration_minutes, price_cents, allow_specific_professional, status
            FROM services
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'active'
            ORDER BY name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $serviceId): ?array
    {
        $sql = "
            SELECT id, clinic_id, name, duration_minutes, price_cents, allow_specific_professional, status
            FROM services
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $serviceId, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(
        int $clinicId,
        string $name,
        int $durationMinutes,
        ?int $priceCents,
        bool $allowSpecificProfessional
    ): int {
        $sql = "
            INSERT INTO services (clinic_id, name, duration_minutes, price_cents, allow_specific_professional, status, created_at)
            VALUES (:clinic_id, :name, :duration_minutes, :price_cents, :allow_specific_professional, 'active', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'name' => $name,
            'duration_minutes' => $durationMinutes,
            'price_cents' => $priceCents,
            'allow_specific_professional' => $allowSpecificProfessional ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }
}
