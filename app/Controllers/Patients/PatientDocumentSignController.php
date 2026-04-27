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
            $bytes = file_get_contents($file['tmp_name']);
            if ($bytes !== false) {
                $ext = pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION) ?: 'pdf';
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
            $this->sendEmail($patientEmail, $patientName, $title, $url);
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

    private function sendEmail(string $email, string $name, string $title, string $url): void
    {
        try {
            $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $safeName = htmlspecialchars($name !== '' ? $name : $email, ENT_QUOTES, 'UTF-8');
            $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;">'
                . '<p>Olá, <strong>' . $safeName . '</strong>!</p>'
                . '<p>Você recebeu um documento para assinatura: <strong>' . $safeTitle . '</strong></p>'
                . '<p><a href="' . $safeUrl . '" style="display:inline-block;background:#815901;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Visualizar e assinar</a></p>'
                . '<p style="font-size:12px;color:#999;">Se o botão não funcionar, copie e cole: ' . $safeUrl . '</p>'
                . '</div>';

            (new MailerService($this->container))->send($email, $name !== '' ? $name : $email, 'Documento para assinatura: ' . $title, $html);
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
}
