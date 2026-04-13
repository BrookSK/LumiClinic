<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\LegalDocumentAcceptanceRepository;
use App\Repositories\LegalDocumentRepository;
use App\Repositories\PatientUserRepository;
use App\Repositories\PatientPackageRepository;
use App\Services\Patients\PatientService;
use App\Services\Auth\AuthService;
use App\Services\Settings\OperationalConfigService;

final class PatientController extends Controller
{
    private function redirectSuperAdminWithoutClinicContext(): ?\App\Core\Http\Response
    {
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if (!$isSuperAdmin) {
            return null;
        }

        $auth = new AuthService($this->container);
        if ($auth->clinicId() === null) {
            return $this->redirect('/sys/clinics');
        }

        return null;
    }

    public function index(Request $request)
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $q = trim((string)$request->input('q', ''));
        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 25);

        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $service = new PatientService($this->container);
        $patients = $service->search($q, $perPage + 1, $offset);
        $hasNext = count($patients) > $perPage;
        if ($hasNext) {
            $patients = array_slice($patients, 0, $perPage);
        }

        return $this->view('patients/index', [
            'patients' => $patients,
            'q' => $q,
            'page' => $page,
            'per_page' => $perPage,
            'has_next' => $hasNext,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('patients.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new PatientService($this->container);

        return $this->view('patients/create', [
            'professionals' => $service->listReferenceProfessionals(),
            'patient_origins' => (new OperationalConfigService($this->container))->listActivePatientOrigins(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('patients.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $email = trim((string)$request->input('email', ''));
        $phone = trim((string)$request->input('phone', ''));
        $whatsappOptIn = (int)$request->input('whatsapp_opt_in', 1);
        $birthDate = trim((string)$request->input('birth_date', ''));
        $sex = trim((string)$request->input('sex', ''));
        $cpf = trim((string)$request->input('cpf', ''));
        $street = trim((string)$request->input('address_street', ''));
        $number = trim((string)$request->input('address_number', ''));
        $complement = trim((string)$request->input('address_complement', ''));
        $district = trim((string)$request->input('address_district', ''));
        $city = trim((string)$request->input('address_city', ''));
        $state = trim((string)$request->input('address_state', ''));
        $zip = trim((string)$request->input('address_zip', ''));

        $address = '';
        $line1 = trim($street
            . ($number !== '' ? (', ' . $number) : '')
            . ($complement !== '' ? (' - ' . $complement) : '')
        );
        $line2 = trim(
            ($district !== '' ? ($district . ' - ') : '')
            . $city
            . ($state !== '' ? ('/' . $state) : '')
        );
        $line3 = $zip !== '' ? ('CEP: ' . $zip) : '';
        $address = implode("\n", array_values(array_filter([$line1, $line2, $line3], fn ($v) => is_string($v) && trim($v) !== '')));

        if (trim($address) === '') {
            $address = trim((string)$request->input('address', ''));
        }
        $notes = trim((string)$request->input('notes', ''));
        $refProfessionalId = (int)$request->input('reference_professional_id', 0);
        $patientOriginId = (int)$request->input('patient_origin_id', 0);

        if ($name === '') {
            $service = new PatientService($this->container);
            return $this->view('patients/create', [
                'error' => 'Nome é obrigatório.',
                'professionals' => $service->listReferenceProfessionals(),
                'patient_origins' => (new OperationalConfigService($this->container))->listActivePatientOrigins(),
            ]);
        }

        $service = new PatientService($this->container);
        $id = $service->create([
            'name' => $name,
            'email' => ($email === '' ? null : $email),
            'phone' => ($phone === '' ? null : $phone),
            'whatsapp_opt_in' => $whatsappOptIn ? 1 : 0,
            'birth_date' => ($birthDate === '' ? null : $birthDate),
            'sex' => ($sex === '' ? null : $sex),
            'cpf' => ($cpf === '' ? null : $cpf),
            'address' => ($address === '' ? null : $address),
            'notes' => ($notes === '' ? null : $notes),
            'reference_professional_id' => ($refProfessionalId > 0 ? $refProfessionalId : null),
            'patient_origin_id' => ($patientOriginId > 0 ? $patientOriginId : null),
        ], $request->ip());

        // Criar acesso ao portal se senha foi informada
        $portalPassword = trim((string)$request->input('portal_password', ''));
        if ($portalPassword !== '' && $email !== '') {
            try {
                (new \App\Services\Portal\PatientPortalAccessService($this->container))
                    ->createWithPassword($id, strtolower($email), $portalPassword, $request->ip());
            } catch (\Throwable $ignore) {
                // Não bloqueia o cadastro por falha no portal
            }
        }

        return $this->redirect('/patients/view?id=' . $id);
    }

    public function show(Request $request)
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/patients');
        }

        $service = new PatientService($this->container);
        $patient = $service->get($id, $request->ip(), $request->header('user-agent'));
        if ($patient === null) {
            return $this->redirect('/patients');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        $portalDocs = [];
        $portalAcceptances = [];
        $patientUser = null;

        if ($clinicId !== null) {
            $pdo = $this->container->get(\PDO::class);
            $patientUser = (new PatientUserRepository($pdo))->findByPatientId($clinicId, $id);

            $portalDocs = (new LegalDocumentRepository($pdo))->listActiveForPatientPortal($clinicId);

            if ($patientUser !== null && isset($patientUser['id'])) {
                $portalAcceptances = (new LegalDocumentAcceptanceRepository($pdo))->listByPatientUser($clinicId, (int)$patientUser['id'], 500);
            }
        }

        return $this->view('patients/view', [
            'patient' => $patient,
            'patient_user' => $patientUser,
            'portal_legal_docs' => $portalDocs,
            'portal_legal_acceptances' => $portalAcceptances,
        ]);
    }

    public function edit(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/patients');
        }

        $service = new PatientService($this->container);
        $patient = $service->get($id, $request->ip(), $request->header('user-agent'));
        if ($patient === null) {
            return $this->redirect('/patients');
        }

        return $this->view('patients/edit', [
            'patient' => $patient,
            'professionals' => $service->listReferenceProfessionals(),
            'patient_origins' => (new OperationalConfigService($this->container))->listActivePatientOrigins(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $name = trim((string)$request->input('name', ''));
        $email = trim((string)$request->input('email', ''));
        $phone = trim((string)$request->input('phone', ''));
        $whatsappOptIn = (int)$request->input('whatsapp_opt_in', 1);
        $birthDate = trim((string)$request->input('birth_date', ''));
        $sex = trim((string)$request->input('sex', ''));
        $cpf = trim((string)$request->input('cpf', ''));
        $street = trim((string)$request->input('address_street', ''));
        $number = trim((string)$request->input('address_number', ''));
        $complement = trim((string)$request->input('address_complement', ''));
        $district = trim((string)$request->input('address_district', ''));
        $city = trim((string)$request->input('address_city', ''));
        $state = trim((string)$request->input('address_state', ''));
        $zip = trim((string)$request->input('address_zip', ''));

        $address = '';
        $line1 = trim($street
            . ($number !== '' ? (', ' . $number) : '')
            . ($complement !== '' ? (' - ' . $complement) : '')
        );
        $line2 = trim(
            ($district !== '' ? ($district . ' - ') : '')
            . $city
            . ($state !== '' ? ('/' . $state) : '')
        );
        $line3 = $zip !== '' ? ('CEP: ' . $zip) : '';
        $address = implode("\n", array_values(array_filter([$line1, $line2, $line3], fn ($v) => is_string($v) && trim($v) !== '')));

        if (trim($address) === '') {
            $address = trim((string)$request->input('address', ''));
        }
        $notes = trim((string)$request->input('notes', ''));
        $refProfessionalId = (int)$request->input('reference_professional_id', 0);
        $patientOriginId = (int)$request->input('patient_origin_id', 0);
        $status = trim((string)$request->input('status', 'active'));

        $allowedStatus = ['active', 'disabled', 'inactive'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'active';
        }

        if ($id <= 0 || $name === '') {
            $service = new PatientService($this->container);
            return $this->view('patients/edit', [
                'patient' => $service->get($id, $request->ip()),
                'professionals' => $service->listReferenceProfessionals(),
                'patient_origins' => (new OperationalConfigService($this->container))->listActivePatientOrigins(),
                'error' => 'Preencha os campos obrigatórios.',
            ]);
        }

        $service = new PatientService($this->container);

        $fallbackPatient = [
            'id' => $id,
            'name' => $name,
            'email' => ($email === '' ? null : $email),
            'phone' => ($phone === '' ? null : $phone),
            'whatsapp_opt_in' => $whatsappOptIn ? 1 : 0,
            'birth_date' => ($birthDate === '' ? null : $birthDate),
            'sex' => ($sex === '' ? null : $sex),
            'cpf' => ($cpf === '' ? null : $cpf),
            'address' => ($address === '' ? null : $address),
            'notes' => ($notes === '' ? null : $notes),
            'reference_professional_id' => ($refProfessionalId > 0 ? $refProfessionalId : null),
            'patient_origin_id' => ($patientOriginId > 0 ? $patientOriginId : null),
            'status' => ($status === '' ? 'active' : $status),
        ];

        try {
            $service->update($id, [
                'name' => $name,
                'email' => ($email === '' ? null : $email),
                'phone' => ($phone === '' ? null : $phone),
                'whatsapp_opt_in' => $whatsappOptIn ? 1 : 0,
                'birth_date' => ($birthDate === '' ? null : $birthDate),
                'sex' => ($sex === '' ? null : $sex),
                'cpf' => ($cpf === '' ? null : $cpf),
                'address' => ($address === '' ? null : $address),
                'notes' => ($notes === '' ? null : $notes),
                'reference_professional_id' => ($refProfessionalId > 0 ? $refProfessionalId : null),
                'patient_origin_id' => ($patientOriginId > 0 ? $patientOriginId : null),
                'status' => ($status === '' ? 'active' : $status),
            ], $request->ip(), $request->header('user-agent'));

            return $this->redirect(trim((string)$request->input('_redirect', '')) ?: '/patients/view?id=' . $id);
        } catch (\RuntimeException $e) {
            $patient = $service->get($id, $request->ip()) ?? $fallbackPatient;
            return $this->view('patients/edit', [
                'patient' => $patient,
                'professionals' => $service->listReferenceProfessionals(),
                'patient_origins' => (new OperationalConfigService($this->container))->listActivePatientOrigins(),
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $patient = $service->get($id, $request->ip()) ?? $fallbackPatient;
            return $this->view('patients/edit', [
                'patient' => $patient,
                'professionals' => $service->listReferenceProfessionals(),
                'patient_origins' => (new OperationalConfigService($this->container))->listActivePatientOrigins(),
                'error' => 'Erro ao salvar paciente.',
            ]);
        }
    }

    public function searchJson(Request $request): Response
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return Response::json(['items' => []]);
        }

        $q = trim((string)$request->input('q', ''));
        $limit = (int)$request->input('limit', 20);
        $limit = max(1, min(30, $limit));

        $service = new PatientService($this->container);
        $rows = $service->search($q, $limit, 0);

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id' => (int)($r['id'] ?? 0),
                'name' => (string)($r['name'] ?? ''),
                'email' => (string)($r['email'] ?? ''),
                'phone' => (string)($r['phone'] ?? ''),
            ];
        }

        return Response::json(['items' => $items]);
    }

    public function packagesJson(Request $request): Response
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return Response::json(['items' => []]);
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return Response::json(['items' => []]);
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return Response::json(['items' => []]);
        }

        $limit = (int)$request->input('limit', 30);
        $limit = max(1, min(100, $limit));

        $repo = new PatientPackageRepository($this->container->get(\PDO::class));
        $rows = $repo->listActiveByPatient($clinicId, $patientId, $limit);

        $items = [];
        foreach ($rows as $r) {
            $total = (int)($r['total_sessions'] ?? 0);
            $used = (int)($r['used_sessions'] ?? 0);
            $items[] = [
                'id' => (int)($r['id'] ?? 0),
                'package_id' => (int)($r['package_id'] ?? 0),
                'package_name' => (string)($r['package_name'] ?? ''),
                'total_sessions' => $total,
                'used_sessions' => $used,
                'remaining_sessions' => max(0, $total - $used),
                'valid_until' => $r['valid_until'] !== null ? (string)$r['valid_until'] : null,
                'status' => (string)($r['status'] ?? ''),
            ];
        }

        return Response::json(['items' => $items]);
    }
}
