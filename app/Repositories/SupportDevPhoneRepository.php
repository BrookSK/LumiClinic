<?php

declare(strict_types=1);

namespace App\Repositories;

final class SupportDevPhoneRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return list<array<string,mixed>> */
    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, phone, is_active, created_at, updated_at FROM support_dev_phones ORDER BY name ASC, id ASC');
        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    public function listActive(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, phone, is_active FROM support_dev_phones WHERE is_active = 1 ORDER BY id ASC');
        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_dev_phones WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name, string $phone): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO support_dev_phones (name, phone, is_active, created_at) VALUES (:name, :phone, 1, NOW())');
        $stmt->execute(['name' => $name, 'phone' => $phone]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $phone): void
    {
        $stmt = $this->pdo->prepare('UPDATE support_dev_phones SET name = :name, phone = :phone WHERE id = :id');
        $stmt->execute(['name' => $name, 'phone' => $phone, 'id' => $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE support_dev_phones SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM support_dev_phones WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
