<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Controllers\Controller;
use App\Core\Http\Request;
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
}
