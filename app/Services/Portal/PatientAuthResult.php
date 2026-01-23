<?php

declare(strict_types=1);

namespace App\Services\Portal;

final class PatientAuthResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
    ) {}
}
