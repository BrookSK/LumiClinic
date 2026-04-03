<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\SaasPlanRepository;
use App\Repositories\UserRepository;
use App\Services\Auth\AuthService;
use App\Services\System\SystemClinicService;

final class ClinicSignupController extends Controller
{
    public function show(Request $request)
    {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            return $this->redirect('/');
        }

        $plans = (new SaasPlanRepository($this->container->get(\PDO::class)))->listActive();

        return $this->view('public/clinic_signup', [
            'plans' => $plans,
            'error' => null,
        ]);
    }

    public function store(Request $request)
    {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            return $this->redirect('/');
        }

        $pdo = $this->container->get(\PDO::class);
        $plans = (new SaasPlanRepository($pdo))->listActive();

        $clinicName = trim((string)$request->input('clinic_name', ''));
        $ownerName = trim((string)$request->input('owner_name', ''));
        $ownerEmail = strtolower(trim((string)$request->input('owner_email', '')));
        $ownerPassword = (string)$request->input('owner_password', '');
        $ownerPhone = preg_replace('/\D+/', '', (string)$request->input('owner_phone', ''));
        $docType = (string)$request->input('doc_type', 'cpf');
        $docNumber = preg_replace('/\D+/', '', (string)$request->input('doc_number', ''));
        $selectedPlan = (string)$request->input('plan', '');

        $viewData = ['plans' => $plans];

        if ($clinicName === '' || $ownerName === '' || $ownerEmail === '' || $ownerPassword === '') {
            $viewData['error'] = 'Preencha todos os campos obrigatórios.';
            return $this->view('public/clinic_signup', $viewData);
        }

        if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            $viewData['error'] = 'E-mail inválido.';
            return $this->view('public/clinic_signup', $viewData);
        }

        if (strlen($ownerPassword) < 8) {
            $viewData['error'] = 'Senha deve ter pelo menos 8 caracteres.';
            return $this->view('public/clinic_signup', $viewData);
        }

        $existing = (new UserRepository($pdo))->listActiveByEmail($ownerEmail, 1);
        if ($existing !== []) {
            $viewData['error'] = 'Já existe uma conta com este e-mail.';
            return $this->view('public/clinic_signup', $viewData);
        }

        $ownerFields = [
            'owner_name' => $ownerName,
            'owner_phone' => $ownerPhone,
            'owner_doc_type' => $docType,
        ];

        try {
            $svc = new SystemClinicService($this->container);
            $result = $svc->createClinicWithOwnerAndReturnIds(
                $clinicName,
                null,
                null,
                $ownerName,
                $ownerEmail,
                $ownerPassword,
                $request->ip(),
                $docNumber !== '' ? $docNumber : null,
                $ownerFields,
                []
            );

            // Save billing profile on the owner user
            $userBilling = [];
            if ($ownerPhone !== '') $userBilling['phone'] = $ownerPhone;
            if ($docType !== '') $userBilling['doc_type'] = $docType;
            if ($docNumber !== '') $userBilling['doc_number'] = $docNumber;
            if (!empty($userBilling)) {
                (new UserRepository($pdo))->updateBillingProfile((int)$result['owner_user_id'], $userBilling);
            }

            (new AuthService($this->container))->loginUserByIdForSession((int)$result['owner_user_id'], $request->ip(), $request->header('user-agent'));

            // Apply selected plan — create subscription directly with the chosen plan
            $selectedPlanRow = null;
            if ($selectedPlan !== '') {
                foreach ($plans as $p) {
                    if ((string)($p['code'] ?? '') === $selectedPlan) {
                        $selectedPlanRow = $p;
                        break;
                    }
                }
            }

            $planId = $selectedPlanRow !== null ? (int)($selectedPlanRow['id'] ?? 0) : null;
            $trialDays = $selectedPlanRow !== null ? (int)($selectedPlanRow['trial_days'] ?? 0) : 14;

            // If no plan selected or plan not found, fall back to trial plan
            if ($planId === null || $planId === 0) {
                $trialPlanRow = (new \App\Repositories\SaasPlanRepository($pdo))->findActiveByCode('trial');
                $planId = $trialPlanRow !== null ? (int)$trialPlanRow['id'] : null;
                $trialDays = $trialPlanRow !== null ? (int)($trialPlanRow['trial_days'] ?? 14) : 14;
            }

            $subStatus = $trialDays > 0 ? 'trial' : 'active';
            $trialEndsAt = $trialDays > 0
                ? (new \DateTimeImmutable('now'))->modify('+' . $trialDays . ' days')->format('Y-m-d H:i:s')
                : null;

            // Create subscription with the correct plan from the start
            $pdo->prepare("
                INSERT INTO clinic_subscriptions (clinic_id, plan_id, status, trial_ends_at, created_at)
                VALUES (:clinic_id, :plan_id, :status, :trial_ends_at, NOW())
                ON DUPLICATE KEY UPDATE
                    plan_id = VALUES(plan_id),
                    status = VALUES(status),
                    trial_ends_at = VALUES(trial_ends_at),
                    updated_at = NOW()
            ")->execute([
                'clinic_id' => (int)$result['clinic_id'],
                'plan_id' => $planId,
                'status' => $subStatus,
                'trial_ends_at' => $trialEndsAt,
            ]);

            // Send welcome email (best effort)
            try {
                (new \App\Services\Mail\WelcomeMailService($this->container))->sendClinicWelcome(
                    $ownerEmail,
                    $ownerName,
                    $clinicName,
                    $ownerPassword
                );
            } catch (\Throwable $ignore) {}

            // Process card and create gateway subscription
            $ccHolder = trim((string)$request->input('cc_holder', ''));
            $ccNumber = preg_replace('/\D+/', '', (string)$request->input('cc_number', ''));
            $ccExpMonth = trim((string)$request->input('cc_exp_month', ''));
            $ccExpYear = trim((string)$request->input('cc_exp_year', ''));
            $ccCvv = trim((string)$request->input('cc_cvv', ''));
            $cpf = preg_replace('/\D+/', '', (string)$request->input('cpf', ''));
            $postalCode = preg_replace('/\D+/', '', (string)$request->input('postal_code', ''));
            $addressNumber = trim((string)$request->input('address_number', ''));
            $mobile = preg_replace('/\D+/', '', (string)$request->input('mobile', ''));

            if ($ccHolder !== '' && $ccNumber !== '' && $ccExpMonth !== '' && $ccExpYear !== '' && $ccCvv !== '') {
                $gatewayError = null;
                try {
                    $cardData = [
                        'cc_holder' => $ccHolder,
                        'cc_number' => $ccNumber,
                        'cc_exp_month' => $ccExpMonth,
                        'cc_exp_year' => $ccExpYear,
                        'cc_cvv' => $ccCvv,
                        'cpf' => $cpf,
                        'postal_code' => $postalCode,
                        'address_number' => $addressNumber,
                        'phone' => $mobile,
                        'mobile' => $mobile,
                        'remote_ip' => $request->ip(),
                    ];
                    $gwService = new \App\Services\Billing\BillingGatewayService($this->container);
                    $gwService->ensureGatewaySubscription((int)$result['clinic_id'], $cardData);
                } catch (\Throwable $gwEx) {
                    $gatewayError = $gwEx->getMessage();
                    // Log to system error log
                    error_log('[Signup Gateway Error] clinic_id=' . $result['clinic_id'] . ' plan=' . $selectedPlan . ' error=' . $gatewayError);
                }
            }

            return $this->redirect('/');
        } catch (\RuntimeException $e) {
            $viewData['error'] = $e->getMessage();
            return $this->view('public/clinic_signup', $viewData);
        }
    }
}
