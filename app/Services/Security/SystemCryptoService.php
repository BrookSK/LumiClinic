<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Core\Container\Container;

/**
 * System-level encryption service for secrets not scoped to a specific clinic.
 * Uses AES-256-GCM. The encryption key is stored in ai_billing_settings.crypto_key
 * and auto-generated on first use. No external APP_KEY or .env required.
 */
final class SystemCryptoService
{
    public function __construct(private readonly Container $container) {}

    private function systemKey(): string
    {
        $pdo = $this->container->get(\PDO::class);

        // Try to get existing key from ai_billing_settings
        try {
            $stmt = $pdo->prepare("SELECT crypto_key FROM ai_billing_settings WHERE id = 1 LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            $keyHex = $row ? trim((string)($row['crypto_key'] ?? '')) : '';

            if ($keyHex !== '') {
                $raw = @hex2bin($keyHex);
                if ($raw !== false && strlen($raw) >= 32) {
                    return substr($raw, 0, 32);
                }
            }
        } catch (\Throwable $e) {
            // Column may not exist yet — fall through to generate
        }

        // Try system_settings as secondary source
        try {
            $stmt = $pdo->prepare("SELECT value_text FROM system_settings WHERE `key` = 'system.encryption_key' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            $keyHex = $row ? trim((string)($row['value_text'] ?? '')) : '';

            if ($keyHex !== '') {
                $raw = @hex2bin($keyHex);
                if ($raw !== false && strlen($raw) >= 32) {
                    return substr($raw, 0, 32);
                }
            }
        } catch (\Throwable $e) {
            // Fall through
        }

        // Generate a new key and persist it in ai_billing_settings
        $newKeyRaw = random_bytes(32);
        $newKeyHex = bin2hex($newKeyRaw);

        try {
            // Ensure row exists
            $pdo->exec("INSERT IGNORE INTO ai_billing_settings (id, price_per_minute_brl, cost_per_minute_brl) VALUES (1, 0.0910, 0.0350)");
            $pdo->prepare("UPDATE ai_billing_settings SET crypto_key = :k WHERE id = 1")
                ->execute(['k' => $newKeyHex]);
        } catch (\Throwable $e) {
            // If crypto_key column doesn't exist yet, store in system_settings as fallback
            try {
                $pdo->prepare("INSERT INTO system_settings (`key`, value_text) VALUES ('system.encryption_key', :k)
                    ON DUPLICATE KEY UPDATE value_text = :k")
                    ->execute(['k' => $newKeyHex]);
            } catch (\Throwable $e2) {
                throw new \RuntimeException('Não foi possível gerar ou armazenar a chave de criptografia do sistema.');
            }
        }

        return $newKeyRaw;
    }

    public function encrypt(string $plaintext): string
    {
        $key = $this->systemKey();
        $nonce = random_bytes(12);

        $tag = '';
        $cipherRaw = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($cipherRaw === false) {
            throw new \RuntimeException('Falha ao criptografar dado do sistema.');
        }

        return base64_encode($nonce . $tag . $cipherRaw);
    }

    public function decrypt(string $ciphertextB64): string
    {
        $key = $this->systemKey();
        $bin = base64_decode($ciphertextB64, true);
        if ($bin === false || strlen($bin) < 12 + 16 + 1) {
            throw new \RuntimeException('Ciphertext do sistema inválido.');
        }

        $nonce = substr($bin, 0, 12);
        $tag = substr($bin, 12, 16);
        $cipherRaw = substr($bin, 28);

        $plain = openssl_decrypt(
            $cipherRaw,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($plain === false) {
            throw new \RuntimeException('Falha ao descriptografar dado do sistema.');
        }

        return $plain;
    }
}
