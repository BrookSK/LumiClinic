<?php

declare(strict_types=1);

namespace App\Controllers\Billing;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\BillingEventRepository;
use App\Services\Queue\QueueService;

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

        $secret = '';
        if ($provider === 'asaas') {
            $secret = (string)($config['billing']['asaas']['webhook_secret'] ?? '');
        } elseif ($provider === 'mercadopago') {
            $secret = (string)($config['billing']['mercadopago']['webhook_secret'] ?? '');
        }

        $provided = $request->header('x-webhook-secret');
        if ($provided === null) {
            $provided = $request->header('x-signature');
        }

        if ($appEnv !== 'local') {
            if ($secret === '' || $provided === null || !hash_equals($secret, $provided)) {
                return Response::html('Invalid signature.', 401);
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
