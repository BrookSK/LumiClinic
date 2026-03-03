<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Ai\AiConfigService;
use App\Services\Ai\OpenAiClient;
use App\Services\Settings\SettingsService;

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
}
