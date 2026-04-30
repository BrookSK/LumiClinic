<?php

declare(strict_types=1);

namespace App\Controllers\Dev;

use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AiBillingSettingsRepository;
use App\Repositories\AiWalletTransactionRepository;
use App\Services\Ai\AsaasAiClient;
use App\Services\Ai\AiWalletService;
use App\Services\Security\SystemCryptoService;

/**
 * Developer portal controller for AI billing management.
 * Does NOT extend the main Controller base class.
 * Uses its own session key _dev_ai_auth for authentication.
 */
final class AiBillingController
{
    public function __construct(private readonly Container $container) {}

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['_dev_ai_auth']) && $_SESSION['_dev_ai_auth'] === true;
    }

    private function requireAuth(): ?Response
    {
        if (!$this->isAuthenticated()) {
            return Response::redirect('/dev/ai-billing');
        }
        return null;
    }

    private function csrf(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return (string)$_SESSION['_csrf'];
    }

    private function verifyCsrf(Request $request): bool
    {
        $token = (string)$request->input('_csrf', '');
        $expected = (string)($_SESSION['_csrf'] ?? '');
        return $token !== '' && $token === $expected;
    }

    /**
     * GET /dev/ai-billing
     * Shows login form if not authenticated; shows dashboard if authenticated.
     */
    public function index(Request $request): Response
    {
        if (!$this->isAuthenticated()) {
            $error = trim((string)$request->input('error', ''));
            return $this->renderLogin($error !== '' ? $error : null);
        }

        return $this->renderDashboard($request);
    }

    /**
     * POST /dev/ai-billing/login
     */
    public function login(Request $request): Response
    {
        $password = trim((string)$request->input('password', ''));

        $pdo = $this->container->get(\PDO::class);
        $settings = (new AiBillingSettingsRepository($pdo))->getOrCreate();
        $storedHash = trim((string)($settings['dev_password_hash'] ?? ''));

        $valid = false;
        if ($storedHash !== '') {
            $valid = password_verify($password, $storedHash);
        } else {
            // Fallback default password
            $valid = password_verify($password, password_hash('padrão123456', PASSWORD_BCRYPT));
        }

        if ($valid) {
            $_SESSION['_dev_ai_auth'] = true;
            return Response::redirect('/dev/ai-billing');
        }

        return $this->renderLogin('Senha incorreta.');
    }

    /**
     * POST /dev/ai-billing/logout
     */
    public function logout(Request $request): Response
    {
        unset($_SESSION['_dev_ai_auth']);
        return Response::redirect('/dev/ai-billing');
    }

    /**
     * POST /dev/ai-billing/settings
     * Saves Asaas key, OpenAI key, pricing, and optional new password.
     */
    public function saveSettings(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect !== null) return $redirect;

        if (!$this->verifyCsrf($request)) {
            return Response::redirect('/dev/ai-billing?error=' . urlencode('Token CSRF inválido.'));
        }

        $asaasKey          = trim((string)$request->input('asaas_api_key', ''));
        $asaasSandbox      = trim((string)$request->input('asaas_sandbox_key', ''));
        $asaasMode         = trim((string)$request->input('asaas_mode', 'sandbox'));
        $webhookSecretSbx  = trim((string)$request->input('webhook_secret_sandbox', ''));
        $webhookSecretProd = trim((string)$request->input('webhook_secret_production', ''));
        $openaiKey         = trim((string)$request->input('openai_api_key', ''));
        $priceStr          = trim((string)$request->input('price_per_minute_brl', ''));
        $costStr           = trim((string)$request->input('cost_per_minute_brl', ''));
        $newPass           = trim((string)$request->input('new_password', ''));

        $price = $priceStr !== '' ? (float)$priceStr : null;
        $cost  = $costStr  !== '' ? (float)$costStr  : null;

        if ($price !== null && $price <= 0) {
            return Response::redirect('/dev/ai-billing?error=' . urlencode('O preço por minuto deve ser maior que zero.'));
        }
        if ($cost !== null && $cost <= 0) {
            return Response::redirect('/dev/ai-billing?error=' . urlencode('O custo por minuto deve ser maior que zero.'));
        }

        $crypto = new SystemCryptoService($this->container);
        $fields = [];

        try {
            if ($asaasKey !== '') {
                $fields['asaas_api_key_encrypted'] = $crypto->encrypt($asaasKey);
            }
            if ($asaasSandbox !== '') {
                $fields['asaas_sandbox_key_encrypted'] = $crypto->encrypt($asaasSandbox);
            }
            if ($webhookSecretSbx !== '') {
                $fields['asaas_webhook_secret_sandbox_encrypted'] = $crypto->encrypt($webhookSecretSbx);
            }
            if ($webhookSecretProd !== '') {
                $fields['asaas_webhook_secret_production_encrypted'] = $crypto->encrypt($webhookSecretProd);
            }
            if (in_array($asaasMode, ['sandbox', 'production'], true)) {
                $fields['asaas_mode'] = $asaasMode;
            }
            if ($openaiKey !== '') {
                $fields['openai_api_key_encrypted'] = $crypto->encrypt($openaiKey);
            }
            if ($price !== null) {
                $fields['price_per_minute_brl'] = $price;
            }
            if ($cost !== null) {
                $fields['cost_per_minute_brl'] = $cost;
            }
            if ($newPass !== '') {
                $fields['dev_password_hash'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            $pdo = $this->container->get(\PDO::class);
            (new AiBillingSettingsRepository($pdo))->save($fields);

        } catch (\Throwable $e) {
            $detail = get_class($e) . ': ' . $e->getMessage()
                . ' | ' . basename($e->getFile()) . ':' . $e->getLine();
            error_log('[AiBilling][saveSettings] ' . $detail . "\n" . $e->getTraceAsString());
            return Response::redirect('/dev/ai-billing?error=' . urlencode($detail));
        }

        return Response::redirect('/dev/ai-billing?saved=1');
    }

    /**
     * GET /dev/ai-billing/webhook-test
     * Diagnostic: checks system health and force-credits any confirmed pending payments.
     */
    public function webhookTest(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect !== null) return $redirect;

        $out = [];
        $pdo = $this->container->get(\PDO::class);

        try {
            $env = $this->resolveEnvironment();
            $out[] = "✅ Environment: $env";

            // Crypto check
            $crypto = new SystemCryptoService($this->container);
            $testEnc = $crypto->encrypt('test');
            $out[] = $crypto->decrypt($testEnc) === 'test' ? '✅ Crypto: OK' : '❌ Crypto: FAILED';

            // Wallet
            $walletService = new AiWalletService($this->container);
            $wallet = $walletService->getOrCreate();
            $out[] = '✅ Wallet balance: R$ ' . number_format((float)($wallet['balance_brl'] ?? 0), 2, ',', '.');

            // Asaas key
            $repo = new AiBillingSettingsRepository($pdo);
            $out[] = $repo->getActiveAsaasKey() !== '' ? '✅ Asaas key: configured' : '❌ Asaas key: NOT configured';

            // Pending charges
            $stmt = $pdo->prepare("SELECT id, payment_id, created_at FROM ai_wallet_transactions WHERE type = 'charge_pending' AND environment = :env ORDER BY id DESC LIMIT 10");
            $stmt->execute(['env' => $env]);
            $pending = $stmt->fetchAll();

            if (empty($pending)) {
                $out[] = '✅ No charge_pending records';
            } else {
                $out[] = '⚠️ Found ' . count($pending) . ' charge_pending record(s) — checking Asaas status...';
                foreach ($pending as $p) {
                    $pid = (string)($p['payment_id'] ?? '');
                    if ($pid === '') { $out[] = '⚠️ charge_pending id=' . $p['id'] . ' has no payment_id'; continue; }

                    try {
                        $asaasClient = new AsaasAiClient($this->container);
                        $payment = $asaasClient->getPayment($pid);
                        $status = (string)($payment['status'] ?? '');
                        $amount = (float)($payment['value'] ?? 0);
                        $out[] = '🔍 ' . $pid . ' → status=' . $status . ' value=R$' . $amount;

                        if (in_array($status, ['CONFIRMED', 'RECEIVED', 'AUTHORIZED'], true) && $amount > 0) {
                            $txRepo = new AiWalletTransactionRepository($pdo, $env);
                            if ($txRepo->findByPaymentId($pid) !== null) {
                                $out[] = '⚠️ ' . $pid . ' already credited';
                            } else {
                                $walletService->credit($amount, 'credit', 'Recarga via cartão — ' . $pid, $pid);
                                $w = $walletService->getOrCreate();
                                $out[] = '✅ Credited R$' . $amount . ' — new balance: R$ ' . number_format((float)($w['balance_brl'] ?? 0), 2, ',', '.');
                            }
                        }
                    } catch (\Throwable $e) {
                        $out[] = '❌ Error for ' . $pid . ': ' . $e->getMessage();
                    }
                }
            }

        } catch (\Throwable $e) {
            $out[] = '❌ ERROR: ' . $e->getMessage() . ' | ' . basename($e->getFile()) . ':' . $e->getLine();
        }

        $html = '<pre style="font-family:monospace;font-size:13px;padding:20px;background:#f9fafb;line-height:1.8;">';
        $html .= '<strong>🔍 Webhook Diagnostic</strong>' . "\n\n";
        $html .= implode("\n", $out);
        $html .= '</pre>';

        return Response::html($html);
    }

    /**
     * POST /dev/ai-billing/clear-pending
     * Removes stale charge_pending transactions so new recharges can be triggered.
     */
    public function clearPending(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect !== null) return $redirect;

        if (!$this->verifyCsrf($request)) {
            return Response::redirect('/dev/ai-billing?error=' . urlencode('Token CSRF inválido.'));
        }

        $env = $this->resolveEnvironment();
        $pdo = $this->container->get(\PDO::class);
        $pdo->prepare("DELETE FROM ai_wallet_transactions WHERE type = 'charge_pending' AND environment = :env")
            ->execute(['env' => $env]);

        return Response::redirect('/dev/ai-billing?saved=1&msg=' . urlencode('Cobranças pendentes removidas.') . '#wallet');
    }

    /**
     * POST /dev/ai-billing/reset-sandbox
     * Resets sandbox wallet: zeroes balance, clears card, deletes all sandbox transactions.
     */
    public function resetSandbox(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect !== null) return $redirect;

        if (!$this->verifyCsrf($request)) {
            return Response::redirect('/dev/ai-billing?error=' . urlencode('Token CSRF inválido.'));
        }

        try {
            $walletService = new AiWalletService($this->container);
            $walletService->resetSandbox();
        } catch (\Throwable $e) {
            return Response::redirect('/dev/ai-billing?error=' . urlencode($e->getMessage()) . '#wallet');
        }

        return Response::redirect('/dev/ai-billing?saved=1&msg=' . urlencode('Sandbox zerado com sucesso.') . '#wallet');
    }

    /**
     * POST /dev/ai-billing/credit
     * Manually credits the wallet.
     */
    public function manualCredit(Request $request): Response
    {
        $redirect = $this->requireAuth();
        if ($redirect !== null) return $redirect;

        if (!$this->verifyCsrf($request)) {
            return Response::redirect('/dev/ai-billing?error=' . urlencode('Token CSRF inválido.'));
        }

        $amount      = (float)$request->input('amount', 0);
        $description = trim((string)$request->input('description', 'Crédito manual'));

        if ($amount <= 0) {
            return Response::redirect('/dev/ai-billing?error=' . urlencode('O valor deve ser maior que zero.'));
        }

        try {
            (new AiWalletService($this->container))->credit($amount, 'manual_credit', $description);
        } catch (\Throwable $e) {
            return Response::redirect('/dev/ai-billing?error=' . urlencode($e->getMessage()));
        }

        return Response::redirect('/dev/ai-billing?saved=1&msg=' . urlencode('Crédito de R$ ' . number_format($amount, 2, ',', '.') . ' aplicado com sucesso.'));
    }

    private function resolveEnvironment(): string
    {
        $pdo = $this->container->get(\PDO::class);
        $settings = (new AiBillingSettingsRepository($pdo))->getOrCreate();
        return (string)($settings['asaas_mode'] ?? 'sandbox');
    }

    /**
     * POST /webhooks/ai-billing/asaas
     * Asaas webhook receiver — always returns HTTP 200.
     */
    public function webhook(Request $request): Response
    {
        try {
            $body = (string)file_get_contents('php://input');
            error_log('[AiBilling][Webhook] Received body: ' . substr($body, 0, 500));

            // Verify webhook secret if configured
            $pdo = $this->container->get(\PDO::class);
            $repo = new AiBillingSettingsRepository($pdo);
            $encryptedSecret = $repo->getActiveWebhookSecret();

            if ($encryptedSecret !== '') {
                $crypto = new SystemCryptoService($this->container);
                $secret = $crypto->decrypt($encryptedSecret);

                $authHeader = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
                $authHeader = preg_replace('/^Bearer\s+/i', '', $authHeader);

                $data = json_decode($body, true);
                $bodyToken = is_array($data) ? trim((string)($data['accessToken'] ?? '')) : '';
                $receivedToken = $authHeader !== '' ? $authHeader : $bodyToken;

                if (!hash_equals($secret, $receivedToken)) {
                    error_log('[AiBilling][Webhook] Invalid secret — rejected');
                    return Response::raw('ok', 200);
                }
            }

            $data = $data ?? json_decode($body, true);

            if (!is_array($data)) {
                error_log('[AiBilling][Webhook] Invalid JSON body');
                return Response::raw('ok', 200);
            }

            $event     = (string)($data['event'] ?? '');
            $paymentId = (string)($data['payment']['id'] ?? '');

            error_log('[AiBilling][Webhook] event=' . $event . ' paymentId=' . $paymentId);

            $processableEvents = ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED', 'PAYMENT_AUTHORIZED'];
            if (!in_array($event, $processableEvents, true)) {
                error_log('[AiBilling][Webhook] Ignoring event: ' . $event);
                return Response::raw('ok', 200);
            }

            if ($paymentId === '') {
                error_log('[AiBilling][Webhook] Empty paymentId');
                return Response::raw('ok', 200);
            }

            $env = $this->resolveEnvironment();
            error_log('[AiBilling][Webhook] environment=' . $env);

            $txRepo = new AiWalletTransactionRepository($pdo, $env);

            $existing = $txRepo->findByPaymentId($paymentId);
            if ($existing !== null) {
                error_log('[AiBilling][Webhook] Already credited paymentId=' . $paymentId);
                return Response::raw('ok', 200);
            }

            // Verify payment status directly with Asaas API
            $asaas = new AsaasAiClient($this->container);
            $payment = $asaas->getPayment($paymentId);
            $status = (string)($payment['status'] ?? '');
            error_log('[AiBilling][Webhook] Asaas payment status=' . $status . ' value=' . ($payment['value'] ?? 0));

            if (!in_array($status, ['CONFIRMED', 'RECEIVED'], true)) {
                error_log('[AiBilling][Webhook] Payment not confirmed, status=' . $status);
                return Response::raw('ok', 200);
            }

            $amount = (float)($payment['value'] ?? 0);
            if ($amount <= 0) {
                error_log('[AiBilling][Webhook] Invalid amount=' . $amount);
                return Response::raw('ok', 200);
            }

            (new AiWalletService($this->container))->credit(
                $amount,
                'credit',
                'Recarga via cartão — ' . $paymentId,
                $paymentId
            );

            error_log('[AiBilling][Webhook] Credited R$' . $amount . ' for paymentId=' . $paymentId);

        } catch (\Throwable $e) {
            error_log('[AiBilling][Webhook] Error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
        }

        return Response::raw('ok', 200);
    }

    // -------------------------------------------------------------------------
    // Private rendering helpers
    // -------------------------------------------------------------------------

    private function renderLogin(?string $error): Response
    {
        $csrf = $this->csrf();
        ob_start();
        include dirname(__DIR__, 2) . '/Views/dev/ai_billing_login.php';
        return Response::html((string)ob_get_clean());
    }

    private function renderDashboard(Request $request): Response
    {
        $csrf = $this->csrf();
        $pdo  = $this->container->get(\PDO::class);

        $saved   = trim((string)$request->input('saved', ''));
        $errorQs = trim((string)$request->input('error', ''));
        $msgQs   = trim((string)$request->input('msg', ''));

        $settings = (new AiBillingSettingsRepository($pdo))->getOrCreate();
        $walletService = new AiWalletService($this->container);
        $wallet = $walletService->getOrCreate();
        $transactions = $walletService->listTransactions(100);

        $txRepo = new AiWalletTransactionRepository($pdo);
        $currentMonth = date('Y-m');
        $statsMonth = $txRepo->statsForPeriod($currentMonth);
        $statsTotal = $txRepo->statsTotal();

        $pricePerMin = (float)($settings['price_per_minute_brl'] ?? 0.0910);
        $costPerMin  = (float)($settings['cost_per_minute_brl'] ?? 0.0350);

        // Compute profit for stats
        $monthMinutes = (int)ceil((float)($statsMonth['total_seconds'] ?? 0) / 60);
        $monthCharged = (float)($statsMonth['total_charged_brl'] ?? 0);
        $monthCost    = round($monthMinutes * $costPerMin, 4);
        $monthProfit  = round($monthCharged - $monthCost, 4);

        $totalMinutes = (int)ceil((float)($statsTotal['total_seconds'] ?? 0) / 60);
        $totalCharged = (float)($statsTotal['total_charged_brl'] ?? 0);
        $totalCost    = round($totalMinutes * $costPerMin, 4);
        $totalProfit  = round($totalCharged - $totalCost, 4);

        $domain = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $webhookUrl = 'https://' . $domain . '/webhooks/ai-billing/asaas';

        $asaasKeySet          = trim((string)($settings['asaas_api_key_encrypted'] ?? '')) !== '';
        $asaasSandboxSet      = trim((string)($settings['asaas_sandbox_key_encrypted'] ?? '')) !== '';
        $asaasMode            = (string)($settings['asaas_mode'] ?? 'sandbox');
        $webhookSecretSbxSet  = trim((string)($settings['asaas_webhook_secret_sandbox_encrypted'] ?? '')) !== '';
        $webhookSecretProdSet = trim((string)($settings['asaas_webhook_secret_production_encrypted'] ?? '')) !== '';
        $openaiKeySet         = trim((string)($settings['openai_api_key_encrypted'] ?? '')) !== '';

        ob_start();
        include dirname(__DIR__, 2) . '/Views/dev/ai_billing_dashboard.php';
        return Response::html((string)ob_get_clean());
    }
}
