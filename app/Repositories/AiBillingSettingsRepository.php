<?php

declare(strict_types=1);

namespace App\Repositories;

final class AiBillingSettingsRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /** @return array<string,mixed> */
    public function getOrCreate(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ai_billing_settings WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        $this->pdo->exec("INSERT INTO ai_billing_settings (id, price_per_minute_brl, cost_per_minute_brl) VALUES (1, 0.0910, 0.0350)");

        $stmt = $this->pdo->prepare("SELECT * FROM ai_billing_settings WHERE id = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch() ?: [];
    }

    /**
     * Returns the active webhook secret (encrypted) based on current mode.
     */
    public function getActiveWebhookSecret(): string
    {
        $settings = $this->getOrCreate();
        $mode = (string)($settings['asaas_mode'] ?? 'sandbox');

        $col = $mode === 'production'
            ? 'asaas_webhook_secret_production_encrypted'
            : 'asaas_webhook_secret_sandbox_encrypted';

        return trim((string)($settings[$col] ?? ''));
    }

    /**
     * Returns the active Asaas API key (sandbox or production) based on current mode.
     */
    public function getActiveAsaasKey(): string
    {
        $settings = $this->getOrCreate();
        $mode = (string)($settings['asaas_mode'] ?? 'sandbox');

        if ($mode === 'production') {
            return trim((string)($settings['asaas_api_key_encrypted'] ?? ''));
        }

        // sandbox mode — prefer sandbox key, fall back to prod key
        $sandboxKey = trim((string)($settings['asaas_sandbox_key_encrypted'] ?? ''));
        return $sandboxKey !== '' ? $sandboxKey : trim((string)($settings['asaas_api_key_encrypted'] ?? ''));
    }

    /**
     * Returns the active Asaas base URL based on current mode.
     */
    public function getAsaasBaseUrl(): string
    {
        $settings = $this->getOrCreate();
        $mode = (string)($settings['asaas_mode'] ?? 'sandbox');

        return $mode === 'production'
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';
    }

    /**
     * Partial update — skips empty-string values to preserve existing encrypted keys.
     * Property 9: Blank key fields do not overwrite existing values.
     *
     * @param array<string,mixed> $fields
     */
    public function save(array $fields): void
    {
        $this->getOrCreate(); // ensure row exists

        $allowed = [
            'asaas_api_key_encrypted',
            'asaas_sandbox_key_encrypted',
            'asaas_webhook_secret_sandbox_encrypted',
            'asaas_webhook_secret_production_encrypted',
            'asaas_mode',
            'openai_api_key_encrypted',
            'price_per_minute_brl',
            'cost_per_minute_brl',
            'dev_password_hash',
        ];

        $setClauses = [];
        $params = [];

        foreach ($allowed as $col) {
            if (!array_key_exists($col, $fields)) {
                continue;
            }

            $val = $fields[$col];

            // Skip empty strings for encrypted key fields — preserve existing value
            if (in_array($col, [
                'asaas_api_key_encrypted',
                'asaas_sandbox_key_encrypted',
                'asaas_webhook_secret_sandbox_encrypted',
                'asaas_webhook_secret_production_encrypted',
                'openai_api_key_encrypted',
            ], true)) {
                if ($val === '' || $val === null) {
                    continue;
                }
            }

            $setClauses[] = "`{$col}` = :{$col}";
            $params[$col] = $val;
        }

        if ($setClauses === []) {
            return;
        }

        $params['updated_at'] = date('Y-m-d H:i:s');
        $sql = "UPDATE ai_billing_settings SET " . implode(', ', $setClauses) . ", updated_at = :updated_at WHERE id = 1";

        try {
            $this->pdo->prepare($sql)->execute($params);
        } catch (\PDOException $e) {
            // If a column doesn't exist yet (migration pending), retry without that column
            if (str_contains($e->getMessage(), 'Unknown column')) {
                // Extract the unknown column name from error message
                preg_match("/Unknown column '([^']+)'/", $e->getMessage(), $m);
                $badCol = $m[1] ?? '';
                if ($badCol !== '') {
                    // Remove the bad column and retry
                    $setClauses2 = array_filter($setClauses, fn($c) => !str_contains($c, "`{$badCol}`"));
                    unset($params[$badCol]);
                    if ($setClauses2 !== []) {
                        $sql2 = "UPDATE ai_billing_settings SET " . implode(', ', $setClauses2) . ", updated_at = :updated_at WHERE id = 1";
                        $this->pdo->prepare($sql2)->execute($params);
                    }
                }
                return;
            }
            throw $e;
        }
    }
}
