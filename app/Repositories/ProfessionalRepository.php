<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProfessionalRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function listActiveByClinic(int $clinicId): array
    {
        $sql = "
            SELECT id, clinic_id, user_id, name, specialty, allow_online_booking, status
            FROM professionals
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
              AND status = 'ativo'
            ORDER BY name ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $clinicId, int $professionalId): ?array
    {
        $sql = "
            SELECT id, clinic_id, user_id, name, specialty, allow_online_booking, status
            FROM professionals
            WHERE id = :id
              AND clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $professionalId, 'clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByUserId(int $clinicId, int $userId): ?array
    {
        $sql = "
            SELECT id, clinic_id, user_id, name, specialty, allow_online_booking, status
            FROM professionals
            WHERE clinic_id = :clinic_id
              AND user_id = :user_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $clinicId, ?int $userId, string $name, ?string $specialty, bool $allowOnlineBooking): int
    {
        $sql = "
            INSERT INTO professionals (clinic_id, user_id, name, specialty, allow_online_booking, status, created_at)
            VALUES (:clinic_id, :user_id, :name, :specialty, :allow_online_booking, 'ativo', NOW())
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'name' => $name,
            'specialty' => ($specialty === '' ? null : $specialty),
            'allow_online_booking' => $allowOnlineBooking ? 1 : 0,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $clinicId, int $professionalId, string $name, ?string $specialty, bool $allowOnlineBooking): void
    {
        $stmt = $this->pdo->prepare("\n            UPDATE professionals\n            SET name = :name,\n                specialty = :specialty,\n                allow_online_booking = :allow_online_booking,\n                updated_at = NOW()\n            WHERE id = :id\n              AND clinic_id = :clinic_id\n              AND deleted_at IS NULL\n            LIMIT 1\n        ");

        $stmt->execute([
            'id' => $professionalId,
            'clinic_id' => $clinicId,
            'name' => $name,
            'specialty' => ($specialty === '' ? null : $specialty),
            'allow_online_booking' => $allowOnlineBooking ? 1 : 0,
        ]);
    }

    public function softDelete(int $clinicId, int $professionalId): void
    {
        $stmt = $this->pdo->prepare("\n            UPDATE professionals\n            SET status = 'inativo',\n                deleted_at = NOW(),\n                updated_at = NOW()\n            WHERE id = :id\n              AND clinic_id = :clinic_id\n              AND deleted_at IS NULL\n            LIMIT 1\n        ");

        $stmt->execute([
            'id' => $professionalId,
            'clinic_id' => $clinicId,
        ]);
    }
}
