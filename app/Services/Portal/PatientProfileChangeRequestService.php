<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\PatientProfileChangeRequestRepository;
use App\Repositories\PatientRepository;

final class PatientProfileChangeRequestService
{
    public function __construct(private readonly Container $container) {}

    public function createForCurrentPatient(array $requestedFields, string $ip): void
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        $patientUserId = $auth->patientUserId();

        if ($clinicId === null || $patientId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente não encontrado.');
        }

        $fields = [
            'name' => trim((string)($requestedFields['name'] ?? '')),
            'email' => trim((string)($requestedFields['email'] ?? '')),
            'phone' => trim((string)($requestedFields['phone'] ?? '')),
            'birth_date' => trim((string)($requestedFields['birth_date'] ?? '')),
        ];

        if ($fields['name'] === '') {
            throw new \RuntimeException('Nome é obrigatório.');
        }

        // Store only the fields that are actually changing (optional, but keeps payload clean)
        $payload = [];
        foreach ($fields as $k => $v) {
            $current = trim((string)($patient[$k] ?? ''));
            if ($v !== '' && $v !== $current) {
                $payload[$k] = $v;
            }
        }

        if ($payload === []) {
            throw new \RuntimeException('Nenhuma alteração foi detectada.');
        }

        $repo = new PatientProfileChangeRequestRepository($pdo);
        $repo->create($clinicId, $patientId, $patientUserId, $payload);
    }
}
