<?php

declare(strict_types=1);

namespace App\Services\MedicalImages;

use App\Core\Container\Container;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\MedicalImageRepository;
use App\Repositories\MedicalRecordRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;
use App\Services\Billing\PlanEntitlementsService;
use App\Services\Storage\PrivateStorage;

final class MedicalImageService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{patient:array<string,mixed>,images:list<array<string,mixed>>,professionals:list<array<string,mixed>>,records:list<array<string,mixed>>} */
    public function listForPatient(int $patientId, string $ip, ?string $userAgent = null): array
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
        $pairs = $repo->listComparisonPairsByPatient($clinicId, $patientId, 100);

        $records = (new MedicalRecordRepository($pdo))->listByPatient($clinicId, $patientId, 200);

        $profRepo = new ProfessionalRepository($pdo);
        $professionals = $profRepo->listActiveByClinic($clinicId);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'medical_images.view',
            ['patient_id' => $patientId],
            $ip,
            $roleCodes,
            'patient',
            $patientId,
            $userAgent
        );

        return ['patient' => $patient, 'images' => $images, 'professionals' => $professionals, 'pairs' => $pairs, 'records' => $records];
    }

    /**
     * @param array{kind:string,taken_at:?string,procedure_type:?string,professional_id:?int,medical_record_id:?int} $meta
     */
    public function upload(int $patientId, array $meta, array $file, string $ip, ?string $userAgent = null): int
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

        $ent = new PlanEntitlementsService($this->container);
        $limitBytes = $ent->storageLimitBytes($clinicId);
        if (is_int($limitBytes)) {
            $used = $this->sumStorageUsedBytes($clinicId);
            $nextTotal = $used + strlen($bytes);
            if ($nextTotal > $limitBytes) {
                throw new \RuntimeException('Limite de armazenamento do plano atingido.');
            }
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
            null,
            ($takenAt === '' ? null : $takenAt),
            $meta['procedure_type'],
            $relative,
            $originalName,
            $mime,
            $size,
            $actorId
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'medical_images.upload',
            ['medical_image_id' => $id, 'patient_id' => $patientId, 'kind' => $kind, 'mime' => $mime, 'size_bytes' => $size],
            $ip,
            $roleCodes,
            'medical_image',
            $id,
            $userAgent
        );

        return $id;
    }

    /**
     * @param array{taken_at:?string,procedure_type:?string,professional_id:?int,medical_record_id:?int} $meta
     */
    public function uploadPair(int $patientId, array $meta, array $beforeFile, array $afterFile, string $ip, ?string $userAgent = null): string
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

        $takenAt = $meta['taken_at'];
        if ($takenAt !== null && $takenAt !== '') {
            $takenAt = str_replace('T', ' ', $takenAt);
            if (strlen($takenAt) === 16) {
                $takenAt .= ':00';
            }
        }

        $key = bin2hex(random_bytes(12));

        $before = $this->storeUploadedImage($clinicId, $patientId, $beforeFile);
        $after = $this->storeUploadedImage($clinicId, $patientId, $afterFile);

        $repo = new MedicalImageRepository($pdo);

        $beforeId = $repo->create(
            $clinicId,
            $patientId,
            $meta['medical_record_id'],
            $meta['professional_id'],
            'before',
            $key,
            ($takenAt === '' ? null : $takenAt),
            $meta['procedure_type'],
            $before['relative'],
            $before['original_name'],
            $before['mime'],
            $before['size'],
            $actorId
        );

        $afterId = $repo->create(
            $clinicId,
            $patientId,
            $meta['medical_record_id'],
            $meta['professional_id'],
            'after',
            $key,
            ($takenAt === '' ? null : $takenAt),
            $meta['procedure_type'],
            $after['relative'],
            $after['original_name'],
            $after['mime'],
            $after['size'],
            $actorId
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'medical_images.upload_pair',
            ['patient_id' => $patientId, 'comparison_key' => $key, 'before_id' => $beforeId, 'after_id' => $afterId],
            $ip,
            $roleCodes,
            'patient',
            $patientId,
            $userAgent
        );

        return $key;
    }

    /** @return array{relative:string,original_name:?string,mime:string,size:?int} */
    private function storeUploadedImage(int $clinicId, int $patientId, array $file): array
    {
        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        $err = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK || $tmp === '' || !is_file($tmp)) {
            throw new \RuntimeException('Falha no upload.');
        }

        $bytes = file_get_contents($tmp);
        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException('Arquivo inválido.');
        }

        $ent = new PlanEntitlementsService($this->container);
        $limitBytes = $ent->storageLimitBytes($clinicId);
        if (is_int($limitBytes)) {
            $used = $this->sumStorageUsedBytes($clinicId);
            $nextTotal = $used + strlen($bytes);
            if ($nextTotal > $limitBytes) {
                throw new \RuntimeException('Limite de armazenamento do plano atingido.');
            }
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

        return ['relative' => $relative, 'original_name' => $originalName, 'mime' => $mime, 'size' => $size];
    }

    public function serveFile(int $imageId, string $ip, ?string $userAgent = null): Response
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            return Response::html('Contexto inválido.', 403);
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MedicalImageRepository($pdo);
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

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'files.read',
            ['medical_image_id' => $imageId, 'patient_id' => (int)$img['patient_id'], 'storage_path' => $path],
            $ip,
            $roleCodes,
            'medical_image',
            $imageId,
            $userAgent
        );

        return Response::raw((string)$bytes, 200, $headers);
    }

    private function sumStorageUsedBytes(int $clinicId): int
    {
        $pdo = $this->container->get(\PDO::class);

        $stmt1 = $pdo->prepare("\n            SELECT COALESCE(SUM(size_bytes),0) AS s
            FROM patient_uploads
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
        ");
        $stmt1->execute(['clinic_id' => $clinicId]);
        $r1 = $stmt1->fetch();
        $sum1 = (int)($r1['s'] ?? 0);

        $stmt2 = $pdo->prepare("\n            SELECT COALESCE(SUM(size_bytes),0) AS s
            FROM medical_images
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
        ");
        $stmt2->execute(['clinic_id' => $clinicId]);
        $r2 = $stmt2->fetch();
        $sum2 = (int)($r2['s'] ?? 0);

        return max(0, $sum1) + max(0, $sum2);
    }
}
