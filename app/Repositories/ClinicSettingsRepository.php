<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClinicSettingsRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string, mixed>|null */
    public function findByClinicId(int $clinicId): ?array
    {
        $sql = "
            SELECT clinic_id, timezone, language, encryption_key
            FROM clinic_settings
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function update(int $clinicId, string $timezone, string $language): void
    {
        $sql = "
            UPDATE clinic_settings
               SET timezone = :timezone,
                   language = :language,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'timezone' => $timezone,
            'language' => $language,
        ]);
    }
}
