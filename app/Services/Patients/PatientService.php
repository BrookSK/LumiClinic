<?php

declare(strict_types=1);

namespace App\Services\Patients;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;
use App\Services\Security\CryptoService;

final class PatientService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function search(string $q): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new PatientRepository($this->container->get(\PDO::class));
        return $repo->searchByClinic($clinicId, $q, 50);
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

        $cpfEncrypted = null;
        $cpfLast4 = null;
        if ($data['cpf'] !== null && $data['cpf'] !== '') {
            $cpfDigits = preg_replace('/\\D+/', '', $data['cpf']);
            $cpfDigits = $cpfDigits === null ? '' : $cpfDigits;

            if ($cpfDigits !== '') {
                $crypto = new CryptoService($this->container);
                $cpfEncrypted = $crypto->encrypt($clinicId, $cpfDigits);
                $cpfLast4 = substr($cpfDigits, -4);
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
            $cpfEncrypted,
            $cpfLast4,
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
    public function update(int $patientId, array $data, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new PatientRepository($this->container->get(\PDO::class));
        $existing = $repo->findClinicalById($clinicId, $patientId);

        $cpfEncrypted = $existing !== null ? ($existing['cpf_encrypted'] ?? null) : null;
        $cpfEncrypted = $cpfEncrypted !== null ? (string)$cpfEncrypted : null;

        $cpfLast4 = $existing !== null ? ($existing['cpf_last4'] ?? null) : null;
        $cpfLast4 = $cpfLast4 !== null ? (string)$cpfLast4 : null;

        if ($data['cpf'] !== null && $data['cpf'] !== '') {
            $cpfDigits = preg_replace('/\D+/', '', $data['cpf']);
            $cpfDigits = $cpfDigits === null ? '' : $cpfDigits;

            if ($cpfDigits !== '') {
                $crypto = new CryptoService($this->container);
                $cpfEncrypted = $crypto->encrypt($clinicId, $cpfDigits);
                $cpfLast4 = substr($cpfDigits, -4);
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
            $cpfEncrypted,
            $cpfLast4,
            $data['address'],
            $data['notes'],
            $data['reference_professional_id'],
            $data['status']
        );

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($actorId, $clinicId, 'patients.update', ['patient_id' => $patientId], $ip);
    }
}
