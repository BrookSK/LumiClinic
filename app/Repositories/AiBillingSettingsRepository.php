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
            if (in_array($col, ['asaas_api_key_encrypted', 'openai_api_key_encrypted'], true)) {
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
        $this->pdo->prepare($sql)->execute($params);
    }
}
