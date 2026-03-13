<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\WhatsappMessageLogQueryRepository;
use App\Repositories\WhatsappTemplateRepository;
use App\Services\Ai\AiConfigService;
use App\Services\Ai\OpenAiClient;
use App\Services\Auth\AuthService;
use App\Services\Settings\SettingsService;
use App\Services\Anamnesis\AnamnesisService;
use App\Services\Whatsapp\EvolutionClient;
use App\Services\Whatsapp\WhatsappConfigService;
use App\Services\System\SystemSettingsService;
use App\Services\System\SystemErrorLogService;

final class SettingsController extends Controller
{
    private function isWhatsappGlobalConfigured(): bool
    {
        $svc = new SystemSettingsService($this->container);
        $baseUrl = trim((string)($svc->getText('whatsapp.evolution.base_url') ?? ''));
        $token = trim((string)($svc->getText('whatsapp.evolution.token') ?? ''));
        return $baseUrl !== '' && $token !== '';
    }

    public function index(Request $request)
    {
        $this->authorize('settings.read');

        $service = new SettingsService($this->container);

        $anamnesisTemplates = [];
        try {
            $anamnesisTemplates = (new AnamnesisService($this->container))->listTemplates();
        } catch (\Throwable $e) {
            $anamnesisTemplates = [];
        }

        return $this->view('settings/index', [
            'settings' => $service->getSettings(),
            'anamnesis_templates' => $anamnesisTemplates,
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('settings.update');

        $timezone = trim((string)$request->input('timezone', ''));
        $language = trim((string)$request->input('language', ''));
        $weekStartWeekday = (int)$request->input('week_start_weekday', 1);
        $weekEndWeekday = (int)$request->input('week_end_weekday', 0);
        $anamnesisDefaultTemplateId = (int)$request->input('anamnesis_default_template_id', 0);

        if ($timezone === '' || $language === '') {
            $service = new SettingsService($this->container);
            $anamnesisTemplates = [];
            try {
                $anamnesisTemplates = (new AnamnesisService($this->container))->listTemplates();
            } catch (\Throwable $e) {
                $anamnesisTemplates = [];
            }
            return $this->view('settings/index', [
                'settings' => $service->getSettings(),
                'anamnesis_templates' => $anamnesisTemplates,
                'error' => 'Preencha todos os campos.',
            ]);
        }

        if ($weekStartWeekday < 0 || $weekStartWeekday > 6 || $weekEndWeekday < 0 || $weekEndWeekday > 6) {
            $service = new SettingsService($this->container);
            $anamnesisTemplates = [];
            try {
                $anamnesisTemplates = (new AnamnesisService($this->container))->listTemplates();
            } catch (\Throwable $e) {
                $anamnesisTemplates = [];
            }
            return $this->view('settings/index', [
                'settings' => $service->getSettings(),
                'anamnesis_templates' => $anamnesisTemplates,
                'error' => 'Semana inválida.',
            ]);
        }

        $expectedEnd = ($weekStartWeekday + 6) % 7;
        if ($weekEndWeekday !== $expectedEnd) {
            $service = new SettingsService($this->container);
            $anamnesisTemplates = [];
            try {
                $anamnesisTemplates = (new AnamnesisService($this->container))->listTemplates();
            } catch (\Throwable $e) {
                $anamnesisTemplates = [];
            }
            return $this->view('settings/index', [
                'settings' => $service->getSettings(),
                'anamnesis_templates' => $anamnesisTemplates,
                'error' => 'O fim da semana deve ser o dia anterior ao início (ex.: início Seg => fim Dom).',
            ]);
        }

        $service = new SettingsService($this->container);
        $service->updateSettings(
            $timezone,
            $language,
            $weekStartWeekday,
            $weekEndWeekday,
            ($anamnesisDefaultTemplateId > 0 ? $anamnesisDefaultTemplateId : null),
            $request->ip()
        );

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

    public function whatsappConnect(Request $request)
    {
        $this->authorize('settings.update');

        $globalConfigured = $this->isWhatsappGlobalConfigured();
        if (!$globalConfigured) {
            return $this->redirect('/settings/whatsapp?error=' . urlencode('O WhatsApp ainda não foi configurado pelo administrador do sistema.'));
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/settings');
        }

        $svc = new WhatsappConfigService($this->container);
        $data = $svc->getWhatsappSettings();
        $instance = $data['evolution_instance'] ?? null;
        $instance = $instance === null ? '' : trim((string)$instance);

        if ($instance === '') {
            $instance = 'lc-' . $clinicId;
            try {
                $svc->setEvolutionInstance($instance, $request->ip());
                $data = $svc->getWhatsappSettings();
            } catch (\Throwable $e) {
                return $this->view('settings/whatsapp', [
                    'evolution_instance' => $data['evolution_instance'] ?? null,
                    'evolution_apikey_set' => (bool)($data['evolution_apikey_set'] ?? false),
                    'global_configured' => $globalConfigured,
                    'error' => 'Falha ao preparar instância do WhatsApp. Tente novamente.',
                ]);
            }
        }

        try {
            $conn = (new EvolutionClient($this->container))->connectInstance($instance);
        } catch (\Throwable $e) {
            $requestId = '';
            try {
                $requestId = bin2hex(random_bytes(8));
            } catch (\Throwable $ignore) {
                $requestId = (string)time();
            }

            (new SystemErrorLogService($this->container))->logHttpError(
                $request,
                502,
                'whatsapp.evolution.connect',
                $e->getMessage(),
                $e,
                [
                    'request_id' => $requestId,
                    'clinic_id' => $clinicId,
                    'instance' => $instance,
                ]
            );

            return $this->view('settings/whatsapp', [
                'evolution_instance' => $data['evolution_instance'] ?? null,
                'evolution_apikey_set' => (bool)($data['evolution_apikey_set'] ?? false),
                'global_configured' => $globalConfigured,
                'error' => $e->getMessage() . ' (Ref: ' . $requestId . ')',
            ]);
        }

        return $this->view('settings/whatsapp', [
            'evolution_instance' => $data['evolution_instance'] ?? null,
            'evolution_apikey_set' => (bool)($data['evolution_apikey_set'] ?? false),
            'global_configured' => $globalConfigured,
            'connect_data' => $conn,
            'success' => 'QR Code gerado. Escaneie com o WhatsApp para conectar.',
        ]);
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

        $globalConfigured = $this->isWhatsappGlobalConfigured();

        $svc = new WhatsappConfigService($this->container);
        $data = $svc->getWhatsappSettings();

        $saved = trim((string)$request->input('saved', ''));
        return $this->view('settings/whatsapp', [
            'evolution_instance' => $data['evolution_instance'] ?? null,
            'evolution_apikey_set' => (bool)($data['evolution_apikey_set'] ?? false),
            'global_configured' => $globalConfigured,
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
        ]);
    }

    public function whatsappUpdate(Request $request)
    {
        $this->authorize('settings.update');

        if ($this->isWhatsappGlobalConfigured()) {
            return $this->redirect('/settings/whatsapp?error=' . urlencode('O WhatsApp está configurado pelo administrador do sistema.'));
        }

        $instance = trim((string)$request->input('evolution_instance', ''));
        $apiKey = trim((string)$request->input('evolution_apikey', ''));

        if ($instance === '' || $apiKey === '') {
            $svc = new WhatsappConfigService($this->container);
            $data = $svc->getWhatsappSettings();
            return $this->view('settings/whatsapp', [
                'evolution_instance' => $data['evolution_instance'] ?? null,
                'evolution_apikey_set' => (bool)($data['evolution_apikey_set'] ?? false),
                'error' => 'Informe a instância e a apikey para salvar.',
            ]);
        }

        (new WhatsappConfigService($this->container))->setEvolutionConfig($instance, $apiKey, $request->ip());
        return $this->redirect('/settings/whatsapp?saved=1');
    }

    public function whatsappTest(Request $request)
    {
        $this->authorize('settings.update');

        $svc = new WhatsappConfigService($this->container);
        $data = $svc->getWhatsappSettings();

        try {
            $ok = (new EvolutionClient($this->container))->instanceStatus();
            return $this->view('settings/whatsapp', [
                'evolution_instance' => $data['evolution_instance'] ?? null,
                'evolution_apikey_set' => (bool)($data['evolution_apikey_set'] ?? false),
                'success' => $ok ? 'Conexão com Evolution API OK.' : 'Falha ao testar WhatsApp.',
            ]);
        } catch (\Throwable $e) {
            return $this->view('settings/whatsapp', [
                'evolution_instance' => $data['evolution_instance'] ?? null,
                'evolution_apikey_set' => (bool)($data['evolution_apikey_set'] ?? false),
                'error' => 'Falha ao testar WhatsApp. Verifique as credenciais e tente novamente.',
            ]);
        }
    }

    public function whatsappClear(Request $request)
    {
        $this->authorize('settings.update');

        if ($this->isWhatsappGlobalConfigured()) {
            return $this->redirect('/settings/whatsapp?error=' . urlencode('O WhatsApp está configurado pelo administrador do sistema.'));
        }

        (new WhatsappConfigService($this->container))->clearEvolutionConfig($request->ip());
        return $this->redirect('/settings/whatsapp?saved=1');
    }

    public function whatsappDiagnose(Request $request)
    {
        $this->authorize('settings.update');

        $globalConfigured = $this->isWhatsappGlobalConfigured();

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/sys/clinics');
        }

        $svc = new WhatsappConfigService($this->container);
        $data = $svc->getWhatsappSettings();

        $instance = (string)($data['evolution_instance'] ?? '');
        $apiKeySet = (bool)($data['evolution_apikey_set'] ?? false);

        $checks = [];

        $isConfigured = $globalConfigured || (trim($instance) !== '' && $apiKeySet);
        $checks[] = [
            'title' => 'Configuração da Evolution API',
            'ok' => $isConfigured,
            'message' => $isConfigured
                ? 'Credenciais configuradas.'
                : 'A configuração deve ser feita pelo administrador do sistema.',
            'action_label' => $isConfigured ? null : 'Ir para configuração',
            'action_url' => $isConfigured ? null : '/settings/whatsapp',
        ];

        $evoOk = false;
        if ($isConfigured) {
            try {
                $evoOk = (new EvolutionClient($this->container))->instanceStatus();
            } catch (\Throwable $e) {
                $evoOk = false;
            }
        }
        $checks[] = [
            'title' => 'Conexão com a Evolution API',
            'ok' => $isConfigured ? $evoOk : false,
            'message' => !$isConfigured
                ? 'Configure as credenciais antes de testar a conexão.'
                : ($evoOk ? 'Conexão OK.' : 'Falha ao conectar. Verifique credenciais e status da instância.'),
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
            'evolution_instance' => $data['evolution_instance'] ?? null,
            'evolution_apikey_set' => (bool)($data['evolution_apikey_set'] ?? false),
            'global_configured' => $globalConfigured,
            'diagnose' => [
                'checks' => $checks,
            ],
            'success' => 'Diagnóstico concluído. Veja os itens abaixo.',
        ]);
    }
}
