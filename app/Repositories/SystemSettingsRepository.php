<?php

declare(strict_types=1);

namespace App\Repositories;

final class SystemSettingsRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function getText(string $key): ?string
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        $sql = "
            SELECT value_text
            FROM system_settings
            WHERE `key` = :key
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();

        if (!$row || !array_key_exists('value_text', $row)) {
            return null;
        }

        $v = $row['value_text'];
        return $v === null ? null : (string)$v;
    }

    public function upsertText(string $key, ?string $value): void
    {
        $key = trim($key);
        if ($key === '') {
            throw new \RuntimeException('Parâmetro inválido.');
        }

        $sql = "
            INSERT INTO system_settings (`key`, value_text, created_at)
            VALUES (:key, :value_text, NOW())
            ON DUPLICATE KEY UPDATE
                value_text = VALUES(value_text),
                updated_at = NOW()
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'key' => $key,
            'value_text' => ($value === null || trim($value) === '') ? null : $value,
        ]);
    }
}
