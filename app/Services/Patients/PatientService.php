<?php

declare(strict_types=1);

namespace App\Services\Patients;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\DataVersionRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;

final class PatientService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function search(string $q, int $limit = 50, int $offset = 0): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new PatientRepository($this->container->get(\PDO::class));
        $limit = max(1, min($limit, 200));
        $offset = max(0, $offset);
        return $repo->searchByClinic($clinicId, $q, $limit, $offset);
    }

    /** @return list<array<string, mixed>> */
    public function listReferenceProfessionals(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ProfessionalRepository($this->container->get(\PDO::class));
        return $repo->listActiveByClinic($clinicId);
    }

    /** @param array{name:string,email:?string,phone:?string,birth_date:?string,sex:?string,cpf:?string,address:?string,notes:?string,reference_professional_id:?int} $data */
    public function create(array $data, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $cpf = null;
        if ($data['cpf'] !== null && $data['cpf'] !== '') {
            $cpfDigits = preg_replace('/\D+/', '', $data['cpf']);
            $cpfDigits = $cpfDigits === null ? '' : $cpfDigits;

            if ($cpfDigits !== '') {
                $cpf = $cpfDigits;
            }
        }

        $repo = new PatientRepository($this->container->get(\PDO::class));
        $id = $repo->createWithClinicalFields(
            $clinicId,
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['birth_date'],
            $data['sex'],
            $cpf,
            $data['address'],
            $data['notes'],
            $data['reference_professional_id']
        );

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($actorId, $clinicId, 'patients.create', ['patient_id' => $id], $ip);

        return $id;
    }

    /** @return array<string, mixed>|null */
    public function get(int $patientId, string $ip): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new PatientRepository($this->container->get(\PDO::class));
        $patient = $repo->findClinicalById($clinicId, $patientId);

        if ($patient !== null) {
            $audit = new AuditLogRepository($this->container->get(\PDO::class));
            $audit->log($actorId, $clinicId, 'patients.view', ['patient_id' => $patientId], $ip);
        }

        return $patient;
    }

    /** @param array{name:string,email:?string,phone:?string,birth_date:?string,sex:?string,cpf:?string,address:?string,notes:?string,reference_professional_id:?int,status:string} $data */
    public function update(int $patientId, array $data, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientRepository($pdo);
        $existing = $repo->findClinicalById($clinicId, $patientId);

        if ($existing !== null) {
            (new DataVersionRepository($pdo))->record(
                $clinicId,
                'patient',
                $patientId,
                'patients.update',
                $existing,
                $actorId,
                $ip,
                $userAgent
            );
        }

        $cpf = $existing !== null ? ($existing['cpf'] ?? null) : null;
        $cpf = $cpf !== null ? (string)$cpf : null;

        if ($data['cpf'] !== null && $data['cpf'] !== '') {
            $cpfDigits = preg_replace('/\D+/', '', $data['cpf']);
            $cpfDigits = $cpfDigits === null ? '' : $cpfDigits;

            if ($cpfDigits !== '') {
                $cpf = $cpfDigits;
            }
        }
        $repo->updateClinicalFields(
            $clinicId,
            $patientId,
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['birth_date'],
            $data['sex'],
            $cpf,
            $data['address'],
            $data['notes'],
            $data['reference_professional_id'],
            $data['status']
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.update', ['patient_id' => $patientId], $ip, $roleCodes, 'patient', $patientId, $userAgent);
    }
}
