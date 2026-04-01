<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Whatsapp\WhatsappManualSendService;

final class PatientWhatsappController extends Controller
{
    public function sendJson(Request $request): Response
    {
        $this->authorize('patients.read');

        $patientId = (int)$request->input('patient_id', 0);
        $templateCode = trim((string)$request->input('template_code', ''));

        if ($patientId <= 0 || $templateCode === '') {
            return Response::json(['ok' => false, 'error' => 'Parâmetros inválidos.'], 400);
        }

        try {
            $result = (new WhatsappManualSendService($this->container))
                ->send($patientId, $templateCode, $request->ip(), $request->header('user-agent'));
            return Response::json($result);
        } catch (\RuntimeException $e) {
            return Response::json(['ok' => false, 'error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'error' => 'Falha ao enviar mensagem.'], 500);
        }
    }
}
