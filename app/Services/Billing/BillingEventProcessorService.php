<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\Container\Container;
use App\Repositories\ClinicSubscriptionRepository;

final class BillingEventProcessorService
{
    public function __construct(private readonly Container $container) {}

    /** @param array<string,mixed> $event */
    public function process(array $event): void
    {
        $provider = (string)($event['provider'] ?? '');

        if ($provider === 'asaas') {
            $this->processAsaas($event);
            return;
        }

        if ($provider === 'mercadopago') {
            $this->processMercadoPago($event);
            return;
        }
    }

    /** @param array<string,mixed> $event */
    private function processAsaas(array $event): void
    {
        $payload = $this->decodePayload($event);

        $externalSubId = null;
        if (isset($payload['subscription']) && is_string($payload['subscription'])) {
            $externalSubId = $payload['subscription'];
        } elseif (isset($payload['payment']['subscription']) && is_string($payload['payment']['subscription'])) {
            $externalSubId = $payload['payment']['subscription'];
        }

        if ($externalSubId === null || trim($externalSubId) === '') {
            return;
        }

        $status = $this->mapAsaasStatus($payload);
        if ($status === null) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        $subsRepo = new ClinicSubscriptionRepository($pdo);
        $sub = $subsRepo->findByAsaasSubscriptionId($externalSubId);
        if ($sub === null) {
            return;
        }

        $clinicId = (int)$sub['clinic_id'];

        if ($status === 'past_due') {
            $subsRepo->updateStatus($clinicId, 'past_due', (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'));
            return;
        }

        if ($status === 'active') {
            $subsRepo->updateStatus($clinicId, 'active', null);
            return;
        }

        if ($status === 'canceled') {
            $subsRepo->updateStatus($clinicId, 'canceled', null);
            return;
        }
    }

    /** @param array<string,mixed> $event */
    private function processMercadoPago(array $event): void
    {
        $payload = $this->decodePayload($event);

        $preapprovalId = null;
        if (isset($payload['preapproval_id']) && is_string($payload['preapproval_id'])) {
            $preapprovalId = $payload['preapproval_id'];
        } elseif (isset($payload['data']['id']) && is_string($payload['data']['id'])) {
            $preapprovalId = $payload['data']['id'];
        }

        if ($preapprovalId === null || trim($preapprovalId) === '') {
            return;
        }

        $status = $this->mapMpStatus($payload);
        if ($status === null) {
            return;
        }

        $pdo = $this->container->get(\PDO::class);
        $subsRepo = new ClinicSubscriptionRepository($pdo);
        $sub = $subsRepo->findByMpPreapprovalId($preapprovalId);
        if ($sub === null) {
            return;
        }

        $clinicId = (int)$sub['clinic_id'];

        if ($status === 'past_due') {
            $subsRepo->updateStatus($clinicId, 'past_due', (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'));
            return;
        }

        if ($status === 'active') {
            $subsRepo->updateStatus($clinicId, 'active', null);
            return;
        }

        if ($status === 'canceled') {
            $subsRepo->updateStatus($clinicId, 'canceled', null);
            return;
        }
    }

    /** @param array<string,mixed> $event */
    private function decodePayload(array $event): array
    {
        $raw = $event['payload_json'] ?? null;

        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string,mixed> $payload */
    private function mapAsaasStatus(array $payload): ?string
    {
        $eventType = (string)($payload['event'] ?? $payload['type'] ?? '');

        if (stripos($eventType, 'PAYMENT_OVERDUE') !== false) {
            return 'past_due';
        }

        if (stripos($eventType, 'PAYMENT_CONFIRMED') !== false || stripos($eventType, 'PAYMENT_RECEIVED') !== false) {
            return 'active';
        }

        if (stripos($eventType, 'SUBSCRIPTION_DELETED') !== false || stripos($eventType, 'SUBSCRIPTION_INACTIVATED') !== false) {
            return 'canceled';
        }

        return null;
    }

    /** @param array<string,mixed> $payload */
    private function mapMpStatus(array $payload): ?string
    {
        $type = (string)($payload['type'] ?? '');
        $action = (string)($payload['action'] ?? '');
        $topic = (string)($payload['topic'] ?? '');

        $hint = strtolower($type . ' ' . $action . ' ' . $topic);

        if (str_contains($hint, 'preapproval')) {
            if (str_contains($hint, 'cancel') || str_contains($hint, 'paused')) {
                return 'canceled';
            }

            if (str_contains($hint, 'authorized') || str_contains($hint, 'active')) {
                return 'active';
            }
        }

        if (str_contains($hint, 'payment')) {
            if (str_contains($hint, 'failed') || str_contains($hint, 'rejected')) {
                return 'past_due';
            }

            if (str_contains($hint, 'approved') || str_contains($hint, 'paid')) {
                return 'active';
            }
        }

        return null;
    }
}
