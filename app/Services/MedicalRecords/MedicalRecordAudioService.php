<?php

declare(strict_types=1);

namespace App\Services\MedicalRecords;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\MedicalRecordAudioNoteRepository;
use App\Repositories\PatientRepository;
use App\Services\Ai\OpenAiClient;
use App\Services\Auth\AuthService;
use App\Services\Billing\PlanEntitlementsService;
use App\Services\Storage\PrivateStorage;

final class MedicalRecordAudioService
{
    public function __construct(private readonly Container $container) {}

    /** @param array{patient_id:int,medical_record_id?:?int,appointment_id?:?int,professional_id?:?int} $meta */
    public function uploadAndTranscribe(array $meta, array $file, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $patientId = (int)($meta['patient_id'] ?? 0);
        if ($patientId <= 0) {
            throw new \RuntimeException('Paciente é obrigatório.');
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
        $mimeDetected = (string)$finfo->file($tmp);
        $mimeClient = isset($file['type']) ? trim((string)$file['type']) : '';
        $originalName = isset($file['name']) ? (string)$file['name'] : null;

        $allowed = [
            'audio/webm' => 'webm',
            'video/webm' => 'webm',
            'audio/ogg' => 'ogg',
            'application/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'video/mp4' => 'm4a',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
        ];

        $mime = $mimeDetected;
        if (!isset($allowed[$mime]) && $mimeClient !== '' && isset($allowed[$mimeClient])) {
            $mime = $mimeClient;
        }

        if (!isset($allowed[$mime])) {
            $extFromName = '';
            if (is_string($originalName) && $originalName !== '' && str_contains($originalName, '.')) {
                $extFromName = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
            }

            if (in_array($extFromName, ['webm', 'ogg', 'mp3', 'm4a', 'mp4', 'wav'], true)) {
                if ($extFromName === 'mp4') {
                    $extFromName = 'm4a';
                }
                if ($extFromName === 'wav') {
                    $mime = 'audio/wav';
                } elseif ($extFromName === 'mp3') {
                    $mime = 'audio/mpeg';
                } elseif ($extFromName === 'm4a') {
                    $mime = 'audio/mp4';
                } elseif ($extFromName === 'ogg') {
                    $mime = 'audio/ogg';
                } else {
                    $mime = 'audio/webm';
                }
            }
        }

        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('Formato de áudio não suportado.');
        }

        $ext = $allowed[$mime];
        $token = bin2hex(random_bytes(16));
        $relative = 'medical_record_audio/patient_' . $patientId . '/' . date('Ymd') . '_' . $token . '.' . $ext;
        PrivateStorage::put($clinicId, $relative, $bytes);

        $size = isset($file['size']) ? (int)$file['size'] : null;

        $repo = new MedicalRecordAudioNoteRepository($pdo);
        $audioId = $repo->create(
            $clinicId,
            $patientId,
            array_key_exists('medical_record_id', $meta) ? ($meta['medical_record_id'] !== null ? (int)$meta['medical_record_id'] : null) : null,
            array_key_exists('appointment_id', $meta) ? ($meta['appointment_id'] !== null ? (int)$meta['appointment_id'] : null) : null,
            array_key_exists('professional_id', $meta) ? ($meta['professional_id'] !== null ? (int)$meta['professional_id'] : null) : null,
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
            'medical_records.audio_upload',
            ['audio_note_id' => $audioId, 'patient_id' => $patientId, 'mime' => $mime, 'size_bytes' => $size],
            $ip,
            $roleCodes,
            'medical_record_audio_note',
            $audioId,
            $userAgent
        );

        try {
            $fullPath = PrivateStorage::fullPath($clinicId, $relative);
            $result = (new OpenAiClient($this->container))->audioTranscription($fullPath, $originalName ?? ('audio.' . $ext));
            $text = trim((string)($result['text'] ?? ''));
            $repo->setTranscript($clinicId, $audioId, 'transcribed', $text);
        } catch (\Throwable $e) {
            $repo->setTranscript($clinicId, $audioId, 'transcription_failed', null);
            throw $e;
        }

        $row = $repo->findById($clinicId, $audioId);
        $text = is_array($row) ? trim((string)($row['transcript_text'] ?? '')) : '';

        return [
            'audio_note_id' => $audioId,
            'transcript_text' => $text,
        ];
    }

    private function sumStorageUsedBytes(int $clinicId): int
    {
        $pdo = $this->container->get(\PDO::class);

        $sum = 0;
        $tables = [
            ['patient_uploads', 'size_bytes'],
            ['medical_images', 'size_bytes'],
            ['consultation_attachments', 'size_bytes'],
            ['medical_record_audio_notes', 'size_bytes'],
        ];

        foreach ($tables as [$t, $col]) {
            $stmt = $pdo->prepare("\n                SELECT COALESCE(SUM({$col}),0) AS s\n                FROM {$t}\n                WHERE clinic_id = :clinic_id\n                  AND deleted_at IS NULL\n            ");
            $stmt->execute(['clinic_id' => $clinicId]);
            $r = $stmt->fetch();
            $sum += (int)($r['s'] ?? 0);
        }

        return max(0, $sum);
    }
}
