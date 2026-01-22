<?php

declare(strict_types=1);

namespace App\Controllers\Anamnesis;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Anamnesis\AnamnesisService;
use App\Services\Auth\AuthService;

final class AnamnesisController extends Controller
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

    public function templates(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new AnamnesisService($this->container);
        $templates = $service->listTemplates();

        return $this->view('anamnesis/templates', [
            'templates' => $templates,
        ]);
    }

    public function createTemplate(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        return $this->view('anamnesis/templates-create');
    }

    public function storeTemplate(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $fieldsJson = trim((string)$request->input('fields_json', ''));

        if ($name === '') {
            return $this->view('anamnesis/templates-create', ['error' => 'Nome é obrigatório.']);
        }

        $fields = [];
        if ($fieldsJson !== '') {
            $decoded = json_decode($fieldsJson, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }

        $service = new AnamnesisService($this->container);
        $id = $service->createTemplate($name, is_array($fields) ? $fields : [], $request->ip());

        return $this->redirect('/anamnesis/templates/edit?id=' . $id);
    }

    public function editTemplate(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/anamnesis/templates');
        }

        $service = new AnamnesisService($this->container);
        $data = $service->getTemplateWithFields($id);

        return $this->view('anamnesis/templates-edit', [
            'template' => $data['template'],
            'fields' => $data['fields'],
        ]);
    }

    public function updateTemplate(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $name = trim((string)$request->input('name', ''));
        $status = trim((string)$request->input('status', 'active'));
        $fieldsJson = trim((string)$request->input('fields_json', ''));

        if ($id <= 0 || $name === '') {
            $service = new AnamnesisService($this->container);
            $data = $service->getTemplateWithFields($id);
            return $this->view('anamnesis/templates-edit', [
                'template' => $data['template'],
                'fields' => $data['fields'],
                'error' => 'Preencha os campos obrigatórios.',
            ]);
        }

        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $fields = [];
        if ($fieldsJson !== '') {
            $decoded = json_decode($fieldsJson, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }

        $service = new AnamnesisService($this->container);
        $service->updateTemplate($id, $name, $status, is_array($fields) ? $fields : [], $request->ip());

        return $this->redirect('/anamnesis/templates/edit?id=' . $id);
    }

    public function index(Request $request)
    {
        $this->authorize('anamnesis.fill');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new AnamnesisService($this->container);
        $data = $service->listForPatient($patientId, $request->ip());

        return $this->view('anamnesis/index', [
            'patient' => $data['patient'],
            'templates' => $data['templates'],
            'responses' => $data['responses'],
        ]);
    }

    public function fill(Request $request)
    {
        $this->authorize('anamnesis.fill');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $templateId = (int)$request->input('template_id', 0);
        if ($patientId <= 0 || $templateId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new AnamnesisService($this->container);
        $list = $service->listForPatient($patientId, $request->ip());
        $tpl = $service->getTemplateWithFields($templateId);

        return $this->view('anamnesis/fill', [
            'patient' => $list['patient'],
            'template' => $tpl['template'],
            'fields' => $tpl['fields'],
            'professionals' => $service->listProfessionals(),
        ]);
    }

    public function submit(Request $request)
    {
        $this->authorize('anamnesis.fill');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $templateId = (int)$request->input('template_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);

        if ($patientId <= 0 || $templateId <= 0) {
            return $this->redirect('/patients');
        }

        $answers = [];
        foreach ($_POST as $k => $v) {
            if (str_starts_with((string)$k, 'a_')) {
                $answers[substr((string)$k, 2)] = $v;
            }
        }

        $service = new AnamnesisService($this->container);
        $service->submit(
            $patientId,
            $templateId,
            ($professionalId > 0 ? $professionalId : null),
            $answers,
            $request->ip()
        );

        return $this->redirect('/anamnesis?patient_id=' . $patientId);
    }
}
