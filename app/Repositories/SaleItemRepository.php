<?php

declare(strict_types=1);

namespace App\Repositories;

final class SaleItemRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listBySale(int $clinicId, int $saleId): array
    {
        $sql = "
            SELECT id, clinic_id, sale_id,
                   type, reference_id, professional_id,
                   quantity, unit_price, subtotal,
                   created_at, updated_at
            FROM sale_items
            WHERE clinic_id = :clinic_id
              AND sale_id = :sale_id
              AND deleted_at IS NULL
            ORDER BY id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'sale_id' => $saleId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function create(
        int $clinicId,
        int $saleId,
        string $type,
        int $referenceId,
        ?int $professionalId,
        int $quantity,
        string $unitPrice,
        string $subtotal
    ): int {
        $sql = "
            INSERT INTO sale_items (
                clinic_id, sale_id,
                type, reference_id, professional_id,
                quantity, unit_price, subtotal,
                created_at
            )
            VALUES (
                :clinic_id, :sale_id,
                :type, :reference_id, :professional_id,
                :quantity, :unit_price, :subtotal,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'sale_id' => $saleId,
            'type' => $type,
            'reference_id' => $referenceId,
            'professional_id' => $professionalId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDelete(int $clinicId, int $id): void
    {
        $sql = "
            UPDATE sale_items
            SET deleted_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'clinic_id' => $clinicId]);
    }

    public function saleHasProfessional(int $clinicId, int $saleId, int $professionalId): bool
    {
        $sql = "
            SELECT 1
            FROM sale_items
            WHERE clinic_id = :clinic_id
              AND sale_id = :sale_id
              AND deleted_at IS NULL
              AND professional_id = :professional_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'sale_id' => $saleId,
            'professional_id' => $professionalId,
        ]);

        return (bool)$stmt->fetchColumn();
    }
}
