<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Patients\PatientService;
use App\Services\Auth\AuthService;

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
        $birthDate = trim((string)$request->input('birth_date', ''));
        $sex = trim((string)$request->input('sex', ''));
        $cpf = trim((string)$request->input('cpf', ''));
        $address = trim((string)$request->input('address', ''));
        $notes = trim((string)$request->input('notes', ''));
        $refProfessionalId = (int)$request->input('reference_professional_id', 0);

        if ($name === '') {
            $service = new PatientService($this->container);
            return $this->view('patients/create', [
                'error' => 'Nome é obrigatório.',
                'professionals' => $service->listReferenceProfessionals(),
            ]);
        }

        $service = new PatientService($this->container);
        $id = $service->create([
            'name' => $name,
            'email' => ($email === '' ? null : $email),
            'phone' => ($phone === '' ? null : $phone),
            'birth_date' => ($birthDate === '' ? null : $birthDate),
            'sex' => ($sex === '' ? null : $sex),
            'cpf' => ($cpf === '' ? null : $cpf),
            'address' => ($address === '' ? null : $address),
            'notes' => ($notes === '' ? null : $notes),
            'reference_professional_id' => ($refProfessionalId > 0 ? $refProfessionalId : null),
        ], $request->ip());

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
        $patient = $service->get($id, $request->ip());
        if ($patient === null) {
            return $this->redirect('/patients');
        }

        return $this->view('patients/view', [
            'patient' => $patient,
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
        $patient = $service->get($id, $request->ip());
        if ($patient === null) {
            return $this->redirect('/patients');
        }

        return $this->view('patients/edit', [
            'patient' => $patient,
            'professionals' => $service->listReferenceProfessionals(),
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
        $birthDate = trim((string)$request->input('birth_date', ''));
        $sex = trim((string)$request->input('sex', ''));
        $cpf = trim((string)$request->input('cpf', ''));
        $address = trim((string)$request->input('address', ''));
        $notes = trim((string)$request->input('notes', ''));
        $refProfessionalId = (int)$request->input('reference_professional_id', 0);
        $status = trim((string)$request->input('status', 'active'));

        if ($id <= 0 || $name === '') {
            $service = new PatientService($this->container);
            return $this->view('patients/edit', [
                'patient' => $service->get($id, $request->ip()),
                'professionals' => $service->listReferenceProfessionals(),
                'error' => 'Preencha os campos obrigatórios.',
            ]);
        }

        $service = new PatientService($this->container);
        $service->update($id, [
            'name' => $name,
            'email' => ($email === '' ? null : $email),
            'phone' => ($phone === '' ? null : $phone),
            'birth_date' => ($birthDate === '' ? null : $birthDate),
            'sex' => ($sex === '' ? null : $sex),
            'cpf' => ($cpf === '' ? null : $cpf),
            'address' => ($address === '' ? null : $address),
            'notes' => ($notes === '' ? null : $notes),
            'reference_professional_id' => ($refProfessionalId > 0 ? $refProfessionalId : null),
            'status' => ($status === '' ? 'active' : $status),
        ], $request->ip(), $request->header('user-agent'));

        return $this->redirect('/patients/view?id=' . $id);
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
}
