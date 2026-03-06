<?php

declare(strict_types=1);

namespace App\Controllers\Marketing;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Marketing\MarketingAutomationService;

final class MarketingAutomationController extends Controller
{
    public function click(Request $request): Response
    {
        $token = trim((string)$request->input('token', ''));
        $target = (new MarketingAutomationService($this->container))->click($token);

        if ($target === null) {
            return $this->redirect('/');
        }

        return Response::redirect($target);
    }

    public function whatsappWebhook(Request $request): Response
    {
        // MVP: endpoint simples para atualizar delivered/read.
        // Espera: clinic_id, provider_message_id, status
        $clinicId = (int)$request->input('clinic_id', 0);
        $providerMessageId = trim((string)$request->input('provider_message_id', ''));
        $status = trim((string)$request->input('status', ''));

        (new MarketingAutomationService($this->container))->updateProviderStatus($clinicId, $providerMessageId, $status);

        return Response::json(['ok' => true]);
    }
}
