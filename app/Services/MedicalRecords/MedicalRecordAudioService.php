<?php

declare(strict_types=1);

namespace App\Services\MedicalRecords;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\MedicalRecordAudioNoteRepository;
use App\Repositories\PatientRepository;
use App\Services\Ai\AiKeyResolverService;
use App\Services\Ai\AiWalletService;
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

        // Verificar limite de transcrição
        $transcriptionStatus = $ent->transcriptionStatus($clinicId);
        if ($transcriptionStatus['blocked']) {
            $msg = !empty($transcriptionStatus['disabled'])
                ? 'Transcrição de áudio não está disponível no seu plano. Faça upgrade para acessar este recurso.'
                : 'Limite de transcrição do plano atingido. Entre em contato com o suporte para fazer upgrade.';
            throw new \RuntimeException($msg);
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
        $stored = PrivateStorage::put($clinicId, $relative, $bytes);
        if ($stored === false) {
            throw new \RuntimeException('Falha ao salvar arquivo de áudio. Verifique as permissões da pasta storage/.');
        }

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

        // Salvar duração real se fornecida pelo frontend
        $durationSeconds = isset($meta['duration_seconds']) && $meta['duration_seconds'] !== null ? (int)$meta['duration_seconds'] : null;
        if ($durationSeconds !== null && $durationSeconds > 0) {
            try {
                $pdo->prepare("UPDATE medical_record_audio_notes SET duration_seconds = :d WHERE id = :id AND clinic_id = :c LIMIT 1")
                    ->execute(['d' => $durationSeconds, 'id' => $audioId, 'c' => $clinicId]);
            } catch (\Throwable $e) {
                // Coluna pode não existir ainda (migration pendente)
            }
        }

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

        // Resolve which OpenAI key to use (clinic > superadmin > wallet)
        $resolved = (new AiKeyResolverService($this->container))->resolve($clinicId);

        try {
            $fullPath = PrivateStorage::fullPath($clinicId, $relative);
            $text = $this->transcribeWithChunking($fullPath, $originalName ?? ('audio.' . $ext), $ext, $resolved['key']);
            $repo->setTranscript($clinicId, $audioId, 'transcribed', $text);
        } catch (\Throwable $e) {
            $repo->setTranscript($clinicId, $audioId, 'transcription_failed', null);
            throw $e;
        }

        // Debit wallet if transcription used the wallet key
        if ($resolved['wallet_mode'] === true) {
            try {
                (new AiWalletService($this->container))->debitForTranscription(
                    $clinicId,
                    $audioId,
                    $durationSeconds ?? 0,
                    $size ?? 0
                );
            } catch (\Throwable $e) {
                error_log('[AiWallet] Debit failed for audio_note_id=' . $audioId . ': ' . $e->getMessage());
                // Debit failure does not block returning the transcription result
            }
        }

        $row = $repo->findById($clinicId, $audioId);
        $text = is_array($row) ? trim((string)($row['transcript_text'] ?? '')) : '';

        return [
            'audio_note_id' => $audioId,
            'transcript_text' => $text,
        ];
    }

    /**
     * Transcreve áudio, dividindo em chunks de ~10 min se o arquivo for > 24MB.
     * Usa ffmpeg para dividir e concatena os textos transcritos.
     */
    private function transcribeWithChunking(string $fullPath, string $filename, string $ext, ?string $apiKey = null): string
    {
        $maxBytes = 24 * 1024 * 1024; // 24MB (Whisper limit is 25MB)
        $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;

        $client = $apiKey !== null
            ? OpenAiClient::withKey($this->container, $apiKey)
            : new OpenAiClient($this->container);

        // Arquivo pequeno: transcreve direto
        if ($fileSize <= $maxBytes) {
            $result = $client->audioTranscription($fullPath, $filename);
            return trim((string)($result['text'] ?? ''));
        }

        // Arquivo grande: dividir com ffmpeg
        $ffmpeg = $this->findFfmpeg();
        if ($ffmpeg === null) {
            // Sem ffmpeg: tenta enviar direto (pode falhar se > 25MB)
            $result = $client->audioTranscription($fullPath, $filename);
            return trim((string)($result['text'] ?? ''));
        }

        $tmpDir = sys_get_temp_dir() . '/lc_audio_chunks_' . bin2hex(random_bytes(8));
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        try {
            // Dividir em chunks de 10 minutos
            $chunkPattern = $tmpDir . '/chunk_%03d.' . $ext;
            $cmd = sprintf(
                '%s -i %s -f segment -segment_time 600 -c copy -reset_timestamps 1 %s 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($fullPath),
                escapeshellarg($chunkPattern)
            );
            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                // ffmpeg falhou: tenta enviar direto
                $result = $client->audioTranscription($fullPath, $filename);
                return trim((string)($result['text'] ?? ''));
            }

            // Listar chunks gerados
            $chunks = glob($tmpDir . '/chunk_*.' . $ext);
            if ($chunks === false || $chunks === []) {
                $result = $client->audioTranscription($fullPath, $filename);
                return trim((string)($result['text'] ?? ''));
            }

            sort($chunks);

            // Transcrever cada chunk
            $texts = [];
            foreach ($chunks as $i => $chunkPath) {
                $chunkFilename = 'chunk_' . $i . '.' . $ext;
                $result = $client->audioTranscription($chunkPath, $chunkFilename);
                $chunkText = trim((string)($result['text'] ?? ''));
                if ($chunkText !== '') {
                    $texts[] = $chunkText;
                }
            }

            return implode("\n\n", $texts);
        } finally {
            // Limpar chunks temporários
            $files = glob($tmpDir . '/*');
            if (is_array($files)) {
                foreach ($files as $f) {
                    if (is_file($f)) @unlink($f);
                }
            }
            @rmdir($tmpDir);
        }
    }

    private function findFfmpeg(): ?string
    {
        // Tentar caminhos comuns
        $paths = ['ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'];
        foreach ($paths as $p) {
            $out = [];
            $code = 0;
            @exec(escapeshellarg($p) . ' -version 2>&1', $out, $code);
            if ($code === 0) {
                return $p;
            }
        }
        return null;
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
