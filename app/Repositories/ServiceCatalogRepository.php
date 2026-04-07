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
            SELECT
                s.id,
                s.clinic_id,
                s.procedure_id,
                s.category_id,
                s.name,
                s.duration_minutes,
                s.buffer_before_minutes,
                s.buffer_after_minutes,
                s.price_cents,
                s.allow_specific_professional,
                s.status,
                COALESCE(p.name, '') AS procedure_name,
                COALESCE(sc.name, '') AS category_name
            FROM services s
            LEFT JOIN procedures p
                   ON p.id = s.procedure_id
                  AND p.clinic_id = s.clinic_id
                  AND p.deleted_at IS NULL
            LEFT JOIN service_categories sc
                   ON sc.id = s.category_id
                  AND sc.clinic_id = s.clinic_id
                  AND sc.deleted_at IS NULL
            WHERE s.clinic_id = :clinic_id
              AND s.deleted_at IS NULL
              AND s.status = 'active'
            ORDER BY s.name ASC
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
            SELECT
                id, clinic_id,
                procedure_id,
                category_id,
                name,
                duration_minutes,
                buffer_before_minutes,
                buffer_after_minutes,
                price_cents,
                allow_specific_professional,
                status
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
        ?int $procedureId,
        ?int $categoryId,
        string $name,
        int $durationMinutes,
        int $bufferBeforeMinutes,
        int $bufferAfterMinutes,
        ?int $priceCents,
        bool $allowSpecificProfessional
    ): int {
        $bufferBeforeMinutes = max(0, $bufferBeforeMinutes);
        $bufferAfterMinutes = max(0, $bufferAfterMinutes);

        $procedureId = $procedureId !== null ? max(1, $procedureId) : null;
        $categoryId = $categoryId !== null ? max(1, $categoryId) : null;

        $sql = "
            INSERT INTO services (
                clinic_id,
                procedure_id,
                category_id,
                name,
                duration_minutes,
                buffer_before_minutes,
                buffer_after_minutes,
                price_cents,
                allow_specific_professional,
                status,
                created_at
            )
            VALUES (
                :clinic_id,
                :procedure_id,
                :category_id,
                :name,
                :duration_minutes,
                :buffer_before_minutes,
                :buffer_after_minutes,
                :price_cents,
                :allow_specific_professional,
                'active',
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'procedure_id' => $procedureId,
            'category_id' => $categoryId,
            'name' => $name,
            'duration_minutes' => $durationMinutes,
            'buffer_before_minutes' => $bufferBeforeMinutes,
            'buffer_after_minutes' => $bufferAfterMinutes,
            'price_cents' => $priceCents,
            'allow_specific_professional' => $allowSpecificProfessional ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(
        int $clinicId,
        int $serviceId,
        ?int $procedureId,
        ?int $categoryId,
        string $name,
        int $durationMinutes,
        int $bufferBeforeMinutes,
        int $bufferAfterMinutes,
        ?int $priceCents,
        bool $allowSpecificProfessional
    ): void {
        $sql = "
            UPDATE services
            SET procedure_id = :procedure_id,
                category_id = :category_id,
                name = :name,
                duration_minutes = :duration_minutes,
                buffer_before_minutes = :buffer_before_minutes,
                buffer_after_minutes = :buffer_after_minutes,
                price_cents = :price_cents,
                allow_specific_professional = :allow_specific_professional,
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $serviceId,
            'clinic_id' => $clinicId,
            'procedure_id' => $procedureId,
            'category_id' => $categoryId,
            'name' => $name,
            'duration_minutes' => $durationMinutes,
            'buffer_before_minutes' => max(0, $bufferBeforeMinutes),
            'buffer_after_minutes' => max(0, $bufferAfterMinutes),
            'price_cents' => $priceCents,
            'allow_specific_professional' => $allowSpecificProfessional ? 1 : 0,
        ]);
    }

    public function softDelete(int $clinicId, int $serviceId): void
    {
        $stmt = $this->pdo->prepare("UPDATE services SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND clinic_id = :clinic_id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $serviceId, 'clinic_id' => $clinicId]);
    }
}
