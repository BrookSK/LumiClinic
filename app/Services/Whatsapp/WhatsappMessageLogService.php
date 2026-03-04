<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Repositories\WhatsappMessageLogQueryRepository;
use App\Services\Auth\AuthService;

final class WhatsappMessageLogService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @param array{status?:string,template_code?:string,from?:string,to?:string,appointment_id?:string,patient_id?:string} $filters
     * @return list<array<string,mixed>>
     */
    public function list(array $filters, int $limit = 100, int $offset = 0): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new WhatsappMessageLogQueryRepository($this->container->get(\PDO::class));
        $limit = max(1, min($limit, 5000));
        $offset = max(0, $offset);
        return $repo->search($clinicId, $filters, $limit, $offset);
    }

    /** @return array<string,mixed> */
    public function get(int $id): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new WhatsappMessageLogQueryRepository($this->container->get(\PDO::class));
        $row = $repo->findByIdDetailed($clinicId, $id);
        if ($row === null) {
            throw new \RuntimeException('Log inválido.');
        }

        return $row;
    }
}
