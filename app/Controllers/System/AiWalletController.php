<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AiBillingSettingsRepository;
use App\Services\Ai\AsaasAiClient;
use App\Services\Ai\AiWalletService;

final class AiWalletController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    private function verifyCsrf(Request $request): void
    {
        $token = (string)$request->input('_csrf', '');
        $expected = (string)($_SESSION['_csrf'] ?? '');
        if ($token === '' || $token !== $expected) {
            throw new \RuntimeException('Token CSRF inválido.');
        }
    }

    /**
     * POST /sys/settings/ai/wallet
     * Saves card tokenization + auto-recharge configuration.
     */
    public function saveSettings(Request $request): Response
    {
        $this->ensureSuperAdmin();
        $this->verifyCsrf($request);

        $holderName  = trim((string)$request->input('holder_name', ''));
        $email       = trim((string)$request->input('email', ''));
        $cpf         = trim((string)$request->input('cpf', ''));
        $phone       = trim((string)$request->input('phone', ''));
        $postalCode  = trim((string)$request->input('postal_code', ''));
        $addressNum  = trim((string)$request->input('address_number', ''));
        $cardNumber  = trim((string)$request->input('card_number', ''));
        $expiryMonth = trim((string)$request->input('expiry_month', ''));
        $expiryYear  = trim((string)$request->input('expiry_year', ''));
        $ccv         = trim((string)$request->input('ccv', ''));

        $autoEnabled   = (string)$request->input('auto_recharge_enabled', '0') === '1';
        $threshold     = (float)$request->input('auto_recharge_threshold_brl', 10.00);
        $rechargeAmt   = (float)$request->input('auto_recharge_amount_brl', 50.00);

        $walletService = new AiWalletService($this->container);

        // If card data provided, tokenize via Asaas
        $hasCardData = $cardNumber !== '' && $expiryMonth !== '' && $expiryYear !== '' && $ccv !== '';

        if ($hasCardData) {
            try {
                $asaas = new AsaasAiClient($this->container);

                // DEBUG: capture full request/response details
                $debugMode = isset($_GET['_debug']) && $_GET['_debug'] === 'lumi2026';

                // Get or create Asaas customer
                $customer = $asaas->createCustomer(
                    $holderName !== '' ? $holderName : 'Superadmin',
                    $email !== '' ? $email : null,
                    $cpf !== '' ? $cpf : null,
                    $phone !== '' ? $phone : null
                );

                if ($debugMode) {
                    return Response::html('<pre style="font-size:12px;padding:20px;">'
                        . 'createCustomer response: ' . htmlspecialchars(json_encode($customer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES)
                        . '</pre>');
                }

                $customerId = (string)($customer['id'] ?? '');
                if ($customerId === '') {
                    return $this->redirect('/sys/settings/ai?error=' . urlencode('Erro ao criar cliente na Asaas.') . '#wallet');
                }

                // Tokenize card — never store raw card data
                $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                $tokenResult = $asaas->tokenizeCard(
                    $customerId,
                    [
                        'holderName'  => $holderName,
                        'number'      => $cardNumber,
                        'expiryMonth' => $expiryMonth,
                        'expiryYear'  => $expiryYear,
                        'ccv'         => $ccv,
                    ],
                    [
                        'name'          => $holderName,
                        'email'         => $email,
                        'cpfCnpj'       => $cpf,
                        'postalCode'    => $postalCode,
                        'addressNumber' => $addressNum !== '' ? $addressNum : 'S/N',
                        'phone'         => $phone,
                    ],
                    $remoteIp
                );

                $cardToken = (string)($tokenResult['creditCardToken'] ?? '');
                $last4Raw  = (string)($tokenResult['creditCardNumber'] ?? '');
                // Extract last 4 digits from masked number like "****1234"
                $last4 = strlen($last4Raw) >= 4 ? substr($last4Raw, -4) : $last4Raw;

                if ($cardToken === '') {
                    return $this->redirect('/sys/settings/ai?error=' . urlencode('Erro ao tokenizar cartão.') . '#wallet');
                }

                // Property 5: Only token is persisted — never raw card data
                $walletService->saveCardToken($customerId, $cardToken, $last4);
            } catch (\Throwable $e) {
                return $this->redirect('/sys/settings/ai?error=' . urlencode($e->getMessage()) . '#wallet');
            }
        }

        // Save recharge configuration
        if ($rechargeAmt < 25.00) {
            return $this->redirect('/sys/settings/ai?error=' . urlencode('O valor mínimo de recarga é R$ 25,00.') . '#wallet');
        }
        $walletService->saveRechargeConfig($autoEnabled, $threshold, $rechargeAmt);

        return $this->redirect('/sys/settings/ai?saved=1#wallet');
    }

    /**
     * POST /sys/settings/ai/wallet/recharge
     * Triggers a manual recharge using the configured recharge amount.
     */
    public function manualRecharge(Request $request): Response
    {
        $this->ensureSuperAdmin();
        $this->verifyCsrf($request);

        try {
            $walletService = new AiWalletService($this->container);
            $wallet = $walletService->getOrCreate();
            $amount = (float)($wallet['auto_recharge_amount_brl'] ?? 50.00);

            if ($amount <= 0) {
                return $this->redirect('/sys/settings/ai?error=' . urlencode('Valor de recarga não configurado.') . '#wallet');
            }

            $walletService->triggerRecharge($amount);
        } catch (\Throwable $e) {
            return $this->redirect('/sys/settings/ai?error=' . urlencode($e->getMessage()) . '#wallet');
        }

        return $this->redirect('/sys/settings/ai?saved=1&msg=' . urlencode('Recarga iniciada com sucesso.') . '#wallet');
    }
}
