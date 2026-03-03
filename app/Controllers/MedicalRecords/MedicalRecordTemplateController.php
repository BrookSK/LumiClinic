<?php

declare(strict_types=1);

namespace App\Controllers\MedicalRecords;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\MedicalRecords\MedicalRecordTemplateService;

final class MedicalRecordTemplateController extends Controller
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
        $this->authorize('medical_record_templates.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $templates = (new MedicalRecordTemplateService($this->container))->listTemplates();

        return $this->view('medical-record-templates/index', [
            'templates' => $templates,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('medical_record_templates.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        return $this->view('medical-record-templates/create');
    }

    public function store(Request $request)
    {
        $this->authorize('medical_record_templates.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $fieldsJson = trim((string)$request->input('fields_json', ''));

        if ($name === '') {
            return $this->view('medical-record-templates/create', ['error' => 'Nome é obrigatório.']);
        }

        $fields = [];
        if ($fieldsJson !== '') {
            $decoded = json_decode($fieldsJson, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }

        $svc = new MedicalRecordTemplateService($this->container);
        $id = $svc->createTemplate($name, is_array($fields) ? $fields : [], $request->ip());

        return $this->redirect('/medical-record-templates/edit?id=' . $id . '&saved=1');
    }

    public function edit(Request $request)
    {
        $this->authorize('medical_record_templates.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/medical-record-templates');
        }

        $svc = new MedicalRecordTemplateService($this->container);
        $data = $svc->getTemplateWithFields($id);

        $saved = trim((string)$request->input('saved', ''));

        return $this->view('medical-record-templates/edit', [
            'template' => $data['template'],
            'fields' => $data['fields'],
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('medical_record_templates.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $name = trim((string)$request->input('name', ''));
        $status = trim((string)$request->input('status', 'active'));
        $fieldsJson = trim((string)$request->input('fields_json', ''));

        if ($id <= 0 || $name === '') {
            $svc = new MedicalRecordTemplateService($this->container);
            $data = $svc->getTemplateWithFields($id);
            return $this->view('medical-record-templates/edit', [
                'template' => $data['template'],
                'fields' => $data['fields'],
                'error' => 'Preencha os campos obrigatórios.',
            ]);
        }

        $fields = [];
        if ($fieldsJson !== '') {
            $decoded = json_decode($fieldsJson, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }

        $svc = new MedicalRecordTemplateService($this->container);
        try {
            $svc->updateTemplate($id, $name, $status, is_array($fields) ? $fields : [], $request->ip());
        } catch (\RuntimeException $e) {
            $data = $svc->getTemplateWithFields($id);
            return $this->view('medical-record-templates/edit', [
                'template' => $data['template'],
                'fields' => $data['fields'],
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $data = $svc->getTemplateWithFields($id);
            return $this->view('medical-record-templates/edit', [
                'template' => $data['template'],
                'fields' => $data['fields'],
                'error' => 'Erro ao salvar.',
            ]);
        }

        return $this->redirect('/medical-record-templates/edit?id=' . $id);
    }
}
