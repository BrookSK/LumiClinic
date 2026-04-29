<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Container\Container;
use App\Repositories\AiBillingSettingsRepository;
use App\Services\Http\HttpClient;
use App\Services\Security\SystemCryptoService;

/**
 * HTTP client for the developer's Asaas account (separate from the clinic-billing Asaas account).
 * Used exclusively for charging the superadmin's credit card for AI wallet top-ups.
 */
final class AsaasAiClient
{
    public function __construct(private readonly Container $container) {}

    private function resolveKeyAndUrl(): array
    {
        $repo = new AiBillingSettingsRepository($this->container->get(\PDO::class));
        $encrypted = $repo->getActiveAsaasKey();

        if ($encrypted === '') {
            throw new \RuntimeException('Chave Asaas do desenvolvedor não configurada. Acesse o portal dev para configurar.');
        }

        $key = (new SystemCryptoService($this->container))->decrypt($encrypted);
        $url = $repo->getAsaasBaseUrl();

        return [$key, $url];
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        [$apiKey, $baseUrl] = $this->resolveKeyAndUrl();
        $url = $baseUrl . $path;

        $http = new HttpClient();
        $resp = $http->request($method, $url, [
            'access_token' => $apiKey,
        ], $body, 30);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $msg = 'Erro Asaas (HTTP ' . $resp['status'] . ').';

            $jsonResp = $resp['json'];
            if ($jsonResp === null && !empty($resp['body'])) {
                $jsonResp = json_decode((string)$resp['body'], true);
            }

            if (is_array($jsonResp) && isset($jsonResp['errors']) && is_array($jsonResp['errors'])) {
                $parts = [];
                foreach ($jsonResp['errors'] as $e) {
                    if (is_array($e)) {
                        $desc = trim((string)($e['description'] ?? ''));
                        $code = trim((string)($e['code'] ?? ''));
                        if ($desc !== '') {
                            $parts[] = $desc;
                        } elseif ($code !== '') {
                            $parts[] = $code;
                        }
                    }
                }
                if ($parts !== []) {
                    $msg = implode(' | ', array_slice($parts, 0, 4));
                }
            }

            throw new \RuntimeException($msg);
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida da Asaas.');
        }

        return $json;
    }

    /**
     * Creates a customer in the developer's Asaas account.
     * @return array<string,mixed>
     */
    public function createCustomer(
        string $name,
        ?string $email,
        ?string $cpfCnpj,
        ?string $phone
    ): array {
        $payload = ['name' => $name];

        if ($email !== null && trim($email) !== '') {
            $payload['email'] = $email;
        }
        if ($cpfCnpj !== null && trim($cpfCnpj) !== '') {
            $payload['cpfCnpj'] = preg_replace('/\D+/', '', $cpfCnpj);
        }
        if ($phone !== null && trim($phone) !== '') {
            // Asaas uses mobilePhone for mobile numbers
            $payload['mobilePhone'] = preg_replace('/\D+/', '', $phone);
        }

        return $this->request('POST', '/customers', $payload);
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
                'number'      => preg_replace('/\D+/', '', (string)($card['number'] ?? '')),
                'expiryMonth' => preg_replace('/\D+/', '', (string)($card['expiryMonth'] ?? '')),
                'expiryYear'  => preg_replace('/\D+/', '', (string)($card['expiryYear'] ?? '')),
                'ccv'         => preg_replace('/\D+/', '', (string)($card['ccv'] ?? '')),
            ],
            'creditCardHolderInfo' => [
                'name'          => $holder['name'] ?? '',
                'email'         => $holder['email'] ?? '',
                'cpfCnpj'       => preg_replace('/\D+/', '', (string)($holder['cpfCnpj'] ?? '')),
                'postalCode'    => preg_replace('/\D+/', '', (string)($holder['postalCode'] ?? '00000000')),
                'addressNumber' => $holder['addressNumber'] !== '' ? $holder['addressNumber'] : 'S/N',
                'phone'         => preg_replace('/\D+/', '', (string)($holder['phone'] ?? '')),
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
