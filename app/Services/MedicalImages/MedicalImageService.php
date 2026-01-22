<?php

declare(strict_types=1);

namespace App\Services\MedicalImages;

use App\Core\Container\Container;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\MedicalImageRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;
use App\Services\Storage\PrivateStorage;

final class MedicalImageService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{patient:array<string,mixed>,images:list<array<string,mixed>>,professionals:list<array<string,mixed>>} */
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

        $repo = new MedicalImageRepository($pdo);
        $images = $repo->listByPatient($clinicId, $patientId, 200);

        $profRepo = new ProfessionalRepository($pdo);
        $professionals = $profRepo->listActiveByClinic($clinicId);

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'medical_images.view', [
            'patient_id' => $patientId,
        ], $ip);

        return ['patient' => $patient, 'images' => $images, 'professionals' => $professionals];
    }

    /**
     * @param array{kind:string,taken_at:?string,procedure_type:?string,professional_id:?int,medical_record_id:?int} $meta
     */
    public function upload(int $patientId, array $meta, array $file, string $ip): int
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

        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        $err = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK || $tmp === '' || !is_file($tmp)) {
            throw new \RuntimeException('Falha no upload.');
        }

        $bytes = file_get_contents($tmp);
        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException('Arquivo inválido.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('Formato não suportado. Use JPG/PNG/WEBP.');
        }

        $ext = $allowed[$mime];
        $token = bin2hex(random_bytes(16));
        $relative = 'medical_images/patient_' . $patientId . '/' . date('Ymd') . '_' . $token . '.' . $ext;

        PrivateStorage::put($clinicId, $relative, $bytes);

        $originalName = isset($file['name']) ? (string)$file['name'] : null;
        $size = isset($file['size']) ? (int)$file['size'] : null;

        $kind = $meta['kind'];
        if (!in_array($kind, ['before', 'after', 'other'], true)) {
            $kind = 'other';
        }

        $takenAt = $meta['taken_at'];
        if ($takenAt !== null && $takenAt !== '') {
            $takenAt = str_replace('T', ' ', $takenAt);
            if (strlen($takenAt) === 16) {
                $takenAt .= ':00';
            }
        }

        $repo = new MedicalImageRepository($pdo);
        $id = $repo->create(
            $clinicId,
            $patientId,
            $meta['medical_record_id'],
            $meta['professional_id'],
            $kind,
            ($takenAt === '' ? null : $takenAt),
            $meta['procedure_type'],
            $relative,
            $originalName,
            $mime,
            $size,
            $actorId
        );

        $audit = new AuditLogRepository($pdo);
        $audit->log($actorId, $clinicId, 'medical_images.upload', [
            'medical_image_id' => $id,
            'patient_id' => $patientId,
            'kind' => $kind,
            'mime' => $mime,
            'size_bytes' => $size,
        ], $ip);

        return $id;
    }

    public function serveFile(int $imageId, string $ip): Response
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new MedicalImageRepository($this->container->get(\PDO::class));
        $img = $repo->findById($clinicId, $imageId);
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

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($actorId, $clinicId, 'files.read', [
            'medical_image_id' => $imageId,
            'patient_id' => (int)$img['patient_id'],
            'storage_path' => $path,
        ], $ip);

        return Response::raw($bytes, 200, $headers);
    }
}
