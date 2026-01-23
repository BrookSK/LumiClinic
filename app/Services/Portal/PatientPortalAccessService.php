<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientPasswordResetRepository;
use App\Repositories\PatientUserRepository;
use App\Services\Auth\AuthService;

final class PatientPortalAccessService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{patient_user:?array<string,mixed>} */
    public function getAccess(int $patientId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new PatientUserRepository($this->container->get(\PDO::class));
        $patientUser = $repo->findByPatientId($clinicId, $patientId);

        return ['patient_user' => $patientUser];
    }

    /** @return array{reset_token:string} */
    public function ensureAccessAndCreateReset(int $patientId, string $email, string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            throw new \RuntimeException('E-mail do paciente é obrigatório para criar acesso.');
        }

        $patients = new PatientRepository($this->container->get(\PDO::class));
        $patient = $patients->findById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente não encontrado.');
        }

        $pdo = $this->container->get(\PDO::class);
        $users = new PatientUserRepository($pdo);

        $pdo->beginTransaction();
        try {
            $existing = $users->findByPatientIdForUpdate($clinicId, $patientId);
            if ($existing === null) {
                $patientUserId = $users->create($clinicId, $patientId, $email, password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT));
            } else {
                $patientUserId = (int)$existing['id'];
                $users->updateEmail($clinicId, $patientUserId, $email);
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60);

            $resets = new PatientPasswordResetRepository($pdo);
            $resets->create($clinicId, $patientUserId, $tokenHash, $expiresAt, $ip);

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'patients.portal_access.ensure', ['patient_id' => $patientId, 'patient_user_id' => $patientUserId], $ip);

            $pdo->commit();
            return ['reset_token' => $token];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
