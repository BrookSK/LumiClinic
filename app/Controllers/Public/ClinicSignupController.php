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

            // If a specific plan was selected (not trial), redirect to subscription page
            if ($selectedPlan !== '' && $selectedPlan !== 'trial') {
                return $this->redirect('/billing/subscription');
            }

            return $this->redirect('/');
        } catch (\RuntimeException $e) {
            $viewData['error'] = $e->getMessage();
            return $this->view('public/clinic_signup', $viewData);
        }
    }
}
