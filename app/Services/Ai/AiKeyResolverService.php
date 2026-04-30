<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Container\Container;
use App\Repositories\AiBillingSettingsRepository;
use App\Repositories\AiWalletRepository;
use App\Repositories\ClinicSettingsRepository;
use App\Services\Security\CryptoService;
use App\Services\Security\SystemCryptoService;
use App\Services\System\SystemSettingsService;

/**
 * Resolves which OpenAI key to use for a given clinic.
 *
 * Priority (Property 1):
 *   1. Clinic's own key (clinic_settings.openai_api_key_encrypted)
 *   2. Superadmin's own key (system_settings: ai.openai.global_api_key)
 *   3. Developer's key (ai_billing_settings.openai_api_key_encrypted) — only if wallet balance > 0
 *
 * Returns: {key: string, source: 'clinic'|'superadmin'|'wallet', wallet_mode: bool}
 */
final class AiKeyResolverService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array{key: string, source: 'clinic'|'superadmin'|'wallet', wallet_mode: bool}
     * @throws \RuntimeException when no key is available
     */
    public function resolve(int $clinicId): array
    {
        $pdo = $this->container->get(\PDO::class);

        // Priority 1: Clinic's own key
        $clinicSettings = (new ClinicSettingsRepository($pdo))->findByClinicId($clinicId);
        $clinicKeyEnc = is_array($clinicSettings) ? trim((string)($clinicSettings['openai_api_key_encrypted'] ?? '')) : '';

        if ($clinicKeyEnc !== '') {
            try {
                $key = (new CryptoService($this->container))->decrypt($clinicId, $clinicKeyEnc);
                if (trim($key) !== '') {
                    return ['key' => $key, 'source' => 'clinic', 'wallet_mode' => false];
                }
            } catch (\Throwable $e) {
                error_log('[AiKeyResolver] Failed to decrypt clinic key for clinic #' . $clinicId . ': ' . $e->getMessage());
                throw new \RuntimeException('Falha ao descriptografar chave OpenAI.');
            }
        }

        // Priority 2: Superadmin's own key (global system key)
        $systemSettings = new SystemSettingsService($this->container);
        $globalKey = trim((string)($systemSettings->getText('ai.openai.global_api_key') ?? ''));

        if ($globalKey !== '') {
            return ['key' => $globalKey, 'source' => 'superadmin', 'wallet_mode' => false];
        }

        // Priority 3: Developer's key via wallet (only if wallet balance > 0)
        $billingSettings = (new AiBillingSettingsRepository($pdo))->getOrCreate();
        $devKeyEnc = trim((string)($billingSettings['openai_api_key_encrypted'] ?? ''));

        if ($devKeyEnc !== '') {
            $env = (string)($billingSettings['asaas_mode'] ?? 'sandbox');
            $wallet = (new AiWalletRepository($pdo, $env))->getOrCreate();
            $balance = (float)($wallet['balance_brl'] ?? 0);

            if ($balance > 0) {
                try {
                    $devKey = (new SystemCryptoService($this->container))->decrypt($devKeyEnc);
                    if (trim($devKey) !== '') {
                        return ['key' => $devKey, 'source' => 'wallet', 'wallet_mode' => true];
                    }
                } catch (\Throwable $e) {
                    error_log('[AiKeyResolver] Failed to decrypt developer key: ' . $e->getMessage());
                    throw new \RuntimeException('Falha ao descriptografar chave OpenAI.');
                }
            }
        }

        throw new \RuntimeException(
            'Transcrição indisponível: configure uma chave OpenAI ou recarregue a Carteira de IA.'
        );
    }
}
