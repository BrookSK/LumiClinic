<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\PatientWebpushSubscriptionRepository;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PortalWebPushService;

final class PortalPushController extends Controller
{
    private function meOrRedirect(): array
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        $patientUserId = $auth->patientUserId();
        if ($clinicId === null || $patientId === null || $patientUserId === null) {
            return [null, null, null];
        }
        return [(int)$clinicId, (int)$patientId, (int)$patientUserId];
    }

    public function config(Request $request): Response
    {
        [$clinicId] = $this->meOrRedirect();
        if ($clinicId === null) {
            return Response::json(['error' => 'unauthorized'], 401);
        }

        $cfg = (new PortalWebPushService($this->container))->config();
        return Response::json(['public_key' => $cfg['public_key'], 'subject' => $cfg['subject']], 200);
    }

    public function subscribe(Request $request): Response
    {
        [$clinicId, $patientId, $patientUserId] = $this->meOrRedirect();
        if ($clinicId === null || $patientId === null || $patientUserId === null) {
            return Response::json(['error' => 'unauthorized'], 401);
        }

        $raw = (string)file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return Response::json(['error' => 'invalid_json'], 422);
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientWebpushSubscriptionRepository($pdo);

        try {
            $id = $repo->upsert($clinicId, $patientId, $patientUserId, $data, $request->ip(), $request->header('user-agent'));

            (new AuditLogRepository($pdo))->log(null, $clinicId, 'portal.push.subscribe', ['patient_id' => $patientId, 'patient_user_id' => $patientUserId], $request->ip(), null, 'patient_user', $patientUserId, $request->header('user-agent'));

            return Response::json(['ok' => true, 'id' => $id], 200);
        } catch (\RuntimeException $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    public function unsubscribe(Request $request): Response
    {
        [$clinicId, $patientId, $patientUserId] = $this->meOrRedirect();
        if ($clinicId === null || $patientId === null || $patientUserId === null) {
            return Response::json(['error' => 'unauthorized'], 401);
        }

        $raw = (string)file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return Response::json(['error' => 'invalid_json'], 422);
        }

        $endpoint = trim((string)($data['endpoint'] ?? ''));
        if ($endpoint === '') {
            return Response::json(['error' => 'endpoint_required'], 422);
        }

        $pdo = $this->container->get(\PDO::class);
        (new PatientWebpushSubscriptionRepository($pdo))->softDeleteByEndpoint($clinicId, $patientUserId, $endpoint);
        (new AuditLogRepository($pdo))->log(null, $clinicId, 'portal.push.unsubscribe', ['patient_id' => $patientId, 'patient_user_id' => $patientUserId], $request->ip(), null, 'patient_user', $patientUserId, $request->header('user-agent'));

        return Response::json(['ok' => true], 200);
    }

    public function test(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        try {
            (new PortalWebPushService($this->container))->sendTest((int)$clinicId, (int)$patientId, 'Notificação de teste', 'Push do Portal do Paciente está funcionando.');
            return $this->redirect('/portal/notificacoes?success=' . urlencode('Push de teste enviado (se permitido no navegador).'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/portal/notificacoes?error=' . urlencode($e->getMessage()));
        }
    }
}
