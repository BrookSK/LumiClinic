<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\WhatsappTemplateRepository;
use App\Services\Auth\AuthService;

final class WhatsappTemplateService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listTemplates(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        return (new WhatsappTemplateRepository($this->container->get(\PDO::class)))->listByClinic($clinicId);
    }

    /** @return array<string,mixed> */
    public function getTemplate(int $templateId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $tpl = (new WhatsappTemplateRepository($this->container->get(\PDO::class)))->findById($clinicId, $templateId);
        if ($tpl === null) {
            throw new \RuntimeException('Template inválido.');
        }

        return $tpl;
    }

    public function createTemplate(string $code, string $name, string $body, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $code = trim($code);
        $name = trim($name);
        $body = trim($body);

        if ($code === '' || $name === '' || $body === '') {
            throw new \RuntimeException('Preencha os campos obrigatórios.');
        }

        $repo = new WhatsappTemplateRepository($this->container->get(\PDO::class));
        $existing = $repo->findByCode($clinicId, $code);
        if ($existing !== null) {
            throw new \RuntimeException('Já existe um template com este código.');
        }

        $id = $repo->create($clinicId, $code, $name, $body);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $actorId,
            $clinicId,
            'whatsapp_templates.create',
            ['template_id' => $id, 'code' => $code],
            $ip
        );

        return $id;
    }

    public function updateTemplate(int $id, string $code, string $name, string $body, string $status, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $code = trim($code);
        $name = trim($name);
        $body = trim($body);
        $status = trim($status);

        if ($id <= 0 || $code === '' || $name === '' || $body === '') {
            throw new \RuntimeException('Preencha os campos obrigatórios.');
        }

        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $repo = new WhatsappTemplateRepository($this->container->get(\PDO::class));
        $current = $repo->findById($clinicId, $id);
        if ($current === null) {
            throw new \RuntimeException('Template inválido.');
        }

        $other = $repo->findByCode($clinicId, $code);
        if ($other !== null && (int)($other['id'] ?? 0) !== $id) {
            throw new \RuntimeException('Já existe um template com este código.');
        }

        $repo->update($clinicId, $id, $code, $name, $body, $status);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $actorId,
            $clinicId,
            'whatsapp_templates.update',
            ['template_id' => $id, 'code' => $code],
            $ip
        );
    }
}
