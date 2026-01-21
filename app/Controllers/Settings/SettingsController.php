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

        if ($timezone === '' || $language === '') {
            $service = new SettingsService($this->container);
            return $this->view('settings/index', [
                'settings' => $service->getSettings(),
                'error' => 'Preencha todos os campos.',
            ]);
        }

        $service = new SettingsService($this->container);
        $service->updateSettings($timezone, $language, $request->ip());

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
