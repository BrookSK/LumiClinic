<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Core\Container\Container;

/**
 * System-level encryption service for secrets not scoped to a specific clinic.
 * Uses AES-256-GCM. Key is stored in system_settings and auto-generated on first use.
 */
final class SystemCryptoService
{
    public function __construct(private readonly Container $container) {}

    private function systemKey(): string
    {
        $pdo = $this->container->get(\PDO::class);

        // 1. Try system_settings first (always exists, most reliable)
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
            // fall through
        }

        // 2. Try ai_billing_settings.crypto_key (may not exist if migration pending)
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
            // column doesn't exist yet — fall through
        }

        // 3. Generate new key and save in system_settings
        $newKeyRaw = random_bytes(32);
        $newKeyHex = bin2hex($newKeyRaw);

        try {
            $pdo->prepare(
                "INSERT INTO system_settings (`key`, value_text) VALUES ('system.encryption_key', :k)
                 ON DUPLICATE KEY UPDATE value_text = :k"
            )->execute(['k' => $newKeyHex]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Não foi possível armazenar a chave de criptografia: ' . $e->getMessage());
        }

        // Also try ai_billing_settings if column exists
        try {
            $pdo->exec("INSERT IGNORE INTO ai_billing_settings (id, price_per_minute_brl, cost_per_minute_brl) VALUES (1, 0.0910, 0.0350)");
            $pdo->prepare("UPDATE ai_billing_settings SET crypto_key = :k WHERE id = 1")->execute(['k' => $newKeyHex]);
        } catch (\Throwable $e) {
            // column doesn't exist yet — system_settings is enough
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
