<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Mail\MailerService;
use App\Services\System\SystemSettingsService;

final class SystemSettingsController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    public function billing(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);

        return $this->view('system/settings/billing', $service->getBillingSettings());
    }

    public function billingSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $service->saveBillingSettings($_POST);

        return $this->redirect('/sys/settings/billing');
    }

    public function seo(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);

        return $this->view('system/settings/seo', $service->getSeoSettings());
    }

    public function seoSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $service->saveSeoSettings($_POST);

        return $this->redirect('/sys/settings/seo');
    }

    public function support(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);

        return $this->view('system/settings/support', $service->getSupportSettings());
    }

    public function supportSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $service->saveSupportSettings($_POST);

        return $this->redirect('/sys/settings/support');
    }

    public function mail(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $status = trim((string)$request->input('test', ''));
        $msg = trim((string)$request->input('msg', ''));

        return $this->view('system/settings/mail', array_merge($service->getMailSettings(), [
            'test_status' => $status,
            'test_message' => $msg,
        ]));
    }

    public function mailSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $service->saveMailSettings($_POST);

        return $this->redirect('/sys/settings/mail');
    }

    public function mailTest(Request $request)
    {
        $this->ensureSuperAdmin();

        $to = trim((string)$request->input('to', ''));
        if ($to === '') {
            return $this->redirect('/sys/settings/mail');
        }

        try {
            (new MailerService($this->container))->send($to, $to, 'Teste de E-mail - LumiClinic', '<div style="font-family:Arial,sans-serif">Teste de envio SMTP.</div>');
        } catch (\Throwable $e) {
            return $this->redirect('/sys/settings/mail?test=fail&msg=' . urlencode('Falha ao enviar: ' . $e->getMessage()));
        }

        return $this->redirect('/sys/settings/mail?test=ok&msg=' . urlencode('E-mail de teste enviado para ' . $to));
    }
}
