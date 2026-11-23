<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\DocumentSignRequestRepository;
use App\Repositories\AuditLogRepository;
use App\Services\Auth\AuthService;
use App\Services\Mail\MailerService;
use App\Services\Storage\PrivateStorage;

final class PatientDocumentSignController extends Controller
{
    private function repo(): DocumentSignRequestRepository
    {
        return new DocumentSignRequestRepository($this->container->get(\PDO::class));
    }

    private function buildPublicUrl(string $token): string
    {
        $cfg = $this->container->has('config') ? $this->container->get('config') : [];
        $base = is_array($cfg) && isset($cfg['app']['base_url']) ? (string)$cfg['app']['base_url'] : '';
        if ($base === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        return rtrim($base, '/') . '/doc/sign?token=' . urlencode($token);
    }

    public function index(Request $request): Response
    {
        $this->authorize('patients.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = (int)$request->input('patient_id', 0);
        if ($clinicId === null || $patientId <= 0) {
            return $this->redirect('/patients');
        }

        $items = $this->repo()->listByPatient($clinicId, $patientId);

        $pdo = $this->container->get(\PDO::class);
        $patient = $pdo->prepare("SELECT id, name FROM patients WHERE id = :id AND clinic_id = :c AND deleted_at IS NULL LIMIT 1");
        $patient->execute(['id' => $patientId, 'c' => $clinicId]);
        $pat = $patient->fetch() ?: null;

        return $this->view('patients/document_sign', [
            'patient' => $pat,
            'items' => $items,
            'success' => trim((string)$request->input('success', '')),
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('patients.update');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        $patientId = (int)$request->input('patient_id', 0);
        if ($clinicId === null || $patientId <= 0) {
            return $this->redirect('/patients');
        }

        $title = trim((string)$request->input('title', ''));
        $body = trim((string)$request->input('body', ''));
        if ($title === '') {
            return $this->redirect('/patients/document-sign?patient_id=' . $patientId . '&error=' . urlencode('Título é obrigatório.'));
        }

        $filePath = null;
        $fileName = null;
        $fileMime = null;
        $file = $_FILES['document_file'] ?? null;
        if (is_array($file) && ($file['error'] ?? 4) === UPLOAD_ERR_OK && ($file['size'] ?? 0) > 0) {
            $ext = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'], true)) {
                return $this->redirect('/patients/document-sign?patient_id=' . $patientId . '&error=' . urlencode('Formato de arquivo não permitido. Envie PDF, JPG, PNG, DOC ou DOCX.'));
            }
            $bytes = file_get_contents($file['tmp_name']);
            if ($bytes !== false) {
                $token = bin2hex(random_bytes(12));
                $relative = 'document_sign/' . date('Ymd') . '_' . $token . '.' . $ext;
                PrivateStorage::put($clinicId, $relative, $bytes);
                $filePath = $relative;
                $fileName = (string)($file['name'] ?? '');
                $fileMime = (string)($file['type'] ?? '');
            }
        }

        $accessToken = bin2hex(random_bytes(32));
        $repo = $this->repo();
        $id = $repo->create($clinicId, $patientId, $title, ($body === '' ? null : $body), $filePath, $fileName, $fileMime, $accessToken, $userId);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId, $clinicId, 'document_sign.create', ['id' => $id, 'patient_id' => $patientId], $request->ip()
        );

        // Send
        $sendVia = trim((string)$request->input('send_via', 'portal'));
        $url = $this->buildPublicUrl($accessToken);
        $row = $repo->findById($clinicId, $id);
        $patientName = (string)($row['patient_name'] ?? '');
        $patientEmail = trim((string)($row['patient_email'] ?? ''));
        $patientPhone = trim((string)($row['patient_phone'] ?? ''));

        if ($sendVia === 'whatsapp' && $patientPhone !== '') {
            $this->sendWhatsapp($clinicId, $patientPhone, $patientName, $title, $url);
            $repo->markSent($id, 'whatsapp');
        } elseif ($sendVia === 'email' && $patientEmail !== '') {
            $this->sendEmail($clinicId, $patientEmail, $patientName, $title, $url);
            $repo->markSent($id, 'email');
        } else {
            $repo->markSent($id, 'portal');
        }

        return $this->redirect('/patients/document-sign?patient_id=' . $patientId . '&success=' . urlencode('Documento enviado para assinatura.'));
    }

    private function sendWhatsapp(int $clinicId, string $phone, string $name, string $title, string $url): void
    {
        try {
            $number = preg_replace('/\D/', '', $phone);
            $msg = "Olá {$name}, você recebeu um documento para assinatura: *{$title}*.\n\nAcesse o link para visualizar e assinar:\n{$url}";

            $client = new \App\Services\Whatsapp\EvolutionClient($this->container);
            $client->sendText($number, $msg);
        } catch (\Throwable $e) {
            error_log('[DocumentSign] WhatsApp send error: ' . $e->getMessage());
        }
    }

    private function sendEmail(int $clinicId, string $email, string $name, string $title, string $url): void
    {
        try {
            $clinic = (new \App\Repositories\ClinicRepository($this->container->get(\PDO::class)))->findById($clinicId);
            $clinicName = trim((string)($clinic['name'] ?? ''));
            $clinicEmail = trim((string)($clinic['contact_email'] ?? ''));
            $displayName = $clinicName !== '' ? $clinicName : 'LumiClinic';

            $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
            $greetName = $name !== '' ? $name : $email;

            $content = '<h1 style="margin:0 0 8px;font-size:22px;font-weight:800;color:#1a1a2e;">Olá, ' . $e($greetName) . '!</h1>'
                . '<p style="margin:0 0 24px;font-size:15px;color:#4a4a6a;line-height:1.6;">'
                . ($clinicName !== '' ? 'A <strong>' . $e($clinicName) . '</strong> enviou' : 'Você recebeu')
                . ' um documento para assinatura: <strong>' . $e($title) . '</strong></p>'
                . '<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;"><tr><td align="center">'
                . '<a href="' . $e($url) . '" style="display:inline-block;background:linear-gradient(135deg,#eeb810,#d4a010);color:#1a1a2e;font-weight:800;font-size:15px;text-decoration:none;padding:14px 36px;border-radius:10px;letter-spacing:0.2px;">Visualizar e assinar</a>'
                . '</td></tr></table>'
                . '<p style="margin:0;font-size:12px;color:#aaaacc;line-height:1.5;word-break:break-all;">Se o botão não funcionar, copie e cole este link no navegador: ' . $e($url) . '</p>';

            $html = \App\Services\Mail\EmailLayout::render(
                $displayName,
                'Documento para assinatura: ' . $title,
                $content,
                'Este e-mail foi enviado para <strong>' . $e($email) . '</strong> por ' . $e($displayName) . '.'
            );

            $options = ['from_name' => $displayName];
            if ($clinicEmail !== '') {
                $options['reply_to'] = $clinicEmail;
            }

            (new MailerService($this->container))->send($email, $greetName, 'Documento para assinatura: ' . $title, $html, $options);
        } catch (\Throwable $e) {
            error_log('[DocumentSign] Email send error: ' . $e->getMessage());
        }
    }

    /** Public page — patient signs without login */
    public function publicSign(Request $request): Response
    {
        $token = trim((string)$request->input('token', ''));
        if ($token === '') {
            return Response::html('Link inválido.', 400);
        }

        $repo = $this->repo();
        $row = $repo->findByToken($token);
        if ($row === null) {
            return Response::html('Documento não encontrado.', 404);
        }

        if ((string)($row['status'] ?? '') === 'signed') {
            return $this->view('public/document_sign', ['row' => $row, 'already_signed' => true]);
        }

        if ((string)($row['status'] ?? '') !== 'pending') {
            return Response::html('Este documento não está mais disponível para assinatura.', 410);
        }

        return $this->view('public/document_sign', ['row' => $row, 'already_signed' => false]);
    }

    public function publicSignSubmit(Request $request): Response
    {
        $token = trim((string)$request->input('token', ''));
        $signatureData = trim((string)$request->input('signature_data', ''));

        if ($token === '' || $signatureData === '') {
            return Response::html('Dados inválidos.', 400);
        }

        $repo = $this->repo();
        $row = $repo->findByToken($token);
        if ($row === null || (string)($row['status'] ?? '') !== 'pending') {
            return Response::html('Documento não disponível.', 410);
        }

        $repo->sign((int)$row['id'], $signatureData, $request->ip(), $request->header('user-agent') ?? '');

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            null, (int)$row['clinic_id'], 'document_sign.signed',
            ['id' => (int)$row['id'], 'patient_id' => (int)$row['patient_id']],
            $request->ip()
        );

        $row['status'] = 'signed';
        return $this->view('public/document_sign', ['row' => $row, 'already_signed' => true, 'just_signed' => true]);
    }

    /** Public file endpoint — serves the attachment of a sign request by token */
    public function publicFile(Request $request): Response
    {
        $token = trim((string)$request->input('token', ''));
        if ($token === '') {
            return Response::html('Link inválido.', 400);
        }

        $row = $this->repo()->findByToken($token);
        if ($row === null) {
            return Response::html('Documento não encontrado.', 404);
        }

        $status = (string)($row['status'] ?? '');
        if ($status !== 'pending' && $status !== 'signed') {
            return Response::html('Este documento não está mais disponível.', 410);
        }

        $download = (string)$request->input('download', '') === '1';
        $response = $this->serveAttachment($row, $download);
        if ($response->status() !== 200) {
            return $response;
        }

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            null, (int)$row['clinic_id'], $download ? 'document_sign.file_downloaded' : 'document_sign.file_viewed',
            ['id' => (int)$row['id'], 'patient_id' => (int)$row['patient_id']],
            $request->ip(), null, 'document_sign_request', (int)$row['id'], $request->header('user-agent')
        );

        return $response;
    }

    /** Staff file endpoint — clinic-scoped */
    public function file(Request $request): Response
    {
        $this->authorize('patients.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/patients');
        }

        $id = (int)$request->input('id', 0);
        $row = $id > 0 ? $this->repo()->findById($clinicId, $id) : null;
        if ($row === null) {
            return Response::html('Documento não encontrado.', 404);
        }

        $download = (string)$request->input('download', '') === '1';
        $response = $this->serveAttachment($row, $download);
        if ($response->status() !== 200) {
            return $response;
        }

        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $auth->userId(), $clinicId, 'document_sign.file_viewed_staff',
            ['id' => (int)$row['id'], 'patient_id' => (int)$row['patient_id'], 'download' => $download],
            $request->ip(), $roleCodes, 'document_sign_request', (int)$row['id'], $request->header('user-agent')
        );

        return $response;
    }

    /** @param array<string,mixed> $row */
    private function serveAttachment(array $row, bool $download): Response
    {
        $relative = trim((string)($row['file_path'] ?? ''));
        if ($relative === '') {
            return Response::html('Este documento não possui arquivo anexo.', 404);
        }

        $clinicId = (int)($row['clinic_id'] ?? 0);
        $fullPath = PrivateStorage::fullPath($clinicId, $relative);
        $realFile = is_file($fullPath) ? realpath($fullPath) : false;
        $realBase = realpath(PrivateStorage::clinicBasePath($clinicId));
        if ($realFile === false || $realBase === false || !str_starts_with($realFile, $realBase . '/')) {
            return Response::html('Arquivo não encontrado.', 404);
        }

        $ext = strtolower((string)pathinfo($relative, PATHINFO_EXTENSION));
        $mimeMap = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        $inlineAllowed = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true);
        $disposition = ($download || !$inlineAllowed) ? 'attachment' : 'inline';

        $originalName = trim(str_replace(["\r", "\n"], '', (string)($row['file_name'] ?? '')));
        $asciiName = (string)preg_replace('/[^\x20-\x7E]/', '', $originalName);
        $asciiName = trim(str_replace(['"', '\\', ';'], '', $asciiName));
        if ($asciiName === '') {
            $asciiName = 'documento' . ($ext !== '' ? '.' . $ext : '');
        }
        $utf8Name = $originalName !== '' ? $originalName : $asciiName;

        $bytes = file_get_contents($realFile);
        if ($bytes === false) {
            return Response::html('Arquivo não encontrado.', 404);
        }

        return Response::raw($bytes, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($utf8Name),
            'Content-Length' => (string)strlen($bytes),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
