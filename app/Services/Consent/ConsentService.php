<?php

declare(strict_types=1);

namespace App\Services\Consent;

use App\Core\Container\Container;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\ConsentAcceptanceRepository;
use App\Repositories\ConsentTermRepository;
use App\Repositories\PatientRepository;
use App\Repositories\SignatureRepository;
use App\Services\Auth\AuthService;
use App\Services\Storage\PrivateStorage;

final class ConsentService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string, mixed>> */
    public function listTerms(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ConsentTermRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId);
    }

    /** @return array<string, mixed>|null */
    public function getTerm(int $termId): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ConsentTermRepository($this->container->get(\PDO::class));
        return $repo->findById($clinicId, $termId);
    }

    public function createTerm(string $procedureType, string $title, string $body, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ConsentTermRepository($pdo);
        $id = $repo->create($clinicId, $procedureType, $title, $body);

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'consent_terms.create', ['term_id' => $id], $ip);

        return $id;
    }

    public function updateTerm(int $termId, string $procedureType, string $title, string $body, string $status, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ConsentTermRepository($pdo);
        $repo->update($clinicId, $termId, $procedureType, $title, $body, $status);

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'consent_terms.update', ['term_id' => $termId], $ip);
    }

    /** @return array{patient:array<string,mixed>,terms:list<array<string,mixed>>,acceptances:list<array<string,mixed>>,signatures:list<array<string,mixed>>} */
    public function listForPatient(int $patientId, string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $termsRepo = new ConsentTermRepository($pdo);
        $accRepo = new ConsentAcceptanceRepository($pdo);
        $sigRepo = new SignatureRepository($pdo);

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'consent_terms.view', ['patient_id' => $patientId], $ip);

        return [
            'patient' => $patient,
            'terms' => $termsRepo->listActiveByClinic($clinicId),
            'acceptances' => $accRepo->listByPatient($clinicId, $patientId, 100),
            'signatures' => $sigRepo->listByPatient($clinicId, $patientId, 100),
        ];
    }

    /** @return array{patient:array<string,mixed>,term:array<string,mixed>} */
    public function getAcceptForm(int $patientId, int $termId, string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $terms = new ConsentTermRepository($pdo);
        $term = $terms->findById($clinicId, $termId);
        if ($term === null || (string)$term['status'] !== 'active') {
            throw new \RuntimeException('Termo inválido.');
        }

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'consent_terms.view', [
            'patient_id' => $patientId,
            'term_id' => $termId,
            'context' => 'accept',
        ], $ip);

        return ['patient' => $patient, 'term' => $term];
    }

    public function accept(int $patientId, int $termId, string $signatureDataUrl, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $patients = new PatientRepository($pdo);
        if ($patients->findById($clinicId, $patientId) === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $terms = new ConsentTermRepository($pdo);
        $term = $terms->findById($clinicId, $termId);
        if ($term === null || (string)$term['status'] !== 'active') {
            throw new \RuntimeException('Termo inválido.');
        }

        $png = $this->decodePngDataUrl($signatureDataUrl);

        try {
            $pdo->beginTransaction();

            $accRepo = new ConsentAcceptanceRepository($pdo);
            $acceptedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            $acceptanceId = $accRepo->create(
                $clinicId,
                $termId,
                $patientId,
                (string)$term['procedure_type'],
                $actorId,
                $ip,
                $acceptedAt
            );

            $token = bin2hex(random_bytes(16));
            $relative = 'signatures/patient_' . $patientId . '/consent_' . $acceptanceId . '_' . $token . '.png';
            PrivateStorage::put($clinicId, $relative, $png);

            $sigRepo = new SignatureRepository($pdo);
            $signatureId = $sigRepo->create(
                $clinicId,
                $patientId,
                $acceptanceId,
                null,
                $relative,
                'image/png',
                $actorId,
                $ip
            );

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'consent_terms.accept', [
                'term_id' => $termId,
                'patient_id' => $patientId,
                'acceptance_id' => $acceptanceId,
                'signature_id' => $signatureId,
            ], $ip);

            $pdo->commit();
            return $acceptanceId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function serveSignature(int $signatureId, string $ip, ?string $userAgent = null): Response
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new SignatureRepository($this->container->get(\PDO::class));
        $sig = $repo->findById($clinicId, $signatureId);
        if ($sig === null) {
            return Response::html('Not Found', 404);
        }

        $path = (string)$sig['storage_path'];
        $full = PrivateStorage::fullPath($clinicId, $path);
        if (!is_file($full)) {
            return Response::html('Not Found', 404);
        }

        $bytes = file_get_contents($full);
        if ($bytes === false) {
            return Response::html('Not Found', 404);
        }

        $mime = (string)($sig['mime_type'] ?? 'application/octet-stream');

        $pdo = $this->container->get(\PDO::class);
        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'files.read',
            ['signature_id' => $signatureId, 'patient_id' => (int)$sig['patient_id'], 'storage_path' => $path],
            $ip,
            $roleCodes,
            'signature',
            $signatureId,
            $userAgent
        );

        return Response::raw($bytes, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string)strlen($bytes),
            'Cache-Control' => 'private, max-age=0, no-cache',
        ]);
    }

    private function decodePngDataUrl(string $dataUrl): string
    {
        $dataUrl = trim($dataUrl);
        if ($dataUrl === '' || !str_starts_with($dataUrl, 'data:image/png;base64,')) {
            throw new \RuntimeException('Assinatura inválida.');
        }

        $b64 = substr($dataUrl, strlen('data:image/png;base64,'));
        $bin = base64_decode($b64, true);
        if ($bin === false || $bin === '') {
            throw new \RuntimeException('Assinatura inválida.');
        }

        if (substr($bin, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new \RuntimeException('Assinatura inválida.');
        }

        return $bin;
    }
}
