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
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inv?lido.');
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

    /** @return array{patient:array<string,mixed>,items:list<array{procedure_type:?string,session_number:?int,images:list<array<string,mixed>>}>} */
    public function timelineForPatient(int $patientId, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            throw new \RuntimeException('Paciente inv?lido.');
        }

        $repo = new MedicalImageRepository($pdo);
        $rows = $repo->listByPatientForTimeline($clinicId, $patientId, 500);

        $items = [];
        $map = [];
        foreach ($rows as $r) {
            $proc = isset($r['procedure_type']) ? (string)$r['procedure_type'] : '';
            $procKey = $proc;
            $sess = $r['session_number'] ?? null;
            $sessKey = $sess === null ? 'null' : (string)(int)$sess;
            $key = $procKey . '|' . $sessKey;

            if (!isset($map[$key])) {
                $map[$key] = count($items);
                $items[] = [
                    'procedure_type' => ($proc === '' ? null : $proc),
                    'session_number' => (is_numeric($sess) ? (int)$sess : null),
                    'images' => [],
                ];
            }

            $idx = (int)$map[$key];
            $items[$idx]['images'][] = $r;
        }

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log(
            $actorId,
            $clinicId,
            'medical_images.timeline.view',
            ['patient_id' => $patientId],
            $ip,
            $roleCodes,
            'patient',
            $patientId,
            $userAgent
        );

        return ['patient' => $patient, 'items' => $items];
    }

    /** @return array<string,mixed>|null */
    public function getImage(int $imageId, string $ip, ?string $userAgent = null): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            return null;
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MedicalImageRepository($pdo);
        $img = $repo->findById($clinicId, $imageId);
        if ($img === null) {
            return null;
        }

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'medical_images.read', ['medical_image_id' => $imageId, 'patient_id' => (int)($img['patient_id'] ?? 0)], $ip, $roleCodes, 'medical_image', $imageId, $userAgent);

        return $img;
    }

    /**
     * @param array{kind:string,taken_at:?string,procedure_type:?string,session_number:?int,pose:?string,professional_id:?int,medical_record_id:?int} $meta
     */
    public function upload(int $patientId, array $meta, array $file, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $patients = new PatientRepository($pdo);
        if ($patients->findById($clinicId, $patientId) === null) {
            throw new \RuntimeException('Paciente inv?lido.');
        }

        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        $err = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;
        if ($err !== UPLOAD_ERR_OK || $tmp === '' || !is_file($tmp)) {
            throw new \RuntimeException('Falha no upload.');
        }

        $bytes = file_get_contents($tmp);
        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException('Arquivo inv?lido.');
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

        // Compress: convert to WebP, resize if > 2048px (saves ~50% storage)
        $compressed = $this->compressImage($bytes, $mime);
        if ($compressed !== null) {
            $bytes = $compressed;
            $ext = 'webp';
        } else {
            $ext = $allowed[$mime];
        }

        $token = bin2hex(random_bytes(16));
        $relative = 'medical_images/patient_' . $patientId . '/' . date('Ymd') . '_' . $token . '.' . $ext;

        PrivateStorage::put($clinicId, $relative, $bytes);

        $originalName = isset($file['name']) ? (string)$file['name'] : null;
        $size = isset($file['size']) ? (int)$file['size'] : null;

        $kind = $meta['kind'];
        if (!in_array($kind, ['photo', 'exam', 'progress', 'other'], true)) {
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
            $meta['session_number'],
            $meta['pose'],
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
     * @param array{taken_at:?string,procedure_type:?string,session_number:?int,pose:?string,professional_id:?int,medical_record_id:?int} $meta
     */
    public function uploadPair(int $patientId, array $meta, array $beforeFile, array $afterFile, string $ip, ?string $userAgent = null): string
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inv?lido.');
        }

        $pdo = $this->container->get(\PDO::class);

        $patients = new PatientRepository($pdo);
        if ($patients->findById($clinicId, $patientId) === null) {
            throw new \RuntimeException('Paciente inv?lido.');
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
            $meta['session_number'],
            $meta['pose'],
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
            $meta['session_number'],
            $meta['pose'],
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
            throw new \RuntimeException('Arquivo inv?lido.');
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

        // Compress: convert to WebP, resize if > 2048px
        $compressed = $this->compressImage($bytes, $mime);
        if ($compressed !== null) {
            $bytes = $compressed;
            $ext = 'webp';
            $mime = 'image/webp';
        } else {
            $ext = $allowed[$mime];
        }

        $token = bin2hex(random_bytes(16));
        $relative = 'medical_images/patient_' . $patientId . '/' . date('Ymd') . '_' . $token . '.' . $ext;

        PrivateStorage::put($clinicId, $relative, $bytes);

        $originalName = isset($file['name']) ? (string)$file['name'] : null;
        $size = strlen($bytes);

        return ['relative' => $relative, 'original_name' => $originalName, 'mime' => $mime, 'size' => $size];
    }

    public function serveFile(int $imageId, string $ip, ?string $userAgent = null): Response
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();

        if ($clinicId === null || $actorId === null) {
            return Response::html('Contexto inv?lido.', 403);
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

    /**
     * Compress image: convert to WebP and resize if larger than max dimension.
     * Returns compressed bytes or null if compression not available/beneficial.
     */
    private function compressImage(string $bytes, string $mime, int $maxDimension = 2048, int $webpQuality = 82): ?string
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            return null;
        }

        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        // Resize if larger than max dimension
        $needsResize = ($w > $maxDimension || $h > $maxDimension);
        if ($needsResize) {
            if ($w >= $h) {
                $newW = $maxDimension;
                $newH = (int)round($h * ($maxDimension / $w));
            } else {
                $newH = $maxDimension;
                $newW = (int)round($w * ($maxDimension / $h));
            }

            $dst = imagecreatetruecolor($newW, $newH);
            if ($dst === false) {
                imagedestroy($src);
                return null;
            }

            // Preserve transparency for PNG
            imagealphablending($dst, false);
            imagesavealpha($dst, true);

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        // Convert to WebP
        ob_start();
        imagewebp($src, null, $webpQuality);
        $compressed = ob_get_clean();
        imagedestroy($src);

        if ($compressed === false || $compressed === '') {
            return null;
        }

        // Only use compressed if it's actually smaller
        if (strlen($compressed) >= strlen($bytes)) {
            return null;
        }

        return $compressed;
    }

    /**
     * Crop an existing medical image using GD.
     * Replaces the file in-place and returns the patient_id.
     */
    public function cropImage(int $imageId, int $x, int $y, int $w, int $h, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MedicalImageRepository($pdo);
        $img = $repo->findById($clinicId, $imageId);
        if ($img === null) {
            throw new \RuntimeException('Imagem não encontrada.');
        }

        $storagePath = (string)$img['storage_path'];
        $fullPath = PrivateStorage::fullPath($clinicId, $storagePath);
        if (!is_file($fullPath)) {
            throw new \RuntimeException('Arquivo não encontrado no disco.');
        }

        $bytes = file_get_contents($fullPath);
        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException('Falha ao ler arquivo.');
        }

        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            throw new \RuntimeException('Formato de imagem não suportado pelo GD.');
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // Clamp crop area to image bounds
        $x = max(0, min($x, $srcW - 1));
        $y = max(0, min($y, $srcH - 1));
        $w = max(1, min($w, $srcW - $x));
        $h = max(1, min($h, $srcH - $y));

        $dst = imagecreatetruecolor($w, $h);
        if ($dst === false) {
            imagedestroy($src);
            throw new \RuntimeException('Falha ao criar imagem recortada.');
        }

        imagecopy($dst, $src, 0, 0, $x, $y, $w, $h);
        imagedestroy($src);

        // Determine output format from extension
        $ext = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
        ob_start();
        if ($ext === 'png') {
            imagepng($dst, null, 9);
        } elseif ($ext === 'webp' && function_exists('imagewebp')) {
            imagewebp($dst, null, 90);
        } else {
            imagejpeg($dst, null, 92);
        }
        $croppedBytes = ob_get_clean();
        imagedestroy($dst);

        if ($croppedBytes === false || $croppedBytes === '') {
            throw new \RuntimeException('Falha ao gerar imagem recortada.');
        }

        // Overwrite the file
        PrivateStorage::put($clinicId, $storagePath, $croppedBytes);

        // Update file size in DB
        $pdo->prepare('UPDATE medical_images SET file_size_bytes = ?, updated_at = NOW() WHERE id = ? AND clinic_id = ?')
            ->execute([strlen($croppedBytes), $imageId, $clinicId]);

        // Audit
        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'medical_images.crop', ['image_id' => $imageId, 'crop' => ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h]], $ip, $roleCodes, 'medical_image', $imageId, $userAgent);

        return (int)$img['patient_id'];
    }

    /**
     * Crop and save as a NEW image (copy). Returns the new image ID.
     */
    public function cropImageAsCopy(int $imageId, int $x, int $y, int $w, int $h, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MedicalImageRepository($pdo);
        $img = $repo->findById($clinicId, $imageId);
        if ($img === null) {
            throw new \RuntimeException('Imagem não encontrada.');
        }

        $storagePath = (string)$img['storage_path'];
        $fullPath = PrivateStorage::fullPath($clinicId, $storagePath);
        if (!is_file($fullPath)) {
            throw new \RuntimeException('Arquivo não encontrado no disco.');
        }

        $bytes = file_get_contents($fullPath);
        if ($bytes === false || $bytes === '') {
            throw new \RuntimeException('Falha ao ler arquivo.');
        }

        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            throw new \RuntimeException('Formato não suportado pelo GD.');
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $x = max(0, min($x, $srcW - 1));
        $y = max(0, min($y, $srcH - 1));
        $w = max(1, min($w, $srcW - $x));
        $h = max(1, min($h, $srcH - $y));

        $dst = imagecreatetruecolor($w, $h);
        if ($dst === false) { imagedestroy($src); throw new \RuntimeException('Falha ao criar imagem.'); }
        imagecopy($dst, $src, 0, 0, $x, $y, $w, $h);
        imagedestroy($src);

        $ext = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
        ob_start();
        if ($ext === 'png') imagepng($dst, null, 9);
        elseif ($ext === 'webp' && function_exists('imagewebp')) imagewebp($dst, null, 90);
        else imagejpeg($dst, null, 92);
        $croppedBytes = ob_get_clean();
        imagedestroy($dst);

        if ($croppedBytes === false || $croppedBytes === '') {
            throw new \RuntimeException('Falha ao gerar imagem recortada.');
        }

        // Save as new file
        $patientId = (int)$img['patient_id'];
        $token = bin2hex(random_bytes(16));
        $newRelative = 'medical_images/patient_' . $patientId . '/' . date('Ymd') . '_crop_' . $token . '.' . $ext;
        PrivateStorage::put($clinicId, $newRelative, $croppedBytes);

        // Create new DB record copying metadata from original
        $mimeMap = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $newId = $repo->create(
            $clinicId,
            $patientId,
            isset($img['medical_record_id']) ? ($img['medical_record_id'] !== null ? (int)$img['medical_record_id'] : null) : null,
            isset($img['professional_id']) ? ($img['professional_id'] !== null ? (int)$img['professional_id'] : null) : null,
            (string)($img['kind'] ?? 'photo'),
            null, // comparison_key
            isset($img['taken_at']) && $img['taken_at'] !== null ? (string)$img['taken_at'] : null,
            isset($img['procedure_type']) && $img['procedure_type'] !== null ? (string)$img['procedure_type'] : null,
            isset($img['session_number']) && $img['session_number'] !== null ? (int)$img['session_number'] : null,
            isset($img['pose']) && $img['pose'] !== null ? (string)$img['pose'] : null,
            $newRelative,
            'Recorte de #' . $imageId,
            $mimeMap[$ext] ?? 'image/jpeg',
            strlen($croppedBytes),
            $actorId
        );

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'medical_images.crop_copy', ['source_id' => $imageId, 'new_id' => $newId, 'crop' => ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h]], $ip, $roleCodes, 'medical_image', $newId, $userAgent);

        return $newId;
    }
}
