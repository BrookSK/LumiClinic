<?php

declare(strict_types=1);

namespace App\Services\MedicalImages;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\MedicalImageRepository;
use App\Repositories\PatientUploadRepository;
use App\Services\Auth\AuthService;

final class PatientUploadModerationService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listPending(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new PatientUploadRepository($this->container->get(\PDO::class));
        return $repo->listPendingByClinic($clinicId, 200);
    }

    public function approve(int $uploadId, ?string $moderationNote, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $uploads = new PatientUploadRepository($pdo);
        $images = new MedicalImageRepository($pdo);

        try {
            $pdo->beginTransaction();

            $u = $uploads->findPendingForUpdate($clinicId, $uploadId);
            if ($u === null) {
                $pdo->commit();
                return;
            }

            $takenAt = $u['taken_at'] ?? null;
            $takenAt = $takenAt !== null ? (string)$takenAt : null;

            $medicalImageId = $images->createFromPatientUpload(
                $clinicId,
                (int)$u['patient_id'],
                (string)$u['kind'],
                $takenAt,
                null,
                (string)$u['storage_path'],
                $u['original_filename'] !== null ? (string)$u['original_filename'] : null,
                $u['mime_type'] !== null ? (string)$u['mime_type'] : null,
                $u['size_bytes'] !== null ? (int)$u['size_bytes'] : null,
                $actorId,
                $uploadId
            );

            $uploads->markApproved($clinicId, $uploadId, $actorId, $moderationNote, $medicalImageId);

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'medical_images.patient_upload_approve', [
                'patient_upload_id' => $uploadId,
                'medical_image_id' => $medicalImageId,
                'patient_id' => (int)$u['patient_id'],
            ], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function reject(int $uploadId, ?string $moderationNote, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $uploads = new PatientUploadRepository($pdo);

        try {
            $pdo->beginTransaction();

            $u = $uploads->findPendingForUpdate($clinicId, $uploadId);
            if ($u === null) {
                $pdo->commit();
                return;
            }

            $uploads->markRejected($clinicId, $uploadId, $actorId, $moderationNote);

            $audit = new AuditLogRepository($pdo);
            $audit->log($actorId, $clinicId, 'medical_images.patient_upload_reject', [
                'patient_upload_id' => $uploadId,
                'patient_id' => (int)$u['patient_id'],
            ], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
