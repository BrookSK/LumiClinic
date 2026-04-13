<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PrescriptionRepository;
use App\Repositories\ProfessionalRepository;
use App\Services\Auth\AuthService;

final class PrescriptionController extends Controller
{
    private function clinicAndActor(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }
        return [$clinicId, $userId];
    }

    public function index(Request $request)
    {
        $this->authorize('medical_records.read');
        [$clinicId] = $this->clinicAndActor();

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $pdo = $this->container->get(\PDO::class);
        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            return $this->redirect('/patients');
        }

        $prescriptions = (new PrescriptionRepository($pdo))->listByPatient($clinicId, $patientId);
        $professionals = (new ProfessionalRepository($pdo))->listActiveByClinic($clinicId);

        return $this->view('patients/prescriptions', [
            'patient'       => $patient,
            'prescriptions' => $prescriptions,
            'professionals' => $professionals,
            'error'         => trim((string)$request->input('error', '')),
            'success'       => trim((string)$request->input('success', '')),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('medical_records.create');
        [$clinicId, $userId] = $this->clinicAndActor();

        $patientId     = (int)$request->input('patient_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $medicalRecordId = (int)$request->input('medical_record_id', 0);
        $title         = trim((string)$request->input('title', 'Receita'));
        $body          = trim((string)$request->input('body', ''));
        $issuedAt      = trim((string)$request->input('issued_at', date('Y-m-d')));

        if ($patientId <= 0 || $body === '') {
            return $this->redirect('/patients/prescriptions?patient_id=' . $patientId . '&error=' . urlencode('Preencha os campos obrigatórios.'));
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new PrescriptionRepository($pdo);
        $id = $repo->create(
            $clinicId, $patientId,
            $professionalId > 0 ? $professionalId : null,
            $medicalRecordId > 0 ? $medicalRecordId : null,
            $title !== '' ? $title : 'Receita',
            $body,
            $issuedAt !== '' ? $issuedAt : date('Y-m-d'),
            $userId
        );

        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'prescriptions.create', ['prescription_id' => $id, 'patient_id' => $patientId], $request->ip());

        return $this->redirect('/patients/prescriptions?patient_id=' . $patientId . '&success=' . urlencode('Receita criada.'));
    }

    public function edit(Request $request)
    {
        $this->authorize('medical_records.read');
        [$clinicId] = $this->clinicAndActor();

        $id = (int)$request->input('id', 0);
        $pdo = $this->container->get(\PDO::class);
        $rx = (new PrescriptionRepository($pdo))->findById($clinicId, $id);
        if ($rx === null) {
            return $this->redirect('/patients');
        }

        $professionals = (new ProfessionalRepository($pdo))->listActiveByClinic($clinicId);

        return $this->view('patients/prescription_edit', [
            'rx'            => $rx,
            'professionals' => $professionals,
            'error'         => trim((string)$request->input('error', '')),
            'success'       => trim((string)$request->input('success', '')),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('medical_records.create');
        [$clinicId, $userId] = $this->clinicAndActor();

        $id      = (int)$request->input('id', 0);
        $title   = trim((string)$request->input('title', 'Receita'));
        $body    = trim((string)$request->input('body', ''));
        $issuedAt = trim((string)$request->input('issued_at', date('Y-m-d')));

        $pdo = $this->container->get(\PDO::class);
        $repo = new PrescriptionRepository($pdo);
        $rx = $repo->findById($clinicId, $id);
        if ($rx === null || $body === '') {
            return $this->redirect('/patients/prescriptions?patient_id=' . (int)($rx['patient_id'] ?? 0) . '&error=' . urlencode('Dados inválidos.'));
        }

        $repo->update($clinicId, $id, $title !== '' ? $title : 'Receita', $body, $issuedAt !== '' ? $issuedAt : date('Y-m-d'));
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'prescriptions.update', ['prescription_id' => $id], $request->ip());

        return $this->redirect('/patients/prescription/edit?id=' . $id . '&success=' . urlencode('Salvo.'));
    }

    public function delete(Request $request)
    {
        $this->authorize('medical_records.create');
        [$clinicId, $userId] = $this->clinicAndActor();

        $id = (int)$request->input('id', 0);
        $patientId = (int)$request->input('patient_id', 0);

        $pdo = $this->container->get(\PDO::class);
        (new PrescriptionRepository($pdo))->softDelete($clinicId, $id);
        (new AuditLogRepository($pdo))->log($userId, $clinicId, 'prescriptions.delete', ['prescription_id' => $id], $request->ip());

        return $this->redirect('/patients/prescriptions?patient_id=' . $patientId . '&success=' . urlencode('Receita excluída.'));
    }

    public function print(Request $request): Response
    {
        $this->authorize('medical_records.read');
        [$clinicId] = $this->clinicAndActor();

        $id = (int)$request->input('id', 0);
        $pdo = $this->container->get(\PDO::class);
        $rx = (new PrescriptionRepository($pdo))->findById($clinicId, $id);
        if ($rx === null) {
            return Response::html('Receita não encontrada.', 404);
        }

        $clinic = (new \App\Repositories\ClinicRepository($pdo))->findById($clinicId);

        // Load patient data (CPF, address)
        $patient = null;
        $patientId = (int)($rx['patient_id'] ?? 0);
        if ($patientId > 0) {
            $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        }

        // Load professional data (specialty, council_number)
        $professionalSpecialty = '';
        $professionalCouncil = '';
        if (!empty($rx['professional_id'])) {
            $prof = (new ProfessionalRepository($pdo))->findById($clinicId, (int)$rx['professional_id']);
            $professionalSpecialty = trim((string)($prof['specialty'] ?? ''));
            $professionalCouncil = trim((string)($prof['council_number'] ?? ''));
        }

        return $this->view('patients/prescription_print', [
            'rx'                     => $rx,
            'clinic'                 => $clinic,
            'patient'                => $patient,
            'professional_specialty' => $professionalSpecialty,
            'professional_council'   => $professionalCouncil,
        ]);
    }
}
