<?php

declare(strict_types=1);

namespace App\Controllers\MedicalRecords;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\MedicalRecords\MedicalRecordService;
use App\Services\MedicalRecords\MedicalRecordTemplateService;

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

        $templateId = (int)$request->input('template_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $dateFrom = trim((string)$request->input('date_from', ''));
        $dateTo = trim((string)$request->input('date_to', ''));

        $service = new MedicalRecordService($this->container);
        $filters = [];
        if ($templateId > 0) {
            $filters['template_id'] = $templateId;
        }
        if ($professionalId > 0) {
            $filters['professional_id'] = $professionalId;
        }
        if ($dateFrom !== '') {
            $filters['date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $filters['date_to'] = $dateTo;
        }

        $data = $filters !== []
            ? $service->timelineFiltered($patientId, $filters, $request->ip(), $request->header('user-agent'))
            : $service->timeline($patientId, $request->ip(), $request->header('user-agent'));

        $tplService = new MedicalRecordTemplateService($this->container);

        return $this->view('medical-records/index', [
            'patient' => $data['patient'],
            'records' => $data['records'],
            'templates' => $tplService->listTemplates(),
            'professionals' => $service->listProfessionals(),
            'filters' => $filters,
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

        $templateId = (int)$request->input('template_id', 0);
        $tpl = null;
        $fields = [];
        if ($templateId > 0) {
            $tplSvc = new MedicalRecordTemplateService($this->container);
            $tmp = $tplSvc->getTemplateWithFields($templateId);
            $tpl = $tmp['template'];
            $fields = $tmp['fields'];
        }

        $service = new MedicalRecordService($this->container);
        $data = $service->timeline($patientId, $request->ip(), $request->header('user-agent'));

        $tplService = new MedicalRecordTemplateService($this->container);

        return $this->view('medical-records/create', [
            'patient' => $data['patient'],
            'professionals' => $service->listProfessionals(),
            'templates' => $tplService->listTemplates(),
            'template' => $tpl,
            'fields' => $fields,
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
        $templateId = (int)$request->input('template_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $clinicalDescription = trim((string)$request->input('clinical_description', ''));
        $clinicalEvolution = trim((string)$request->input('clinical_evolution', ''));
        $notes = trim((string)$request->input('notes', ''));

        $fields = [];
        foreach ($_POST as $k => $v) {
            if (str_starts_with((string)$k, 'f_')) {
                $fields[substr((string)$k, 2)] = $v;
            }
        }

        if ($attendedAt === '') {
            $service = new MedicalRecordService($this->container);
            $data = $service->timeline($patientId, $request->ip());
            $tplService = new MedicalRecordTemplateService($this->container);
            return $this->view('medical-records/create', [
                'patient' => $data['patient'],
                'professionals' => $service->listProfessionals(),
                'templates' => $tplService->listTemplates(),
                'error' => 'Data/hora do atendimento é obrigatória.',
            ]);
        }

        $attendedAt = str_replace('T', ' ', $attendedAt);
        if (strlen($attendedAt) === 16) {
            $attendedAt .= ':00';
        }

        $service = new MedicalRecordService($this->container);
        try {
            $id = $service->create($patientId, [
                'professional_id' => ($professionalId > 0 ? $professionalId : null),
                'attended_at' => $attendedAt,
                'procedure_type' => ($procedureType === '' ? null : $procedureType),
                'template_id' => ($templateId > 0 ? $templateId : null),
                'fields' => $fields,
                'clinical_description' => ($clinicalDescription === '' ? null : $clinicalDescription),
                'clinical_evolution' => ($clinicalEvolution === '' ? null : $clinicalEvolution),
                'notes' => ($notes === '' ? null : $notes),
            ], $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            $data = $service->timeline($patientId, $request->ip(), $request->header('user-agent'));
            $tplService = new MedicalRecordTemplateService($this->container);
            $tpl = null;
            $tplFields = [];
            if ($templateId > 0) {
                $tmp = $tplService->getTemplateWithFields($templateId);
                $tpl = $tmp['template'];
                $tplFields = $tmp['fields'];
            }
            return $this->view('medical-records/create', [
                'patient' => $data['patient'],
                'professionals' => $service->listProfessionals(),
                'templates' => $tplService->listTemplates(),
                'template' => $tpl,
                'fields' => $tplFields,
                'error' => $e->getMessage(),
            ]);
        }

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

        $selectedTemplateId = (int)($data['record']['template_id'] ?? 0);
        $overrideTemplateId = (int)$request->input('template_id', 0);
        if ($overrideTemplateId > 0) {
            $selectedTemplateId = $overrideTemplateId;
        }

        $tplService = new MedicalRecordTemplateService($this->container);
        $tpl = null;
        $fields = [];
        if ($selectedTemplateId > 0) {
            $tmp = $tplService->getTemplateWithFields($selectedTemplateId);
            $tpl = $tmp['template'];
            $fields = $tmp['fields'];
        }

        return $this->view('medical-records/edit', [
            'patient' => $data['patient'],
            'record' => $data['record'],
            'professionals' => $service->listProfessionals(),
            'templates' => $tplService->listTemplates(),
            'template' => $tpl,
            'fields' => $fields,
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
        $templateId = (int)$request->input('template_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $clinicalDescription = trim((string)$request->input('clinical_description', ''));
        $clinicalEvolution = trim((string)$request->input('clinical_evolution', ''));
        $notes = trim((string)$request->input('notes', ''));

        $fields = [];
        foreach ($_POST as $k => $v) {
            if (str_starts_with((string)$k, 'f_')) {
                $fields[substr((string)$k, 2)] = $v;
            }
        }

        if ($attendedAt === '') {
            $service = new MedicalRecordService($this->container);
            $data = $service->getForEdit($patientId, $id, $request->ip(), $request->header('user-agent'));
            $tplService = new MedicalRecordTemplateService($this->container);
            $tpl = null;
            $tplFields = [];
            if ($templateId > 0) {
                $tmp = $tplService->getTemplateWithFields($templateId);
                $tpl = $tmp['template'];
                $tplFields = $tmp['fields'];
            }
            return $this->view('medical-records/edit', [
                'patient' => $data['patient'],
                'record' => $data['record'],
                'professionals' => $service->listProfessionals(),
                'templates' => $tplService->listTemplates(),
                'template' => $tpl,
                'fields' => $tplFields,
                'error' => 'Data/hora do atendimento é obrigatória.',
            ]);
        }

        $attendedAt = str_replace('T', ' ', $attendedAt);
        if (strlen($attendedAt) === 16) {
            $attendedAt .= ':00';
        }

        $service = new MedicalRecordService($this->container);
        try {
            $service->update($patientId, $id, [
                'professional_id' => ($professionalId > 0 ? $professionalId : null),
                'attended_at' => $attendedAt,
                'procedure_type' => ($procedureType === '' ? null : $procedureType),
                'template_id' => ($templateId > 0 ? $templateId : null),
                'fields' => $fields,
                'clinical_description' => ($clinicalDescription === '' ? null : $clinicalDescription),
                'clinical_evolution' => ($clinicalEvolution === '' ? null : $clinicalEvolution),
                'notes' => ($notes === '' ? null : $notes),
            ], $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            $data = $service->getForEdit($patientId, $id, $request->ip(), $request->header('user-agent'));
            $tplService = new MedicalRecordTemplateService($this->container);
            $tpl = null;
            $tplFields = [];
            if ($templateId > 0) {
                $tmp = $tplService->getTemplateWithFields($templateId);
                $tpl = $tmp['template'];
                $tplFields = $tmp['fields'];
            }
            return $this->view('medical-records/edit', [
                'patient' => $data['patient'],
                'record' => $data['record'],
                'professionals' => $service->listProfessionals(),
                'templates' => $tplService->listTemplates(),
                'template' => $tpl,
                'fields' => $tplFields,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->redirect('/medical-records?patient_id=' . $patientId . '#mr-' . $id);
    }
}
