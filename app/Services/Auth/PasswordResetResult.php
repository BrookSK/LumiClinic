<?php

declare(strict_types=1);

namespace App\Services\Auth;

final class PasswordResetResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        /** @var array<string,mixed> */
        public readonly array $data = [],
    ) {}
}
