<?php

declare(strict_types=1);

namespace App\Core\Http;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        private int $status = 200,
        private array $headers = ['Content-Type' => 'text/html; charset=UTF-8'],
        private string $body = '',
    ) {}

    /** @param array<string, string> $headers */
    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $base = ['Content-Type' => 'text/html; charset=UTF-8'];
        return new self($status, array_merge($base, $headers), $html);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self($status, ['Location' => $to], '');
    }

    /** @param array<string, string> $headers */
    public static function raw(string $body, int $status = 200, array $headers = []): self
    {
        return new self($status, $headers, $body);
    }

    /** @param array<string, mixed> $data */
    /** @param array<string, string> $headers */
    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        return new self(
            $status,
            array_merge(['Content-Type' => 'application/json; charset=UTF-8'], $headers),
            (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    public function status(): int
    {
        return $this->status;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        foreach ($headers as $name => $value) {
            $clone->headers[(string)$name] = (string)$value;
        }
        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }
}
