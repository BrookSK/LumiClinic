<?php

declare(strict_types=1);

namespace App\Core\Http;

final class Request
{
    /** @param array<string, mixed> $query */
    /** @param array<string, mixed> $body */
    /** @param array<string, mixed> $server */
    /** @param array<string, string> $headers */
    private function __construct(
        private array $query,
        private array $body,
        private array $server,
        private array $headers,
        private array $cookies,
    ) {}

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = (string)$value;
            }
        }

        return new self($_GET, $_POST, $_SERVER, $headers, $_COOKIE);
    }

    public function method(): string
    {
        return strtoupper((string)($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $uri = (string)($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);

        return $path ? rtrim($path, '/') ?: '/' : '/';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $k = strtolower($name);
        return $this->headers[$k] ?? $default;
    }

    public function ip(): string
    {
        return (string)($this->server['REMOTE_ADDR'] ?? '');
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }
}
