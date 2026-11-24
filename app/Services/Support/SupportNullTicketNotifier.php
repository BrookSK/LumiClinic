<?php

declare(strict_types=1);

namespace App\Services\Support;

final class SupportNullTicketNotifier implements SupportTicketNotifierInterface
{
    public function notifyCreated(array $ticket): void
    {
    }
}
