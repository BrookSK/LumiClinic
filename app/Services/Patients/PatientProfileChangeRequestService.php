<?php

declare(strict_types=1);

namespace App\Services\Patients;

use App\Core\Container\Container;
use App\Repositories\PatientProfileChangeRequestRepository;
use App\Repositories\PatientRepository;
use App\Services\Auth\AuthService;

final class PatientProfileChangeRequestService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function listRequests(?string $status = null, int $limit = 200, int $offset = 0): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        return (new PatientProfileChangeRequestRepository($pdo))->listByClinic($clinicId, $status, $limit, $offset);
    }

    public function approve(int $requestId, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientProfileChangeRequestRepository($pdo);
        $req = $repo->findById($clinicId, $requestId);
        if ($req === null) {
            throw new \RuntimeException('Solicitação não encontrada.');
        }
        if ((string)($req['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('Solicitação não está pendente.');
        }

        $payload = json_decode((string)($req['requested_fields_json'] ?? '{}'), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $patientId = (int)($req['patient_id'] ?? 0);
        if ($patientId <= 0) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $patientRepo = new PatientRepository($pdo);
        $patient = $patientRepo->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente não encontrado.');
        }

        $name = array_key_exists('name', $payload) ? trim((string)$payload['name']) : (string)$patient['name'];
        $email = array_key_exists('email', $payload) ? trim((string)$payload['email']) : (string)($patient['email'] ?? '');
        $phone = array_key_exists('phone', $payload) ? trim((string)$payload['phone']) : (string)($patient['phone'] ?? '');
        $birthDate = array_key_exists('birth_date', $payload) ? trim((string)$payload['birth_date']) : (string)($patient['birth_date'] ?? '');

        // Apply using existing method, keeping unchanged fields
        $patientRepo->updateClinicalFields(
            $clinicId,
            $patientId,
            $name,
            ($email === '' ? null : $email),
            ($phone === '' ? null : $phone),
            ($birthDate === '' ? null : $birthDate),
            (string)($patient['sex'] ?? ''),
            (string)($patient['cpf'] ?? ''),
            (string)($patient['address'] ?? ''),
            (string)($patient['notes'] ?? ''),
            isset($patient['reference_professional_id']) ? (int)$patient['reference_professional_id'] : null,
            (string)($patient['status'] ?? 'active')
        );

        $repo->approve($clinicId, $requestId, $actorId);
    }

    public function reject(int $requestId, ?string $notes, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientProfileChangeRequestRepository($pdo);
        $req = $repo->findById($clinicId, $requestId);
        if ($req === null) {
            throw new \RuntimeException('Solicitação não encontrada.');
        }
        if ((string)($req['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('Solicitação não está pendente.');
        }

        $repo->reject($clinicId, $requestId, $actorId, $notes);
    }
}
