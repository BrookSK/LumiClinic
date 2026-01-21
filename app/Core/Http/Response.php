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

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/html; charset=UTF-8'], $html);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self($status, ['Location' => $to], '');
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
