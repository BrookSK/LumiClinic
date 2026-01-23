<?php

declare(strict_types=1);

namespace App\Services\Observability;

use App\Core\Container\Container;
use App\Repositories\EventLogRepository;
use App\Services\Auth\AuthService;

final class SystemEvent
{
    /** @param array<string,mixed> $payload */
    public static function dispatch(
        Container $container,
        string $event,
        array $payload = [],
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        $auth = new AuthService($container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        $payload = self::sanitizePayload($container, $payload);

        $role = null;
        if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
            $role = 'super_admin';
        } elseif (isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) && $_SESSION['role_codes'] !== []) {
            $role = (string)($_SESSION['role_codes'][0] ?? null);
        }

        (new EventLogRepository($container->get(\PDO::class)))->log(
            $clinicId,
            $userId,
            $role,
            $event,
            $entityType,
            $entityId,
            $payload,
            $ip,
            $userAgent
        );
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private static function sanitizePayload(Container $container, array $payload): array
    {
        $config = $container->has('config') ? $container->get('config') : null;
        $obs = is_array($config) && isset($config['observability']) && is_array($config['observability'])
            ? $config['observability']
            : [];

        $maxBytes = (int)($obs['event_payload_max_bytes'] ?? 16384);
        $maxBytes = max(256, min(1024 * 256, $maxBytes));

        $masked = self::maskRecursive($payload);

        $json = json_encode($masked, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return ['_error' => 'payload_json_failed'];
        }

        if (strlen($json) <= $maxBytes) {
            return $masked;
        }

        $truncated = substr($json, 0, $maxBytes);
        return [
            '_truncated' => true,
            '_payload_json_prefix' => $truncated,
        ];
    }

    /** @param mixed $value @return mixed */
    private static function maskRecursive($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $key = is_string($k) ? strtolower($k) : null;
                if ($key !== null && self::isSensitiveKey($key)) {
                    $out[$k] = '[REDACTED]';
                    continue;
                }
                $out[$k] = self::maskRecursive($v);
            }
            return $out;
        }

        if (is_string($value)) {
            if (strlen($value) > 4096) {
                return substr($value, 0, 4096);
            }
            return $value;
        }

        return $value;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $needles = [
            'password', 'senha',
            'token', 'access_token', 'refresh_token',
            'authorization', 'auth',
            'api_key', 'secret', 'webhook_secret',
            'cpf', 'cnpj', 'rg',
            'card', 'card_number', 'cvv',
        ];

        foreach ($needles as $n) {
            if ($key === $n) {
                return true;
            }
            if (str_contains($key, $n)) {
                return true;
            }
        }

        return false;
    }
}
