<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientUploadRepository;
use App\Services\Storage\PrivateStorage;
use App\Services\Billing\PlanEntitlementsService;

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
