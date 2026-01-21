<?php

declare(strict_types=1);

namespace App\Services\Auth;

final class AuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
    ) {}
}
