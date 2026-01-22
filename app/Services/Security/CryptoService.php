<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Core\Container\Container;
use App\Repositories\ClinicSettingsRepository;
use App\Services\Auth\AuthService;

final class CryptoService
{
    public function __construct(private readonly Container $container) {}

    private function clinicKey(int $clinicId): string
    {
        $repo = new ClinicSettingsRepository($this->container->get(\PDO::class));
        $settings = $repo->findByClinicId($clinicId);

        $keyHex = is_array($settings) && isset($settings['encryption_key']) ? (string)$settings['encryption_key'] : '';
        $keyHex = trim($keyHex);

        if ($keyHex === '') {
            throw new \RuntimeException('Chave de criptografia não configurada para a clínica.');
        }

        $raw = @hex2bin($keyHex);
        if ($raw === false || strlen($raw) < 32) {
            throw new \RuntimeException('Chave de criptografia inválida para a clínica.');
        }

        return substr($raw, 0, 32);
    }

    public function encryptForActiveClinic(string $plaintext): string
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        return $this->encrypt($clinicId, $plaintext);
    }

    public function decryptForActiveClinic(string $ciphertextB64): string
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        return $this->decrypt($clinicId, $ciphertextB64);
    }

    public function encrypt(int $clinicId, string $plaintext): string
    {
        $key = $this->clinicKey($clinicId);
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
            throw new \RuntimeException('Falha ao criptografar.');
        }

        return base64_encode($nonce . $tag . $cipherRaw);
    }

    public function decrypt(int $clinicId, string $ciphertextB64): string
    {
        $key = $this->clinicKey($clinicId);
        $bin = base64_decode($ciphertextB64, true);
        if ($bin === false || strlen($bin) < 12 + 16 + 1) {
            throw new \RuntimeException('Ciphertext inválido.');
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
            throw new \RuntimeException('Falha ao descriptografar.');
        }

        return $plain;
    }
}
