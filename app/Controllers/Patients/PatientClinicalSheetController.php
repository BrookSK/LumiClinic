<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Patients\PatientClinicalSheetService;

final class PatientClinicalSheetController extends Controller
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

    public function show(Request $request)
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        try {
            $svc = new PatientClinicalSheetService($this->container);
            $data = $svc->view($patientId, $request->ip(), $request->header('user-agent'));

            return $this->view('patients/clinical_sheet', [
                'patient' => $data['patient'],
                'allergies' => $data['allergies'],
                'conditions' => $data['conditions'],
                'alerts' => $data['alerts'],
                'error' => trim((string)$request->input('error', '')),
                'success' => trim((string)$request->input('success', '')),
            ]);
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients?error=' . urlencode($e->getMessage()));
        }
    }

    public function createAllergy(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $type = trim((string)$request->input('type', 'allergy'));
        $trigger = trim((string)$request->input('trigger_name', ''));
        $reaction = trim((string)$request->input('reaction', ''));
        $severity = trim((string)$request->input('severity', ''));
        $notes = trim((string)$request->input('notes', ''));

        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        try {
            (new PatientClinicalSheetService($this->container))->createAllergy(
                $patientId,
                $type,
                $trigger,
                $reaction === '' ? null : $reaction,
                $severity === '' ? null : $severity,
                $notes === '' ? null : $notes,
                $request->ip(),
                $request->header('user-agent')
            );

            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&success=' . urlencode('Salvo.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteAllergy(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $id = (int)$request->input('id', 0);

        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        if ($id <= 0) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId);
        }

        try {
            (new PatientClinicalSheetService($this->container))->deleteAllergy($id, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&success=' . urlencode('Removido.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function createCondition(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $name = trim((string)$request->input('condition_name', ''));
        $status = trim((string)$request->input('status', 'active'));
        $onset = trim((string)$request->input('onset_date', ''));
        $notes = trim((string)$request->input('notes', ''));

        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        try {
            (new PatientClinicalSheetService($this->container))->createCondition(
                $patientId,
                $name,
                $status,
                $onset === '' ? null : $onset,
                $notes === '' ? null : $notes,
                $request->ip(),
                $request->header('user-agent')
            );

            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&success=' . urlencode('Salvo.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteCondition(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $id = (int)$request->input('id', 0);

        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        if ($id <= 0) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId);
        }

        try {
            (new PatientClinicalSheetService($this->container))->deleteCondition($id, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&success=' . urlencode('Removido.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function createAlert(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $title = trim((string)$request->input('title', ''));
        $details = trim((string)$request->input('details', ''));
        $severity = trim((string)$request->input('severity', 'warning'));
        $active = (int)$request->input('active', 1);

        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        try {
            (new PatientClinicalSheetService($this->container))->createAlert(
                $patientId,
                $title,
                $details === '' ? null : $details,
                $severity,
                $active === 1 ? 1 : 0,
                $request->ip(),
                $request->header('user-agent')
            );

            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&success=' . urlencode('Salvo.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function resolveAlert(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $id = (int)$request->input('id', 0);

        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        if ($id <= 0) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId);
        }

        try {
            (new PatientClinicalSheetService($this->container))->resolveAlert($id, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&success=' . urlencode('Resolvido.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function deleteAlert(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $id = (int)$request->input('id', 0);

        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        if ($id <= 0) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId);
        }

        try {
            (new PatientClinicalSheetService($this->container))->deleteAlert($id, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&success=' . urlencode('Removido.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/clinical-sheet?patient_id=' . $patientId . '&error=' . urlencode($e->getMessage()));
        }
    }
}
