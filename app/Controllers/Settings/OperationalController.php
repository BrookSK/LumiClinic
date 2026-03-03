<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Settings\OperationalConfigService;

final class OperationalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('settings.read');

        $svc = new OperationalConfigService($this->container);
        $all = $svc->listAll();

        return $this->view('settings/operational', [
            'stages' => $all['stages'],
            'lost_reasons' => $all['lost_reasons'],
            'origins' => $all['origins'],
            'error' => trim((string)$request->input('error', '')),
            'saved' => trim((string)$request->input('saved', '')),
        ]);
    }

    public function createFunnelStage(Request $request)
    {
        $this->authorize('settings.update');

        $name = trim((string)$request->input('name', ''));
        $sort = (int)$request->input('sort_order', 0);

        try {
            (new OperationalConfigService($this->container))->createFunnelStage($name, $sort, $request->ip());
            return $this->redirect('/settings/operational?saved=1');
        } catch (\RuntimeException $e) {
            return $this->redirect('/settings/operational?error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteFunnelStage(Request $request)
    {
        $this->authorize('settings.update');

        $id = (int)$request->input('id', 0);

        try {
            (new OperationalConfigService($this->container))->deleteFunnelStage($id, $request->ip());
            return $this->redirect('/settings/operational?saved=1');
        } catch (\RuntimeException $e) {
            return $this->redirect('/settings/operational?error=' . urlencode($e->getMessage()));
        }
    }

    public function createLostReason(Request $request)
    {
        $this->authorize('settings.update');

        $name = trim((string)$request->input('name', ''));
        $sort = (int)$request->input('sort_order', 0);

        try {
            (new OperationalConfigService($this->container))->createLostReason($name, $sort, $request->ip());
            return $this->redirect('/settings/operational?saved=1');
        } catch (\RuntimeException $e) {
            return $this->redirect('/settings/operational?error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteLostReason(Request $request)
    {
        $this->authorize('settings.update');

        $id = (int)$request->input('id', 0);

        try {
            (new OperationalConfigService($this->container))->deleteLostReason($id, $request->ip());
            return $this->redirect('/settings/operational?saved=1');
        } catch (\RuntimeException $e) {
            return $this->redirect('/settings/operational?error=' . urlencode($e->getMessage()));
        }
    }

    public function createPatientOrigin(Request $request)
    {
        $this->authorize('settings.update');

        $name = trim((string)$request->input('name', ''));
        $sort = (int)$request->input('sort_order', 0);

        try {
            (new OperationalConfigService($this->container))->createPatientOrigin($name, $sort, $request->ip());
            return $this->redirect('/settings/operational?saved=1');
        } catch (\RuntimeException $e) {
            return $this->redirect('/settings/operational?error=' . urlencode($e->getMessage()));
        }
    }

    public function deletePatientOrigin(Request $request)
    {
        $this->authorize('settings.update');

        $id = (int)$request->input('id', 0);

        try {
            (new OperationalConfigService($this->container))->deletePatientOrigin($id, $request->ip());
            return $this->redirect('/settings/operational?saved=1');
        } catch (\RuntimeException $e) {
            return $this->redirect('/settings/operational?error=' . urlencode($e->getMessage()));
        }
    }
}
