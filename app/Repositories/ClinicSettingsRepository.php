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
            SELECT clinic_id, timezone, language, week_start_weekday, week_end_weekday, encryption_key,
                   openai_api_key_encrypted,
                   zapi_instance_id, zapi_token_encrypted
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

    public function updateOpenAiApiKeyEncrypted(int $clinicId, ?string $encrypted): void
    {
        $sql = "
            UPDATE clinic_settings
               SET openai_api_key_encrypted = :encrypted,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'encrypted' => ($encrypted === null || trim($encrypted) === '') ? null : $encrypted,
        ]);
    }

    public function updateZapiConfig(int $clinicId, ?string $instanceId, ?string $tokenEncrypted): void
    {
        $sql = "
            UPDATE clinic_settings
               SET zapi_instance_id = :instance_id,
                   zapi_token_encrypted = :token_encrypted,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $instanceId = $instanceId === null ? null : trim($instanceId);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'instance_id' => ($instanceId === null || $instanceId === '') ? null : $instanceId,
            'token_encrypted' => ($tokenEncrypted === null || trim($tokenEncrypted) === '') ? null : $tokenEncrypted,
        ]);
    }

    public function update(int $clinicId, string $timezone, string $language, ?int $weekStartWeekday = null, ?int $weekEndWeekday = null): void
    {
        $sql = "
            UPDATE clinic_settings
               SET timezone = :timezone,
                   language = :language,
                   week_start_weekday = :week_start_weekday,
                   week_end_weekday = :week_end_weekday,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'timezone' => $timezone,
            'language' => $language,
            'week_start_weekday' => $weekStartWeekday,
            'week_end_weekday' => $weekEndWeekday,
        ]);
    }
}
