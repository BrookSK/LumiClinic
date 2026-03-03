<?php

declare(strict_types=1);

namespace App\Services\Legal;

use App\Core\Container\Container;
use App\Repositories\LegalDocumentRepository;
use App\Repositories\LegalDocumentSignatureRepository;
use App\Services\Auth\AuthService;
use App\Services\Portal\PatientAuthService;

final class LegalDocumentSignatureService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{doc:array<string,mixed>,version:array{version_id:int,version_number:int,hash_sha256:string}} */
    public function getDocumentForSigningAsUser(int $documentId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $doc = (new LegalDocumentRepository($pdo))->findById($documentId);
        if ($doc === null) {
            throw new \RuntimeException('Documento inválido.');
        }

        $scope = (string)($doc['scope'] ?? '');
        if ($scope === 'system_user' && (int)($doc['clinic_id'] ?? 0) !== $clinicId) {
            throw new \RuntimeException('Documento inválido.');
        }
        if ($scope === 'clinic_owner') {
            $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : [];
            if (!in_array('owner', $roleCodes, true)) {
                throw new \RuntimeException('Documento não aplicável para seu perfil.');
            }
            $docClinicId = $doc['clinic_id'] ?? null;
            if ($docClinicId !== null && (int)$docClinicId !== $clinicId) {
                throw new \RuntimeException('Documento inválido.');
            }
        }

        if ((string)($doc['status'] ?? '') !== 'active') {
            throw new \RuntimeException('Documento inativo.');
        }

        $version = (new LegalDocumentVersioningService($this->container))->ensureCurrentVersionForDocumentId($documentId);
        return ['doc' => $doc, 'version' => $version];
    }

    /** @return array{doc:array<string,mixed>,version:array{version_id:int,version_number:int,hash_sha256:string}} */
    public function getDocumentForSigningAsPatientUser(int $documentId): array
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientUserId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $doc = (new LegalDocumentRepository($pdo))->findById($documentId);
        if ($doc === null || (int)($doc['clinic_id'] ?? 0) !== $clinicId || (string)($doc['scope'] ?? '') !== 'patient_portal') {
            throw new \RuntimeException('Documento inválido.');
        }

        if ((string)($doc['status'] ?? '') !== 'active') {
            throw new \RuntimeException('Documento inativo.');
        }

        $version = (new LegalDocumentVersioningService($this->container))->ensureCurrentVersionForDocumentId($documentId);
        return ['doc' => $doc, 'version' => $version];
    }

    public function signAsUser(int $documentId, string $signatureDataUrl, string $ip, ?string $userAgent): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $signatureDataUrl = trim($signatureDataUrl);
        if (!str_starts_with($signatureDataUrl, 'data:image/png;base64,')) {
            throw new \RuntimeException('Assinatura inválida.');
        }
        if (strlen($signatureDataUrl) < 2000) {
            throw new \RuntimeException('Assinatura vazia.');
        }

        $data = $this->getDocumentForSigningAsUser($documentId);
        $versionId = (int)($data['version']['version_id'] ?? 0);
        if ($versionId <= 0) {
            throw new \RuntimeException('Versão inválida.');
        }

        $signedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $sigHash = hash('sha256', $signatureDataUrl);

        $pdo = $this->container->get(\PDO::class);
        $repo = new LegalDocumentSignatureRepository($pdo);
        try {
            $repo->createForUser($clinicId, $documentId, $versionId, $userId, 'draw', $signatureDataUrl, $sigHash, $signedAt, $ip, $userAgent);
        } catch (\Throwable $e) {
            return;
        }
    }

    public function signAsPatientUser(int $documentId, string $signatureDataUrl, string $ip, ?string $userAgent): void
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientUserId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $signatureDataUrl = trim($signatureDataUrl);
        if (!str_starts_with($signatureDataUrl, 'data:image/png;base64,')) {
            throw new \RuntimeException('Assinatura inválida.');
        }
        if (strlen($signatureDataUrl) < 2000) {
            throw new \RuntimeException('Assinatura vazia.');
        }

        $data = $this->getDocumentForSigningAsPatientUser($documentId);
        $versionId = (int)($data['version']['version_id'] ?? 0);
        if ($versionId <= 0) {
            throw new \RuntimeException('Versão inválida.');
        }

        $signedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $sigHash = hash('sha256', $signatureDataUrl);

        $pdo = $this->container->get(\PDO::class);
        $repo = new LegalDocumentSignatureRepository($pdo);
        try {
            $repo->createForPatientUser($clinicId, $documentId, $versionId, $patientUserId, 'draw', $signatureDataUrl, $sigHash, $signedAt, $ip, $userAgent);
        } catch (\Throwable $e) {
            return;
        }
    }
}
