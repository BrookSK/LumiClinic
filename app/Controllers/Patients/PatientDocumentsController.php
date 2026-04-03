<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\PatientDocumentRepository;
use App\Repositories\PatientRepository;
use App\Services\Auth\AuthService;
use App\Services\Storage\PrivateStorage;

final class PatientDocumentsController extends Controller
{
    private function ctx(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) throw new \RuntimeException('Contexto inválido.');
        return [$clinicId, $userId];
    }

    public function index(Request $request)
    {
        $this->authorize('patients.read');
        [$clinicId] = $this->ctx();

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) return $this->redirect('/patients');

        $pdo = $this->container->get(\PDO::class);
        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        if ($patient === null) return $this->redirect('/patients');

        $docs = (new PatientDocumentRepository($pdo))->listByPatient($clinicId, $patientId);

        return $this->view('patients/documents', [
            'patient'   => $patient,
            'documents' => $docs,
            'error'     => trim((string)$request->input('error', '')),
            'success'   => trim((string)$request->input('success', '')),
        ]);
    }

    public function upload(Request $request)
    {
        $this->authorize('patients.update');
        [$clinicId, $userId] = $this->ctx();

        $patientId = (int)$request->input('patient_id', 0);
        $title     = trim((string)$request->input('title', ''));
        if ($patientId <= 0) return $this->redirect('/patients');
        if ($title === '') $title = 'Documento';

        $file = $_FILES['document'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->redirect('/patients/documents?patient_id=' . $patientId . '&error=' . urlencode('Selecione um arquivo.'));
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        $bytes = file_get_contents($tmp);
        if ($bytes === false || $bytes === '') {
            return $this->redirect('/patients/documents?patient_id=' . $patientId . '&error=' . urlencode('Arquivo inválido.'));
        }

        $originalName = (string)($file['name'] ?? 'documento');
        $mime = (string)($file['type'] ?? 'application/octet-stream');
        $size = (int)($file['size'] ?? strlen($bytes));
        $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';

        // Verificar limite de armazenamento
        $ent = new \App\Services\Billing\PlanEntitlementsService($this->container);
        $limitBytes = $ent->storageLimitBytes($clinicId);
        if (is_int($limitBytes)) {
            $used = $this->sumStorageUsedBytes($clinicId);
            if (($used + $size) > $limitBytes) {
                return $this->redirect('/patients/documents?patient_id=' . $patientId . '&error=' . urlencode('Limite de armazenamento do plano atingido.'));
            }
        }

        $token = bin2hex(random_bytes(12));
        $relative = 'patient_documents/patient_' . $patientId . '/' . date('Ymd') . '_' . $token . '.' . $ext;
        PrivateStorage::put($clinicId, $relative, $bytes);

        $pdo = $this->container->get(\PDO::class);
        (new PatientDocumentRepository($pdo))->create($clinicId, $patientId, $title, $relative, $originalName, $mime, $size, $userId);

        return $this->redirect('/patients/documents?patient_id=' . $patientId . '&success=' . urlencode('Documento enviado.'));
    }

    public function file(Request $request): Response
    {
        $this->authorize('patients.read');
        [$clinicId] = $this->ctx();

        $id = (int)$request->input('id', 0);
        $pdo = $this->container->get(\PDO::class);
        $doc = (new PatientDocumentRepository($pdo))->findById($clinicId, $id);
        if ($doc === null) return Response::html('Documento não encontrado.', 404);

        $fullPath = PrivateStorage::fullPath($clinicId, (string)$doc['file_path']);
        if (!is_file($fullPath)) return Response::html('Arquivo não encontrado.', 404);

        $mime = (string)($doc['mime_type'] ?? 'application/octet-stream');
        $name = (string)($doc['original_filename'] ?? 'documento');

        return Response::raw((string)file_get_contents($fullPath), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $name . '"',
        ]);
    }

    public function delete(Request $request)
    {
        $this->authorize('patients.update');
        [$clinicId] = $this->ctx();

        $id = (int)$request->input('id', 0);
        $patientId = (int)$request->input('patient_id', 0);

        (new PatientDocumentRepository($this->container->get(\PDO::class)))->softDelete($clinicId, $id);

        return $this->redirect('/patients/documents?patient_id=' . $patientId . '&success=' . urlencode('Documento excluído.'));
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
            ['patient_documents', 'size_bytes'],
        ];
        foreach ($tables as [$t, $col]) {
            try {
                $stmt = $pdo->prepare("SELECT COALESCE(SUM({$col}),0) AS s FROM {$t} WHERE clinic_id = :c AND deleted_at IS NULL");
                $stmt->execute(['c' => $clinicId]);
                $r = $stmt->fetch();
                $sum += (int)($r['s'] ?? 0);
            } catch (\Throwable $e) {}
        }
        return max(0, $sum);
    }
}
