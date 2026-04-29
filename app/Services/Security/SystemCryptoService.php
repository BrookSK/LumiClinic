<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Core\Container\Container;

/**
 * System-level encryption service for secrets not scoped to a specific clinic.
 * Uses the same AES-256-GCM algorithm as CryptoService but reads the key from
 * system_settings (key: system.encryption_key) or falls back to APP_KEY env var.
 */
final class SystemCryptoService
{
    public function __construct(private readonly Container $container) {}

    private function systemKey(): string
    {
        // Try system_settings first
        try {
            $pdo = $this->container->get(\PDO::class);
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
            // Fall through to APP_KEY
        }

        // Fallback to APP_KEY environment variable
        $appKey = trim((string)(getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? '')));
        if ($appKey === '') {
            throw new \RuntimeException('Chave de criptografia do sistema não configurada. Defina APP_KEY ou system.encryption_key.');
        }

        // Derive a 32-byte key from APP_KEY using SHA-256
        return hash('sha256', $appKey, true);
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
