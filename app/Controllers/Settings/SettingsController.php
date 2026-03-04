<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Ai\AiConfigService;
use App\Services\Ai\OpenAiClient;
use App\Repositories\WhatsappTemplateRepository;
use App\Repositories\WhatsappMessageLogQueryRepository;
use App\Services\Settings\SettingsService;
use App\Services\Auth\AuthService;
use App\Services\Whatsapp\WhatsappConfigService;
use App\Services\Whatsapp\ZapiClient;

final class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('settings.read');

        $service = new SettingsService($this->container);

        return $this->view('settings/index', [
            'settings' => $service->getSettings(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('settings.update');

        $timezone = trim((string)$request->input('timezone', ''));
        $language = trim((string)$request->input('language', ''));
        $weekStartWeekday = (int)$request->input('week_start_weekday', 1);
        $weekEndWeekday = (int)$request->input('week_end_weekday', 0);

        if ($timezone === '' || $language === '') {
            $service = new SettingsService($this->container);
            return $this->view('settings/index', [
                'settings' => $service->getSettings(),
                'error' => 'Preencha todos os campos.',
            ]);
        }

        if ($weekStartWeekday < 0 || $weekStartWeekday > 6 || $weekEndWeekday < 0 || $weekEndWeekday > 6) {
            $service = new SettingsService($this->container);
            return $this->view('settings/index', [
                'settings' => $service->getSettings(),
                'error' => 'Semana inválida.',
            ]);
        }

        $expectedEnd = ($weekStartWeekday + 6) % 7;
        if ($weekEndWeekday !== $expectedEnd) {
            $service = new SettingsService($this->container);
            return $this->view('settings/index', [
                'settings' => $service->getSettings(),
                'error' => 'O fim da semana deve ser o dia anterior ao início (ex.: início Seg => fim Dom).',
            ]);
        }

        $service = new SettingsService($this->container);
        $service->updateSettings($timezone, $language, $weekStartWeekday, $weekEndWeekday, $request->ip());

        return $this->redirect('/settings');
    }

    public function terminology(Request $request)
    {
        $this->authorize('settings.read');

        $service = new SettingsService($this->container);

        return $this->view('settings/terminology', [
            'terminology' => $service->getTerminology(),
        ]);
    }

    public function updateTerminology(Request $request)
    {
        $this->authorize('settings.update');

        $patient = trim((string)$request->input('patient_label', ''));
        $appointment = trim((string)$request->input('appointment_label', ''));
        $professional = trim((string)$request->input('professional_label', ''));

        if ($patient === '' || $appointment === '' || $professional === '') {
            $service = new SettingsService($this->container);
            return $this->view('settings/terminology', [
                'terminology' => $service->getTerminology(),
                'error' => 'Preencha todos os campos.',
            ]);
        }

        $service = new SettingsService($this->container);
        $service->updateTerminology($patient, $appointment, $professional, $request->ip());

        return $this->redirect('/settings/terminology');
    }

    public function ai(Request $request)
    {
        $this->authorize('settings.read');

        $svc = new AiConfigService($this->container);
        $data = $svc->getAiSettings();

        $saved = trim((string)$request->input('saved', ''));
        return $this->view('settings/ai', [
            'openai_key_set' => (bool)($data['openai_key_set'] ?? false),
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
        ]);
    }

    public function aiUpdate(Request $request)
    {
        $this->authorize('settings.update');

        $key = (string)$request->input('openai_api_key', '');
        $key = trim($key);
        if ($key === '') {
            $svc = new AiConfigService($this->container);
            $data = $svc->getAiSettings();
            return $this->view('settings/ai', [
                'openai_key_set' => (bool)($data['openai_key_set'] ?? false),
                'error' => 'Informe a chave para salvar.',
            ]);
        }

        (new AiConfigService($this->container))->setOpenAiApiKey($key, $request->ip());
        return $this->redirect('/settings/ai?saved=1');
    }

    public function aiTest(Request $request)
    {
        $this->authorize('settings.update');

        $svc = new AiConfigService($this->container);
        $data = $svc->getAiSettings();

        try {
            (new OpenAiClient($this->container))->chatCompletions('gpt-4o-mini', [
                ['role' => 'system', 'content' => 'Você é um verificador de conectividade. Responda apenas com OK.'],
                ['role' => 'user', 'content' => 'OK'],
            ], 0.0);

            return $this->view('settings/ai', [
                'openai_key_set' => (bool)($data['openai_key_set'] ?? false),
                'success' => 'Conexão com a OpenAI OK.',
            ]);
        } catch (\Throwable $e) {
            return $this->view('settings/ai', [
                'openai_key_set' => (bool)($data['openai_key_set'] ?? false),
                'error' => 'Falha ao testar IA. Verifique a chave e tente novamente.',
            ]);
        }
    }

    public function aiClear(Request $request)
    {
        $this->authorize('settings.update');

        (new AiConfigService($this->container))->setOpenAiApiKey(null, $request->ip());
        return $this->redirect('/settings/ai?saved=1');
    }

    public function whatsapp(Request $request)
    {
        $this->authorize('settings.read');

        $svc = new WhatsappConfigService($this->container);
        $data = $svc->getWhatsappSettings();

        $saved = trim((string)$request->input('saved', ''));
        return $this->view('settings/whatsapp', [
            'zapi_instance_id' => $data['zapi_instance_id'] ?? null,
            'zapi_token_set' => (bool)($data['zapi_token_set'] ?? false),
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
        ]);
    }

    public function whatsappUpdate(Request $request)
    {
        $this->authorize('settings.update');

        $instanceId = trim((string)$request->input('zapi_instance_id', ''));
        $token = trim((string)$request->input('zapi_token', ''));

        if ($instanceId === '' || $token === '') {
            $svc = new WhatsappConfigService($this->container);
            $data = $svc->getWhatsappSettings();
            return $this->view('settings/whatsapp', [
                'zapi_instance_id' => $data['zapi_instance_id'] ?? null,
                'zapi_token_set' => (bool)($data['zapi_token_set'] ?? false),
                'error' => 'Informe a instância e o token para salvar.',
            ]);
        }

        (new WhatsappConfigService($this->container))->setZapiConfig($instanceId, $token, $request->ip());
        return $this->redirect('/settings/whatsapp?saved=1');
    }

    public function whatsappTest(Request $request)
    {
        $this->authorize('settings.update');

        $svc = new WhatsappConfigService($this->container);
        $data = $svc->getWhatsappSettings();

        try {
            $ok = (new ZapiClient($this->container))->instanceStatus();
            return $this->view('settings/whatsapp', [
                'zapi_instance_id' => $data['zapi_instance_id'] ?? null,
                'zapi_token_set' => (bool)($data['zapi_token_set'] ?? false),
                'success' => $ok ? 'Conexão com Z-API OK.' : 'Falha ao testar WhatsApp.',
            ]);
        } catch (\Throwable $e) {
            return $this->view('settings/whatsapp', [
                'zapi_instance_id' => $data['zapi_instance_id'] ?? null,
                'zapi_token_set' => (bool)($data['zapi_token_set'] ?? false),
                'error' => 'Falha ao testar WhatsApp. Verifique as credenciais e tente novamente.',
            ]);
        }
    }

    public function whatsappClear(Request $request)
    {
        $this->authorize('settings.update');

        (new WhatsappConfigService($this->container))->clearZapiConfig($request->ip());
        return $this->redirect('/settings/whatsapp?saved=1');
    }

    public function whatsappDiagnose(Request $request)
    {
        $this->authorize('settings.update');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/sys/clinics');
        }

        $svc = new WhatsappConfigService($this->container);
        $data = $svc->getWhatsappSettings();

        $instanceId = (string)($data['zapi_instance_id'] ?? '');
        $tokenSet = (bool)($data['zapi_token_set'] ?? false);

        $checks = [];

        $isConfigured = trim($instanceId) !== '' && $tokenSet;
        $checks[] = [
            'title' => 'Configuração da Z-API',
            'ok' => $isConfigured,
            'message' => $isConfigured
                ? 'Credenciais configuradas.'
                : 'Preencha o instance id e o token e clique em Salvar.',
            'action_label' => $isConfigured ? null : 'Ir para configuração',
            'action_url' => $isConfigured ? null : '/settings/whatsapp',
        ];

        $zapiOk = false;
        if ($isConfigured) {
            try {
                $zapiOk = (new ZapiClient($this->container))->instanceStatus();
            } catch (\Throwable $e) {
                $zapiOk = false;
            }
        }
        $checks[] = [
            'title' => 'Conexão com a Z-API',
            'ok' => $isConfigured ? $zapiOk : false,
            'message' => !$isConfigured
                ? 'Configure as credenciais antes de testar a conexão.'
                : ($zapiOk ? 'Conexão OK.' : 'Falha ao conectar. Verifique credenciais e status da instância.'),
            'action_label' => $isConfigured ? 'Abrir configurações' : null,
            'action_url' => $isConfigured ? '/settings/whatsapp' : null,
        ];

        $pdo = $this->container->get(\PDO::class);
        $tplRepo = new WhatsappTemplateRepository($pdo);
        $tpl24 = $tplRepo->findByCode($clinicId, 'reminder_24h');
        $tpl2 = $tplRepo->findByCode($clinicId, 'reminder_2h');

        $tpl24Ok = $tpl24 !== null && (string)($tpl24['status'] ?? 'active') === 'active';
        $tpl2Ok = $tpl2 !== null && (string)($tpl2['status'] ?? 'active') === 'active';
        $checks[] = [
            'title' => 'Templates de lembrete',
            'ok' => $tpl24Ok && $tpl2Ok,
            'message' => ($tpl24Ok && $tpl2Ok)
                ? 'Templates 24h e 2h estão ativos.'
                : 'Ative/crie os templates reminder_24h e reminder_2h.',
            'action_label' => 'Abrir templates',
            'action_url' => '/whatsapp-templates',
        ];

        $failedCount = 0;
        try {
            $q = new WhatsappMessageLogQueryRepository($pdo);
            $rows = $q->search($clinicId, ['status' => 'failed', 'from' => date('Y-m-d', strtotime('-7 day')), 'to' => date('Y-m-d')], 51, 0);
            $failedCount = count($rows);
        } catch (\Throwable $e) {
            $failedCount = 0;
        }

        $checks[] = [
            'title' => 'Falhas recentes (últimos 7 dias)',
            'ok' => $failedCount === 0,
            'message' => $failedCount === 0
                ? 'Nenhuma falha recente.'
                : 'Encontramos falhas recentes. Você pode clicar em "Tentar enviar novamente" nos logs.',
            'action_label' => 'Abrir logs',
            'action_url' => '/whatsapp-logs',
        ];

        return $this->view('settings/whatsapp', [
            'zapi_instance_id' => $data['zapi_instance_id'] ?? null,
            'zapi_token_set' => (bool)($data['zapi_token_set'] ?? false),
            'diagnose' => [
                'checks' => $checks,
            ],
            'success' => 'Diagnóstico concluído. Veja os itens abaixo.',
        ]);
    }
}
