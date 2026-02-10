<?php

declare(strict_types=1);

namespace App\Controllers\Billing;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\BillingEventRepository;
use App\Services\Queue\QueueService;
use App\Services\System\SystemSettingsService;

final class WebhookController
{
    public function __construct(private readonly Container $container) {}

    public function asaas(Request $request): Response
    {
        return $this->handleWebhook($request, 'asaas');
    }

    public function mercadopago(Request $request): Response
    {
        return $this->handleWebhook($request, 'mercadopago');
    }

    private function handleWebhook(Request $request, string $provider): Response
    {
        $config = $this->container->get('config');
        $appEnv = (string)($config['app']['env'] ?? 'local');

        $settings = new SystemSettingsService($this->container);

        if ($appEnv !== 'local') {
            if ($provider === 'asaas') {
                $secret = (string)($settings->getText('billing.asaas.webhook_secret') ?? (string)($config['billing']['asaas']['webhook_secret'] ?? ''));
                $provided = $request->header('x-webhook-secret');
                if ($secret === '' || $provided === null || !hash_equals($secret, $provided)) {
                    return Response::html('Invalid signature.', 401);
                }
            }

            if ($provider === 'mercadopago') {
                $secret = (string)($settings->getText('billing.mercadopago.webhook_secret') ?? (string)($config['billing']['mercadopago']['webhook_secret'] ?? ''));
                if ($secret === '') {
                    return Response::html('Invalid signature.', 401);
                }

                $xSignature = $request->header('x-signature');
                $xRequestId = $request->header('x-request-id');
                if ($xSignature === null || $xRequestId === null) {
                    return Response::html('Invalid signature.', 401);
                }

                $ts = null;
                $v1 = null;
                foreach (explode(',', (string)$xSignature) as $part) {
                    $part = trim($part);
                    if (str_starts_with($part, 'ts=')) {
                        $ts = substr($part, 3);
                    } elseif (str_starts_with($part, 'v1=')) {
                        $v1 = substr($part, 3);
                    }
                }

                $dataId = (string)($request->input('data.id', '') ?? '');
                if ($dataId === '') {
                    $dataId = (string)($request->input('data_id', '') ?? '');
                }
                if ($dataId === '') {
                    $dataId = (string)($request->input('id', '') ?? '');
                }
                $dataId = trim($dataId);
                if ($dataId !== '' && preg_match('/^[a-z0-9]+$/i', $dataId)) {
                    $dataId = strtolower($dataId);
                }

                if ($ts === null || $v1 === null || $dataId === '') {
                    return Response::html('Invalid signature.', 401);
                }

                $template = 'id:' . $dataId . ';request-id:' . $xRequestId . ';ts:' . $ts . ';';
                $expected = hash_hmac('sha256', $template, $secret);
                if (!hash_equals($expected, $v1)) {
                    return Response::html('Invalid signature.', 401);
                }
            }
        }

        $raw = file_get_contents('php://input');
        if ($raw === false) {
            $raw = '';
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $eventType = (string)($payload['event'] ?? $payload['type'] ?? 'webhook');
        $externalId = null;
        if (isset($payload['id'])) {
            $externalId = (string)$payload['id'];
        } elseif (isset($payload['event']['id'])) {
            $externalId = (string)$payload['event']['id'];
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new BillingEventRepository($pdo);

        $clinicId = null;
        $eventId = $repo->create($clinicId, $provider, $eventType, $externalId, $payload);

        (new QueueService($this->container))->enqueue('billing.process_event', ['billing_event_id' => $eventId], null, 'default');

        return Response::json(['ok' => true]);
    }
}
