<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\AppointmentRepository;
use App\Repositories\MedicalRecordRepository;
use App\Repositories\PatientLgpdRequestRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientUserRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SaleRepository;
use App\Services\Auth\AuthService;

final class ComplianceLgpdService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listRequests(?string $status, int $limit, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $limit = max(1, min($limit, 500));

        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientLgpdRequestRepository($pdo);
        $items = $repo->listByClinic($clinicId, $status, $limit);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.lgpd.requests.view', ['status' => $status, 'limit' => $limit], $ip, $roleCodes, null, null, $userAgent);

        return $items;
    }

    public function processRequest(int $requestId, string $decision, ?string $note, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $decision = strtolower(trim($decision));
        if (!in_array($decision, ['processed', 'rejected'], true)) {
            throw new \RuntimeException('Decisão inválida.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientLgpdRequestRepository($pdo);
        $req = $repo->findById($clinicId, $requestId);
        if ($req === null) {
            throw new \RuntimeException('Solicitação inválida.');
        }

        if ((string)$req['status'] !== 'pending') {
            return;
        }

        if ($decision === 'processed') {
            $repo->markProcessed($clinicId, $requestId, $actorId, $note);
        } else {
            $repo->markRejected($clinicId, $requestId, $actorId, $note);
        }

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.lgpd.requests.process', ['request_id' => $requestId, 'decision' => $decision], $ip, $roleCodes, 'patient_lgpd_request', $requestId, $userAgent);
    }

    /** @return array<string,mixed> */
    public function exportPatientDataJson(int $requestId, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $lgpd = new PatientLgpdRequestRepository($pdo);
        $req = $lgpd->findById($clinicId, $requestId);
        if ($req === null) {
            throw new \RuntimeException('Solicitação inválida.');
        }

        $patientId = (int)$req['patient_id'];

        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $patientUserRepo = new PatientUserRepository($pdo);
        $patientUser = $patientUserRepo->findByPatientId($clinicId, $patientId);

        $appointmentsRepo = new AppointmentRepository($pdo);
        $appointmentsUpcoming = $appointmentsRepo->listUpcomingByPatient($clinicId, $patientId, 200);

        $mrRepo = new MedicalRecordRepository($pdo);
        $medicalRecords = $mrRepo->listByPatient($clinicId, $patientId, 200);

        $salesRepo = new SaleRepository($pdo);
        $sales = $salesRepo->listByClinic($clinicId, 500, null);
        $sales = array_values(array_filter($sales, fn ($s) => isset($s['patient_id']) && (int)$s['patient_id'] === $patientId));

        $paymentsRepo = new PaymentRepository($pdo);
        $payments = [];
        foreach ($sales as $s) {
            $sid = (int)$s['id'];
            foreach ($paymentsRepo->listBySale($clinicId, $sid) as $p) {
                $payments[] = $p;
            }
        }

        $payload = [
            'exported_at' => (new \DateTimeImmutable('now'))->format('c'),
            'clinic_id' => $clinicId,
            'patient' => $patient,
            'patient_user' => $patientUser,
            'appointments_upcoming' => $appointmentsUpcoming,
            'medical_records' => $medicalRecords,
            'sales' => $sales,
            'payments' => $payments,
            'lgpd_request' => $req,
        ];

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'compliance.lgpd.export', ['request_id' => $requestId, 'patient_id' => $patientId], $ip, $roleCodes, 'patient', $patientId, $userAgent);

        return $payload;
    }

    public function anonymizePatientFromRequest(int $requestId, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $lgpd = new PatientLgpdRequestRepository($pdo);
        $req = $lgpd->findById($clinicId, $requestId);
        if ($req === null) {
            throw new \RuntimeException('Solicitação inválida.');
        }

        $patientId = (int)$req['patient_id'];

        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $pdo->beginTransaction();
        try {
            $patients->anonymizeById($clinicId, $patientId);

            $patientUsers = new PatientUserRepository($pdo);
            $patientUsers->anonymizeByPatientId($clinicId, $patientId);

            $lgpd->markProcessed($clinicId, $requestId, $actorId, 'Anonimização executada.');

            $audit = new AuditLogRepository($pdo);
            $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
            $audit->log($actorId, $clinicId, 'compliance.lgpd.anonymize', ['request_id' => $requestId, 'patient_id' => $patientId], $ip, $roleCodes, 'patient', $patientId, $userAgent);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
