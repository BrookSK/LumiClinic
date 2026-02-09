<?php

declare(strict_types=1);

namespace App\Controllers\MedicalImages;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\MedicalImages\MedicalImageService;

final class MedicalImageController extends Controller
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
        $this->authorize('medical_images.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new MedicalImageService($this->container);
        $data = $service->listForPatient($patientId, $request->ip(), $request->header('user-agent'));

        return $this->view('medical-images/index', [
            'patient' => $data['patient'],
            'images' => $data['images'],
            'professionals' => $data['professionals'],
            'pairs' => $data['pairs'] ?? [],
        ]);
    }

    public function upload(Request $request)
    {
        $this->authorize('medical_images.upload');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $kind = trim((string)$request->input('kind', 'other'));
        $takenAt = trim((string)$request->input('taken_at', ''));
        $procedureType = trim((string)$request->input('procedure_type', ''));
        $professionalId = (int)$request->input('professional_id', 0);
        $medicalRecordId = (int)$request->input('medical_record_id', 0);

        $file = $_FILES['image'] ?? null;
        if (!is_array($file)) {
            return $this->redirect('/medical-images?patient_id=' . $patientId);
        }

        $service = new MedicalImageService($this->container);
        $service->upload($patientId, [
            'kind' => $kind,
            'taken_at' => ($takenAt === '' ? null : $takenAt),
            'procedure_type' => ($procedureType === '' ? null : $procedureType),
            'professional_id' => ($professionalId > 0 ? $professionalId : null),
            'medical_record_id' => ($medicalRecordId > 0 ? $medicalRecordId : null),
        ], $file, $request->ip(), $request->header('user-agent'));

        return $this->redirect('/medical-images?patient_id=' . $patientId);
    }

    public function uploadPair(Request $request)
    {
        $this->authorize('medical_images.upload');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $takenAt = trim((string)$request->input('taken_at', ''));
        $procedureType = trim((string)$request->input('procedure_type', ''));
        $professionalId = (int)$request->input('professional_id', 0);
        $medicalRecordId = (int)$request->input('medical_record_id', 0);

        $before = $_FILES['before_image'] ?? null;
        $after = $_FILES['after_image'] ?? null;
        if (!is_array($before) || !is_array($after)) {
            return $this->redirect('/medical-images?patient_id=' . $patientId);
        }

        $service = new MedicalImageService($this->container);
        $key = $service->uploadPair($patientId, [
            'taken_at' => ($takenAt === '' ? null : $takenAt),
            'procedure_type' => ($procedureType === '' ? null : $procedureType),
            'professional_id' => ($professionalId > 0 ? $professionalId : null),
            'medical_record_id' => ($medicalRecordId > 0 ? $medicalRecordId : null),
        ], $before, $after, $request->ip(), $request->header('user-agent'));

        return $this->redirect('/medical-images/compare?patient_id=' . $patientId . '&key=' . urlencode($key));
    }

    public function compare(Request $request)
    {
        $this->authorize('medical_images.read');
        $this->authorize('files.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $key = trim((string)$request->input('key', ''));
        if ($patientId <= 0 || $key === '') {
            return $this->redirect('/patients');
        }

        $service = new MedicalImageService($this->container);
        $data = $service->listForPatient($patientId, $request->ip(), $request->header('user-agent'));

        $beforeId = null;
        $afterId = null;
        foreach (($data['pairs'] ?? []) as $p) {
            if (isset($p['comparison_key']) && (string)$p['comparison_key'] === $key) {
                $beforeId = (int)$p['before_id'];
                $afterId = (int)$p['after_id'];
                break;
            }
        }

        if ($beforeId === null || $afterId === null) {
            return $this->redirect('/medical-images?patient_id=' . $patientId);
        }

        return $this->view('medical-images/compare', [
            'patient' => $data['patient'],
            'comparison_key' => $key,
            'before_id' => $beforeId,
            'after_id' => $afterId,
        ]);
    }

    public function file(Request $request)
    {
        $this->authorize('medical_images.read');
        $this->authorize('files.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/patients');
        }

        $service = new MedicalImageService($this->container);
        return $service->serveFile($id, $request->ip(), $request->header('user-agent'));
    }
}
