<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientUploadRepository;
use App\Services\Storage\PrivateStorage;

final class PortalUploadService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listUploads(int $clinicId, int $patientId): array
    {
        $repo = new PatientUploadRepository($this->container->get(\PDO::class));
        return $repo->listByPatient($clinicId, $patientId, 50);
    }

    /** @param array{kind:string,taken_at:?string,note:?string} $meta */
    public function upload(int $clinicId, int $patientId, int $patientUserId, array $meta, array $file, string $ip): int
    {
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

        $note = $meta['note'];

        $ext = $allowed[$mime];
        $token = bin2hex(random_bytes(16));
        $relative = 'patient_uploads/patient_' . $patientId . '/' . date('Ymd') . '_' . $token . '.' . $ext;
        PrivateStorage::put($clinicId, $relative, $bytes);

        $originalName = isset($file['name']) ? (string)$file['name'] : null;
        $size = isset($file['size']) ? (int)$file['size'] : null;

        $repo = new PatientUploadRepository($pdo);
        $id = $repo->create(
            $clinicId,
            $patientId,
            $patientUserId,
            $kind,
            ($takenAt === '' ? null : $takenAt),
            $note,
            $relative,
            $originalName,
            $mime,
            $size
        );

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.upload.create', ['patient_upload_id' => $id, 'patient_id' => $patientId, 'kind' => $kind], $ip);

        return $id;
    }
}
