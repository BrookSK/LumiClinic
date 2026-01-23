<?php

declare(strict_types=1);

namespace App\Services\Patients;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientContentAccessRepository;
use App\Repositories\PatientContentRepository;
use App\Repositories\PatientRepository;
use App\Services\Auth\AuthService;

final class PatientContentAdminService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{contents:list<array<string,mixed>>} */
    public function index(string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new PatientContentRepository($this->container->get(\PDO::class));
        $items = $repo->listActiveByClinic($clinicId, 200);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log($actorId, $clinicId, 'patients.content.view', [], $ip);

        return ['contents' => $items];
    }

    public function create(string $type, string $title, ?string $description, ?string $url, ?string $procedureType, ?string $audience, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $type = $type === '' ? 'link' : $type;
        if (!in_array($type, ['link', 'pdf', 'video'], true)) {
            $type = 'link';
        }

        $title = trim($title);
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $repo = new PatientContentRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $type, $title, $description, $url, $procedureType, $audience, $actorId);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log($actorId, $clinicId, 'patients.content.create', ['content_id' => $id], $ip);

        return $id;
    }

    public function grantToPatient(int $patientId, int $contentId, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $patients = new PatientRepository($this->container->get(\PDO::class));
        if ($patients->findById($clinicId, $patientId) === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $repo = new PatientContentAccessRepository($this->container->get(\PDO::class));
        $repo->grant($clinicId, $patientId, $contentId, $actorId);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log($actorId, $clinicId, 'patients.content.grant', ['patient_id' => $patientId, 'content_id' => $contentId], $ip);
    }
}
