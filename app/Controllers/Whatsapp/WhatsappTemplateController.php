<?php

declare(strict_types=1);

namespace App\Controllers\Whatsapp;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Whatsapp\WhatsappTemplateService;

final class WhatsappTemplateController extends Controller
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
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $templates = (new WhatsappTemplateService($this->container))->listTemplates();

        return $this->view('whatsapp-templates/index', [
            'templates' => $templates,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        return $this->view('whatsapp-templates/create');
    }

    public function store(Request $request)
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $code = trim((string)$request->input('code', ''));
        $name = trim((string)$request->input('name', ''));
        $body = trim((string)$request->input('body', ''));

        if ($code === '' || $name === '' || $body === '') {
            return $this->view('whatsapp-templates/create', [
                'error' => 'Preencha os campos obrigatórios.',
                'template' => ['code' => $code, 'name' => $name, 'body' => $body],
            ]);
        }

        try {
            $id = (new WhatsappTemplateService($this->container))->createTemplate($code, $name, $body, $request->ip());
            return $this->redirect('/whatsapp-templates/edit?id=' . $id . '&saved=1');
        } catch (\RuntimeException $e) {
            return $this->view('whatsapp-templates/create', [
                'error' => $e->getMessage(),
                'template' => ['code' => $code, 'name' => $name, 'body' => $body],
            ]);
        } catch (\Throwable $e) {
            return $this->view('whatsapp-templates/create', [
                'error' => 'Erro ao salvar.',
                'template' => ['code' => $code, 'name' => $name, 'body' => $body],
            ]);
        }
    }

    public function edit(Request $request)
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/whatsapp-templates');
        }

        $saved = trim((string)$request->input('saved', ''));

        try {
            $tpl = (new WhatsappTemplateService($this->container))->getTemplate($id);
            return $this->view('whatsapp-templates/edit', [
                'template' => $tpl,
                'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
            ]);
        } catch (\Throwable $e) {
            return $this->redirect('/whatsapp-templates');
        }
    }

    public function update(Request $request)
    {
        $this->authorize('settings.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $code = trim((string)$request->input('code', ''));
        $name = trim((string)$request->input('name', ''));
        $body = trim((string)$request->input('body', ''));
        $status = trim((string)$request->input('status', 'active'));

        try {
            (new WhatsappTemplateService($this->container))->updateTemplate($id, $code, $name, $body, $status, $request->ip());
            return $this->redirect('/whatsapp-templates/edit?id=' . $id . '&saved=1');
        } catch (\RuntimeException $e) {
            $tpl = ['id' => $id, 'code' => $code, 'name' => $name, 'body' => $body, 'status' => $status];
            return $this->view('whatsapp-templates/edit', [
                'template' => $tpl,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $tpl = ['id' => $id, 'code' => $code, 'name' => $name, 'body' => $body, 'status' => $status];
            return $this->view('whatsapp-templates/edit', [
                'template' => $tpl,
                'error' => 'Erro ao salvar.',
            ]);
        }
    }
}
