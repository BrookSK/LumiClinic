<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Container\Container;
use App\Repositories\AiBillingSettingsRepository;
use App\Services\Security\SystemCryptoService;

/**
 * HTTP client for the developer's Asaas account (separate from the clinic-billing Asaas account).
 * Used exclusively for charging the superadmin's credit card for AI wallet top-ups.
 */
final class AsaasAiClient
{
    private const BASE_URL = 'https://api.asaas.com/v3';

    public function __construct(private readonly Container $container) {}

    private function apiKey(): string
    {
        $repo = new AiBillingSettingsRepository($this->container->get(\PDO::class));
        $settings = $repo->getOrCreate();
        $encrypted = trim((string)($settings['asaas_api_key_encrypted'] ?? ''));

        if ($encrypted === '') {
            throw new \RuntimeException('Chave Asaas do desenvolvedor não configurada.');
        }

        return (new SystemCryptoService($this->container))->decrypt($encrypted);
    }

    /**
     * @param array<string,string|null> $body
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        $apiKey = $this->apiKey();
        $url = self::BASE_URL . $path;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'access_token: ' . $apiKey,
                'User-Agent: LumiClinic/1.0',
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \RuntimeException('Erro de conexão com Asaas: ' . $curlError);
        }

        $data = json_decode((string)$response, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Resposta inválida da Asaas (HTTP ' . $httpCode . ').');
        }

        if ($httpCode >= 400) {
            $errors = $data['errors'] ?? [];
            $msg = is_array($errors) && !empty($errors)
                ? (string)($errors[0]['description'] ?? 'Erro desconhecido')
                : 'Erro Asaas (HTTP ' . $httpCode . ')';
            throw new \RuntimeException($msg);
        }

        return $data;
    }

    /**
     * Creates or retrieves a customer in the developer's Asaas account.
     * @return array<string,mixed>
     */
    public function createCustomer(
        string $name,
        ?string $email,
        ?string $cpfCnpj,
        ?string $phone
    ): array {
        $body = array_filter([
            'name'     => $name,
            'email'    => $email,
            'cpfCnpj'  => $cpfCnpj ? preg_replace('/\D/', '', $cpfCnpj) : null,
            'phone'    => $phone ? preg_replace('/\D/', '', $phone) : null,
        ], fn($v) => $v !== null && $v !== '');

        return $this->request('POST', '/customers', $body);
    }

    /**
     * Tokenizes a credit card for a given customer.
     * Returns ['creditCardToken' => string, 'creditCardNumber' => '****XXXX'].
     * Property 5: Never stores raw card data — only the token is persisted.
     *
     * @param array<string,string> $card   Keys: holderName, number, expiryMonth, expiryYear, ccv
     * @param array<string,string> $holder Keys: name, email, cpfCnpj, postalCode, addressNumber, phone
     * @return array<string,mixed>
     */
    public function tokenizeCard(string $customerId, array $card, array $holder, string $remoteIp): array
    {
        $body = [
            'customer'   => $customerId,
            'creditCard' => [
                'holderName'  => $card['holderName'] ?? '',
                'number'      => $card['number'] ?? '',
                'expiryMonth' => $card['expiryMonth'] ?? '',
                'expiryYear'  => $card['expiryYear'] ?? '',
                'ccv'         => $card['ccv'] ?? '',
            ],
            'creditCardHolderInfo' => [
                'name'          => $holder['name'] ?? '',
                'email'         => $holder['email'] ?? '',
                'cpfCnpj'       => $holder['cpfCnpj'] ?? '',
                'postalCode'    => $holder['postalCode'] ?? '',
                'addressNumber' => $holder['addressNumber'] ?? '',
                'phone'         => $holder['phone'] ?? '',
            ],
            'remoteIp' => $remoteIp,
        ];

        return $this->request('POST', '/credit_cards/tokenize', $body);
    }

    /**
     * Creates a one-time credit card charge using a stored token.
     * @return array<string,mixed>  Includes 'id' (payment_id) and 'status'
     */
    public function createCharge(
        string $customerId,
        string $cardToken,
        float $value,
        string $description
    ): array {
        $body = [
            'customer'        => $customerId,
            'billingType'     => 'CREDIT_CARD',
            'value'           => round($value, 2),
            'dueDate'         => date('Y-m-d'),
            'description'     => $description,
            'creditCardToken' => $cardToken,
        ];

        return $this->request('POST', '/payments', $body);
    }

    /**
     * Retrieves a payment by ID to verify its status.
     * @return array<string,mixed>
     */
    public function getPayment(string $paymentId): array
    {
        return $this->request('GET', '/payments/' . urlencode($paymentId));
    }
}
