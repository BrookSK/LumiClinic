<?php

declare(strict_types=1);

namespace App\Services\Legal;

use App\Core\Container\Container;
use App\Repositories\LegalDocumentAcceptanceRepository;
use App\Repositories\LegalDocumentRepository;
use App\Services\Auth\AuthService;
use App\Services\Portal\PatientAuthService;

final class LegalDocumentService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listActivePatientPortalDocuments(int $clinicId): array
    {
        $pdo = $this->container->get(\PDO::class);
        return (new LegalDocumentRepository($pdo))->listActiveForPatientPortal($clinicId);
    }

    /** @return list<array<string,mixed>> */
    public function listPendingRequiredForCurrentPatientUser(): array
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientUserId === null) {
            return [];
        }

        $pdo = $this->container->get(\PDO::class);
        $docs = (new LegalDocumentRepository($pdo))->listActiveForPatientPortal($clinicId);
        $accepted = (new LegalDocumentAcceptanceRepository($pdo))->listAcceptedDocumentIdsByPatientUser($clinicId, $patientUserId);
        $acceptedMap = array_fill_keys($accepted, true);

        $out = [];
        foreach ($docs as $d) {
            $id = (int)($d['id'] ?? 0);
            $req = (int)($d['is_required'] ?? 0) === 1;
            if (!$req || $id <= 0) {
                continue;
            }
            if (isset($acceptedMap[$id])) {
                continue;
            }
            $out[] = $d;
        }
        return $out;
    }

    public function acceptForCurrentPatientUser(int $documentId, string $ip, ?string $userAgent): void
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientUserId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $docs = new LegalDocumentRepository($pdo);
        $doc = $docs->findById($documentId);
        if ($doc === null || (int)($doc['clinic_id'] ?? 0) !== $clinicId || (string)($doc['scope'] ?? '') !== 'patient_portal') {
            throw new \RuntimeException('Documento inválido.');
        }
        if ((string)($doc['status'] ?? '') !== 'active') {
            throw new \RuntimeException('Documento inativo.');
        }

        $repo = new LegalDocumentAcceptanceRepository($pdo);
        $acceptedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        try {
            $repo->createForPatientUser($clinicId, $documentId, $patientUserId, $acceptedAt, $ip, $userAgent);
        } catch (\Throwable $e) {
            // Unique constraint: already accepted
            return;
        }
    }

    /** @return list<array<string,mixed>> */
    public function listPendingRequiredForCurrentUser(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            return [];
        }

        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : [];

        $pdo = $this->container->get(\PDO::class);
        $docsRepo = new LegalDocumentRepository($pdo);

        $docs = $docsRepo->listActiveForSystemUser($clinicId, $roleCodes);
        if (in_array('owner', $roleCodes, true)) {
            $docs = array_merge($docsRepo->listActiveForClinicOwner($clinicId), $docs);
        }
        $accepted = (new LegalDocumentAcceptanceRepository($pdo))->listAcceptedDocumentIdsByUser($clinicId, $userId);
        $acceptedMap = array_fill_keys($accepted, true);

        $out = [];
        foreach ($docs as $d) {
            $id = (int)($d['id'] ?? 0);
            $req = (int)($d['is_required'] ?? 0) === 1;
            if (!$req || $id <= 0) {
                continue;
            }
            if (isset($acceptedMap[$id])) {
                continue;
            }
            $out[] = $d;
        }
        return $out;
    }

    public function acceptForCurrentUser(int $documentId, string $ip, ?string $userAgent): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : [];

        $pdo = $this->container->get(\PDO::class);
        $docsRepo = new LegalDocumentRepository($pdo);
        $doc = $docsRepo->findById($documentId);
        if ($doc === null) {
            throw new \RuntimeException('Documento inválido.');
        }

        if ((string)($doc['status'] ?? '') !== 'active') {
            throw new \RuntimeException('Documento inativo.');
        }

        $scope = (string)($doc['scope'] ?? '');
        if ($scope === 'system_user') {
            if ((int)($doc['clinic_id'] ?? 0) !== $clinicId) {
                throw new \RuntimeException('Documento inválido.');
            }

            $targetRole = trim((string)($doc['target_role_code'] ?? ''));
            if ($targetRole !== '' && !(is_array($roleCodes) && in_array($targetRole, $roleCodes, true))) {
                throw new \RuntimeException('Documento não aplicável para seu perfil.');
            }
        } elseif ($scope === 'clinic_owner') {
            if (!in_array('owner', $roleCodes, true)) {
                throw new \RuntimeException('Documento não aplicável para seu perfil.');
            }

            $docClinicId = $doc['clinic_id'] ?? null;
            if ($docClinicId !== null && (int)$docClinicId !== $clinicId) {
                throw new \RuntimeException('Documento inválido.');
            }
        } else {
            throw new \RuntimeException('Documento inválido.');
        }

        $repo = new LegalDocumentAcceptanceRepository($pdo);
        $acceptedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        try {
            $repo->createForUser($clinicId, $documentId, $userId, $acceptedAt, $ip, $userAgent);
        } catch (\Throwable $e) {
            return;
        }
    }

    public function getClinicIdForOwnerContext(): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }
        return $clinicId;
    }
}
