<?php

declare(strict_types=1);

namespace App\Controllers\MedicalRecords;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\MedicalRecords\MedicalRecordService;

final class MedicalRecordController extends Controller
{
    private function redirectSuperAdminWithoutClinicContext(): ?\App\Core\Http\Response
    {
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if (!$isSuperAdmin) {
            return null;
        }

        $auth = new AuthService($this->container);
        if ($auth->clinicId() === null) {
            return $this->redirect('/sys/clinics');
        }

        return null;
    }

    public function index(Request $request)
    {
        $this->authorize('medical_records.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new MedicalRecordService($this->container);
        $data = $service->timeline($patientId, $request->ip(), $request->header('user-agent'));

        return $this->view('medical-records/index', [
            'patient' => $data['patient'],
            'records' => $data['records'],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('medical_records.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new MedicalRecordService($this->container);
        $data = $service->timeline($patientId, $request->ip(), $request->header('user-agent'));

        return $this->view('medical-records/create', [
            'patient' => $data['patient'],
            'professionals' => $service->listProfessionals(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('medical_records.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $attendedAt = trim((string)$request->input('attended_at', ''));
        $procedureType = trim((string)$request->input('procedure_type', ''));
        $professionalId = (int)$request->input('professional_id', 0);
        $clinicalDescription = trim((string)$request->input('clinical_description', ''));
        $clinicalEvolution = trim((string)$request->input('clinical_evolution', ''));
        $notes = trim((string)$request->input('notes', ''));

        if ($attendedAt === '') {
            $service = new MedicalRecordService($this->container);
            $data = $service->timeline($patientId, $request->ip());
            return $this->view('medical-records/create', [
                'patient' => $data['patient'],
                'professionals' => $service->listProfessionals(),
                'error' => 'Data/hora do atendimento é obrigatória.',
            ]);
        }

        $attendedAt = str_replace('T', ' ', $attendedAt);
        if (strlen($attendedAt) === 16) {
            $attendedAt .= ':00';
        }

        $service = new MedicalRecordService($this->container);
        $id = $service->create($patientId, [
            'professional_id' => ($professionalId > 0 ? $professionalId : null),
            'attended_at' => $attendedAt,
            'procedure_type' => ($procedureType === '' ? null : $procedureType),
            'clinical_description' => ($clinicalDescription === '' ? null : $clinicalDescription),
            'clinical_evolution' => ($clinicalEvolution === '' ? null : $clinicalEvolution),
            'notes' => ($notes === '' ? null : $notes),
        ], $request->ip(), $request->header('user-agent'));

        return $this->redirect('/medical-records?patient_id=' . $patientId . '#mr-' . $id);
    }

    public function edit(Request $request)
    {
        $this->authorize('medical_records.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $id = (int)$request->input('id', 0);
        if ($patientId <= 0 || $id <= 0) {
            return $this->redirect('/patients');
        }

        $service = new MedicalRecordService($this->container);
        $data = $service->getForEdit($patientId, $id, $request->ip(), $request->header('user-agent'));

        return $this->view('medical-records/edit', [
            'patient' => $data['patient'],
            'record' => $data['record'],
            'professionals' => $service->listProfessionals(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('medical_records.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $id = (int)$request->input('id', 0);
        if ($patientId <= 0 || $id <= 0) {
            return $this->redirect('/patients');
        }

        $attendedAt = trim((string)$request->input('attended_at', ''));
        $procedureType = trim((string)$request->input('procedure_type', ''));
        $professionalId = (int)$request->input('professional_id', 0);
        $clinicalDescription = trim((string)$request->input('clinical_description', ''));
        $clinicalEvolution = trim((string)$request->input('clinical_evolution', ''));
        $notes = trim((string)$request->input('notes', ''));

        if ($attendedAt === '') {
            $service = new MedicalRecordService($this->container);
            $data = $service->getForEdit($patientId, $id, $request->ip(), $request->header('user-agent'));
            return $this->view('medical-records/edit', [
                'patient' => $data['patient'],
                'record' => $data['record'],
                'professionals' => $service->listProfessionals(),
                'error' => 'Data/hora do atendimento é obrigatória.',
            ]);
        }

        $attendedAt = str_replace('T', ' ', $attendedAt);
        if (strlen($attendedAt) === 16) {
            $attendedAt .= ':00';
        }

        $service = new MedicalRecordService($this->container);
        $service->update($patientId, $id, [
            'professional_id' => ($professionalId > 0 ? $professionalId : null),
            'attended_at' => $attendedAt,
            'procedure_type' => ($procedureType === '' ? null : $procedureType),
            'clinical_description' => ($clinicalDescription === '' ? null : $clinicalDescription),
            'clinical_evolution' => ($clinicalEvolution === '' ? null : $clinicalEvolution),
            'notes' => ($notes === '' ? null : $notes),
        ], $request->ip(), $request->header('user-agent'));

        return $this->redirect('/medical-records?patient_id=' . $patientId . '#mr-' . $id);
    }
}
