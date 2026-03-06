<?php

declare(strict_types=1);

namespace App\Repositories;

final class GoogleOAuthTokenRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed>|null */
    public function findActiveByClinicUser(int $clinicId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("\n            SELECT id, clinic_id, user_id, provider, scopes, access_token, refresh_token_encrypted, expires_at, calendar_id, last_error, created_at, updated_at, revoked_at\n            FROM google_oauth_tokens\n            WHERE clinic_id = :clinic_id\n              AND user_id = :user_id\n              AND provider = 'google'\n              AND revoked_at IS NULL\n            LIMIT 1\n        ");
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("\n            SELECT id, clinic_id, user_id, provider, scopes, access_token, refresh_token_encrypted, expires_at, calendar_id, last_error, created_at, updated_at, revoked_at\n            FROM google_oauth_tokens\n            WHERE id = :id\n            LIMIT 1\n        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(
        int $clinicId,
        int $userId,
        ?string $scopes,
        ?string $accessToken,
        ?string $refreshTokenEncrypted,
        ?string $expiresAt,
        ?string $calendarId,
        ?string $lastError
    ): int {
        $existing = $this->findActiveByClinicUser($clinicId, $userId);
        if ($existing !== null) {
            $stmt = $this->pdo->prepare("\n                UPDATE google_oauth_tokens\n                SET scopes = :scopes,\n                    access_token = :access_token,\n                    refresh_token_encrypted = :refresh_token_encrypted,\n                    expires_at = :expires_at,\n                    calendar_id = :calendar_id,\n                    last_error = :last_error,\n                    updated_at = NOW()\n                WHERE id = :id\n                LIMIT 1\n            ");
            $stmt->execute([
                'id' => (int)$existing['id'],
                'scopes' => ($scopes === '' ? null : $scopes),
                'access_token' => ($accessToken === '' ? null : $accessToken),
                'refresh_token_encrypted' => ($refreshTokenEncrypted === '' ? null : $refreshTokenEncrypted),
                'expires_at' => ($expiresAt === '' ? null : $expiresAt),
                'calendar_id' => ($calendarId === '' ? null : $calendarId),
                'last_error' => ($lastError === '' ? null : $lastError),
            ]);
            return (int)$existing['id'];
        }

        $stmt = $this->pdo->prepare("\n            INSERT INTO google_oauth_tokens (\n                clinic_id, user_id, provider,\n                scopes,\n                access_token, refresh_token_encrypted, expires_at,\n                calendar_id,\n                last_error,\n                created_at\n            ) VALUES (\n                :clinic_id, :user_id, 'google',\n                :scopes,\n                :access_token, :refresh_token_encrypted, :expires_at,\n                :calendar_id,\n                :last_error,\n                NOW()\n            )\n        ");
        $stmt->execute([
            'clinic_id' => $clinicId,
            'user_id' => $userId,
            'scopes' => ($scopes === '' ? null : $scopes),
            'access_token' => ($accessToken === '' ? null : $accessToken),
            'refresh_token_encrypted' => ($refreshTokenEncrypted === '' ? null : $refreshTokenEncrypted),
            'expires_at' => ($expiresAt === '' ? null : $expiresAt),
            'calendar_id' => ($calendarId === '' ? null : $calendarId),
            'last_error' => ($lastError === '' ? null : $lastError),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function revoke(int $clinicId, int $userId): void
    {
        $stmt = $this->pdo->prepare("\n            UPDATE google_oauth_tokens\n            SET revoked_at = NOW(), updated_at = NOW()\n            WHERE clinic_id = :clinic_id\n              AND user_id = :user_id\n              AND provider = 'google'\n              AND revoked_at IS NULL\n            LIMIT 1\n        ");
        $stmt->execute(['clinic_id' => $clinicId, 'user_id' => $userId]);
    }
}
