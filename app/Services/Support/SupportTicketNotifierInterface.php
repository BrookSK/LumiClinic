<?php

declare(strict_types=1);

namespace App\Services\Support;

/**
 * Gancho de notificação de novos chamados. A implementação padrão não faz nada (SupportNullTicketNotifier).
 * Para plugar um canal real, registre no container o id 'support.ticket_notifier' com uma implementação desta interface.
 */
interface SupportTicketNotifierInterface
{
    /**
     * @param array<string,mixed> $ticket linha de support_tickets mais as chaves 'context' (array decodificado),
     *                                    'clinic_name', 'user_name' e 'transcript' (list de role/content)
     */
    public function notifyCreated(array $ticket): void;
}
