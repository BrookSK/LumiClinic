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

    public function whatsapp(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $saved = trim((string)$request->input('saved', ''));
        $error = trim((string)$request->input('error', ''));

        return $this->view('system/settings/whatsapp', array_merge($service->getWhatsappSettings(), [
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
            'error' => $error !== '' ? $error : null,
        ]));
    }

    public function whatsappSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $baseUrl = trim((string)$request->input('evolution_base_url', ''));
        if ($baseUrl !== '' && !preg_match('#^https?://#i', $baseUrl)) {
            return $this->redirect('/sys/settings/whatsapp?error=' . urlencode('Base URL inválida. Use http:// ou https://'));
        }

        $token = trim((string)$request->input('evolution_token', ''));
        $clearToken = trim((string)$request->input('clear_evolution_token', '')) !== '';

        $payload = [
            'evolution_base_url' => $baseUrl === '' ? null : $baseUrl,
        ];
        if ($clearToken) {
            $payload['evolution_token'] = null;
        } elseif ($token !== '') {
            $payload['evolution_token'] = $token;
        }

        (new SystemSettingsService($this->container))->saveWhatsappSettings($payload);
        return $this->redirect('/sys/settings/whatsapp?saved=1');
    }

    public function webpush(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $saved = trim((string)$request->input('saved', ''));

        return $this->view('system/settings/webpush', array_merge($service->getWebPushSettings(), [
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
        ]));
    }

    public function webpushSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $service->saveWebPushSettings($_POST);

        return $this->redirect('/sys/settings/webpush?saved=1');
    }

    public function webpushGenerate(Request $request)
    {
        $this->ensureSuperAdmin();

        if (!class_exists('Minishlink\\WebPush\\VAPID')) {
            throw new \RuntimeException('Dependência ausente: instale minishlink/web-push via Composer para gerar chaves VAPID.');
        }

        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();

        $service = new SystemSettingsService($this->container);
        $existing = $service->getWebPushSettings();
        $subject = trim((string)($existing['webpush_subject'] ?? ''));
        if ($subject === '') {
            $subject = 'mailto:admin@example.com';
        }

        $service->saveWebPushSettings([
            'webpush_public_key' => (string)($keys['publicKey'] ?? ''),
            'webpush_private_key' => (string)($keys['privateKey'] ?? ''),
            'webpush_subject' => $subject,
        ]);

        return $this->redirect('/sys/settings/webpush?saved=1');
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

    public function devAlerts(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);

        return $this->view('system/settings/dev_alerts', [
            'dev_alert_emails' => (string)($service->getText('dev.alert_emails') ?? ''),
        ]);
    }

    public function devAlertsSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $val = trim((string)$request->input('dev_alert_emails', ''));
        (new SystemSettingsService($this->container))->setText('dev.alert_emails', $val === '' ? null : $val);

        return $this->redirect('/sys/settings/dev-alerts');
    }

    public function ai(Request $request)
    {
        $this->ensureSuperAdmin();

        $pdo = $this->container->get(\PDO::class);
        $service = new SystemSettingsService($this->container);
        $keySet = trim((string)($service->getText('ai.openai.global_api_key') ?? '')) !== '';
        $saved = trim((string)$request->input('saved', ''));
        $error = trim((string)$request->input('error', ''));
        $msg   = trim((string)$request->input('msg', ''));

        // Load wallet data
        $walletService = new \App\Services\Ai\AiWalletService($this->container);
        $wallet = $walletService->getOrCreate();
        $walletTransactions = $walletService->listTransactions(20);

        // Load billing settings
        $billingSettings = (new \App\Repositories\AiBillingSettingsRepository($pdo))->getOrCreate();

        // Load superadmin profile (name, email, phone, cpf from users table)
        $superadminProfile = [];
        try {
            $stmt = $pdo->prepare("SELECT name, email, phone, doc_number, postal_code, address_number FROM users WHERE is_super_admin = 1 LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            if (is_array($row)) {
                $superadminProfile = $row;
            }
        } catch (\Throwable $e) {
            // Non-critical — profile pre-fill is optional
        }

        return $this->view('system/settings/ai', [
            'key_set'             => $keySet,
            'success'             => $saved !== '' ? ($msg !== '' ? $msg : 'Salvo com sucesso.') : null,
            'error'               => $error !== '' ? $error : null,
            'wallet'              => $wallet,
            'wallet_transactions' => $walletTransactions,
            'billing_settings'    => $billingSettings,
            'superadmin_profile'  => $superadminProfile,
        ]);
    }

    public function aiSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $key = trim((string)$request->input('openai_api_key', ''));
        $clear = (string)$request->input('clear_key', '') === '1';

        $service = new SystemSettingsService($this->container);

        if ($clear) {
            $service->setText('ai.openai.global_api_key', null);
        } elseif ($key !== '') {
            $service->setText('ai.openai.global_api_key', $key);
        }

        return $this->redirect('/sys/settings/ai?saved=1');
    }

    public function serverRequirements(Request $request)
    {
        $this->ensureSuperAdmin();

        $service = new SystemSettingsService($this->container);
        $cronToken = trim((string)($service->getText('cron.secret_token') ?? ''));

        return $this->view('system/settings/server_requirements', [
            'cron_token' => $cronToken,
        ]);
    }

    public function serverSubmit(Request $request)
    {
        $this->ensureSuperAdmin();

        $cronToken = trim((string)$request->input('cron_token', ''));
        (new SystemSettingsService($this->container))->setText('cron.secret_token', $cronToken !== '' ? $cronToken : null);

        return $this->redirect('/sys/settings/server');
    }
}
