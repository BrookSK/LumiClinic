<?php

declare(strict_types=1);

namespace App\Services\Patients;

use App\Core\Container\Container;
use App\Core\Http\Response;
use App\Repositories\AppointmentRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\ConsultationAttachmentRepository;
use App\Repositories\ConsultationRepository;
use App\Services\Auth\AuthService;
use App\Services\Billing\PlanEntitlementsService;
use App\Services\Storage\PrivateStorage;

final class ConsultationService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{appointment:array<string,mixed>,consultation:?array<string,mixed>,attachments:list<array<string,mixed>>} */
    public function getByAppointment(int $appointmentId, string $ip, ?string $userAgent = null): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $apptRepo = new AppointmentRepository($pdo);
        $appt = $apptRepo->findById($clinicId, $appointmentId);
        if ($appt === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $consultRepo = new ConsultationRepository($pdo);
        $consult = $consultRepo->findByAppointmentId($clinicId, $appointmentId);

        $attachments = [];
        if ($consult !== null) {
            $attRepo = new ConsultationAttachmentRepository($pdo);
            $attachments = $attRepo->listByConsultation($clinicId, (int)$consult['id'], 200);
        }

        return ['appointment' => $appt, 'consultation' => $consult, 'attachments' => $attachments];
    }

    public function upsert(int $appointmentId, string $executedAtInput, int $professionalId, ?string $notes, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($professionalId <= 0) {
            throw new \RuntimeException('Profissional é obrigatório.');
        }

        $executedAt = trim($executedAtInput);
        if ($executedAt === '') {
            throw new \RuntimeException('Data/hora é obrigatória.');
        }
        $executedAt = str_replace('T', ' ', $executedAt);
        if (strlen($executedAt) === 16) {
            $executedAt .= ':00';
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $executedAt);
        if ($dt === false) {
            throw new \RuntimeException('Data/hora inválida.');
        }

        $pdo = $this->container->get(\PDO::class);
        $apptRepo = new AppointmentRepository($pdo);
        $appt = $apptRepo->findById($clinicId, $appointmentId);
        if ($appt === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $patientId = isset($appt['patient_id']) && $appt['patient_id'] !== null ? (int)$appt['patient_id'] : 0;
        if ($patientId <= 0) {
            throw new \RuntimeException('Paciente é obrigatório.');
        }

        $consultRepo = new ConsultationRepository($pdo);
        $existing = $consultRepo->findByAppointmentId($clinicId, $appointmentId);

        if ($existing === null) {
            $id = $consultRepo->create($clinicId, $appointmentId, $patientId, $professionalId, $dt->format('Y-m-d H:i:s'), $notes, $actorId);
        } else {
            $id = (int)$existing['id'];
            $consultRepo->update($clinicId, $id, $dt->format('Y-m-d H:i:s'), $notes, $professionalId);
        }

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.consultations.upsert', ['consultation_id' => $id, 'appointment_id' => $appointmentId, 'patient_id' => $patientId], $ip, $roleCodes, 'consultation', $id, $userAgent);

        return $id;
    }

    public function uploadAttachment(int $appointmentId, array $file, ?string $note, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $apptRepo = new AppointmentRepository($pdo);
        $appt = $apptRepo->findById($clinicId, $appointmentId);
        if ($appt === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $patientId = isset($appt['patient_id']) && $appt['patient_id'] !== null ? (int)$appt['patient_id'] : 0;
        if ($patientId <= 0) {
            throw new \RuntimeException('Paciente é obrigatório.');
        }

        $consultRepo = new ConsultationRepository($pdo);
        $consult = $consultRepo->findByAppointmentId($clinicId, $appointmentId);
        if ($consult === null) {
            throw new \RuntimeException('Registre a execução antes de anexar arquivos.');
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
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('Formato não suportado. Use JPG/PNG/WEBP/PDF.');
        }

        $ext = $allowed[$mime];
        $token = bin2hex(random_bytes(16));
        $consultationId = (int)$consult['id'];
        $relative = 'consultation_attachments/consultation_' . $consultationId . '/' . date('Ymd') . '_' . $token . '.' . $ext;
        PrivateStorage::put($clinicId, $relative, $bytes);

        $originalName = isset($file['name']) ? (string)$file['name'] : null;
        $size = isset($file['size']) ? (int)$file['size'] : null;

        $repo = new ConsultationAttachmentRepository($pdo);
        $id = $repo->create($clinicId, $consultationId, $patientId, $note, $relative, $originalName, $mime, $size, $actorId);

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'patients.consultations.attachment.create', ['consultation_attachment_id' => $id, 'consultation_id' => $consultationId, 'patient_id' => $patientId], $ip, $roleCodes, 'consultation_attachment', $id, $userAgent);

        return $id;
    }

    public function serveAttachmentFile(int $attachmentId, string $ip, ?string $userAgent = null): Response
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            return Response::html('Contexto inválido.', 403);
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ConsultationAttachmentRepository($pdo);
        $att = $repo->findById($clinicId, $attachmentId);
        if ($att === null) {
            return Response::html('Not Found', 404);
        }

        $path = (string)$att['storage_path'];
        $full = PrivateStorage::fullPath($clinicId, $path);
        if (!is_file($full)) {
            return Response::html('Not Found', 404);
        }

        $bytes = file_get_contents($full);
        if ($bytes === false) {
            return Response::html('Not Found', 404);
        }

        $mime = (string)($att['mime_type'] ?? 'application/octet-stream');
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
            ['consultation_attachment_id' => $attachmentId, 'patient_id' => (int)$att['patient_id'], 'storage_path' => $path],
            $ip,
            $roleCodes,
            'consultation_attachment',
            $attachmentId,
            $userAgent
        );

        return Response::raw((string)$bytes, 200, $headers);
    }

    private function sumStorageUsedBytes(int $clinicId): int
    {
        $pdo = $this->container->get(\PDO::class);

        $stmt1 = $pdo->prepare("
            SELECT COALESCE(SUM(size_bytes),0) AS s
            FROM consultation_attachments
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
        ");
        $stmt1->execute(['clinic_id' => $clinicId]);
        $r1 = $stmt1->fetch();
        $sum1 = (int)($r1['s'] ?? 0);

        $stmt2 = $pdo->prepare("
            SELECT COALESCE(SUM(size_bytes),0) AS s
            FROM patient_uploads
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
        ");
        $stmt2->execute(['clinic_id' => $clinicId]);
        $r2 = $stmt2->fetch();
        $sum2 = (int)($r2['s'] ?? 0);

        $stmt3 = $pdo->prepare("
            SELECT COALESCE(SUM(size_bytes),0) AS s
            FROM medical_images
            WHERE clinic_id = :clinic_id
              AND deleted_at IS NULL
        ");
        $stmt3->execute(['clinic_id' => $clinicId]);
        $r3 = $stmt3->fetch();
        $sum3 = (int)($r3['s'] ?? 0);

        return max(0, $sum1) + max(0, $sum2) + max(0, $sum3);
    }
}
