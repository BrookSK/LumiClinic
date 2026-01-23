<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\ConsentAcceptanceRepository;
use App\Repositories\MedicalImageRepository;
use App\Repositories\SignatureRepository;
use App\Services\Storage\PrivateStorage;

final class PortalDocumentsService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array{
     *   acceptances:list<array<string,mixed>>,
     *   signatures:list<array<string,mixed>>,
     *   images:list<array<string,mixed>>
     * }
     */
    public function list(int $clinicId, int $patientId, string $ip, ?string $userAgent = null): array
    {
        $pdo = $this->container->get(\PDO::class);

        $accRepo = new ConsentAcceptanceRepository($pdo);
        $sigRepo = new SignatureRepository($pdo);
        $imgRepo = new MedicalImageRepository($pdo);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.documents.view', ['patient_id' => $patientId], $ip, null, 'patient', $patientId, $userAgent);

        return [
            'acceptances' => $accRepo->listByPatient($clinicId, $patientId, 100),
            'signatures' => $sigRepo->listByPatient($clinicId, $patientId, 100),
            'images' => $imgRepo->listVisibleToPatient($clinicId, $patientId, 200),
        ];
    }

    public function serveSignature(int $clinicId, int $patientId, int $signatureId, string $ip, ?string $userAgent = null): Response
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new SignatureRepository($pdo);
        $sig = $repo->findByIdForPatient($clinicId, $patientId, $signatureId);
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
        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => (string)strlen($bytes),
            'Cache-Control' => 'private, max-age=0, no-cache',
        ];

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.files.read', ['signature_id' => $signatureId, 'patient_id' => $patientId, 'storage_path' => $path], $ip, null, 'signature', $signatureId, $userAgent);

        return Response::raw($bytes, 200, $headers);
    }

    public function serveMedicalImage(int $clinicId, int $patientId, int $imageId, string $ip, ?string $userAgent = null): Response
    {
        $pdo = $this->container->get(\PDO::class);
        $repo = new MedicalImageRepository($pdo);
        $img = $repo->findByIdForPatient($clinicId, $patientId, $imageId);
        if ($img === null) {
            return Response::html('Not Found', 404);
        }

        $path = (string)$img['storage_path'];
        $full = PrivateStorage::fullPath($clinicId, $path);
        if (!is_file($full)) {
            return Response::html('Not Found', 404);
        }

        $bytes = file_get_contents($full);
        if ($bytes === false) {
            return Response::html('Not Found', 404);
        }

        $mime = (string)($img['mime_type'] ?? 'application/octet-stream');
        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => (string)strlen($bytes),
            'Cache-Control' => 'private, max-age=0, no-cache',
        ];

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.files.read', ['medical_image_id' => $imageId, 'patient_id' => $patientId, 'storage_path' => $path], $ip, null, 'medical_image', $imageId, $userAgent);

        return Response::raw($bytes, 200, $headers);
    }
}
