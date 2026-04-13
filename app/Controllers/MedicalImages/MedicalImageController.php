<?php

declare(strict_types=1);

namespace App\Controllers\MedicalImages;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\MedicalImageAnnotationRepository;
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
            'records' => $data['records'] ?? [],
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
        $sessionNumber = (int)$request->input('session_number', 0);
        $pose = trim((string)$request->input('pose', ''));
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
            'session_number' => ($sessionNumber > 0 ? $sessionNumber : null),
            'pose' => ($pose === '' ? null : $pose),
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
        $sessionNumber = (int)$request->input('session_number', 0);
        $pose = trim((string)$request->input('pose', ''));
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
            'session_number' => ($sessionNumber > 0 ? $sessionNumber : null),
            'pose' => ($pose === '' ? null : $pose),
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

    public function timeline(Request $request)
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
        $data = $service->timelineForPatient($patientId, $request->ip(), $request->header('user-agent'));

        return $this->view('medical-images/timeline', [
            'patient' => $data['patient'],
            'items' => $data['items'],
        ]);
    }

    public function annotate(Request $request)
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
        $img = $service->getImage($id, $request->ip(), $request->header('user-agent'));
        if ($img === null) {
            return $this->redirect('/patients');
        }

        return $this->view('medical-images/annotate', [
            'image' => $img,
            'csrf' => $_SESSION['_csrf'] ?? '',
        ]);
    }

    public function annotationsJson(Request $request): Response
    {
        $this->authorize('medical_images.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return Response::json(['items' => []]);
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return Response::json(['items' => []]);
        }

        $imageId = (int)$request->input('image_id', 0);
        if ($imageId <= 0) {
            return Response::json(['items' => []]);
        }

        $repo = new MedicalImageAnnotationRepository($this->container->get(\PDO::class));
        $items = $repo->listByImage($clinicId, $imageId, 500);

        return Response::json(['items' => $items]);
    }

    public function annotationsCreate(Request $request)
    {
        $this->authorize('medical_images.upload');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $imageId = (int)$request->input('image_id', 0);
        $payload = trim((string)$request->input('payload_json', ''));
        $note = trim((string)$request->input('note', ''));
        if ($imageId <= 0 || $payload === '') {
            return $this->redirect('/patients');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            return $this->redirect('/patients');
        }

        $repo = new MedicalImageAnnotationRepository($this->container->get(\PDO::class));
        $repo->create($clinicId, $imageId, $payload, ($note === '' ? null : $note), $actorId);

        return $this->redirect('/medical-images/annotate?id=' . $imageId);
    }

    public function annotationsDelete(Request $request)
    {
        $this->authorize('medical_images.upload');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $imageId = (int)$request->input('image_id', 0);
        if ($id <= 0 || $imageId <= 0) {
            return $this->redirect('/patients');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/patients');
        }

        $repo = new MedicalImageAnnotationRepository($this->container->get(\PDO::class));
        $repo->softDelete($clinicId, $id);

        return $this->redirect('/medical-images/annotate?id=' . $imageId);
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

    public function crop(Request $request)
    {
        $this->authorize('medical_images.upload');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $imageId = (int)$request->input('image_id', 0);
        $x = (int)$request->input('x', 0);
        $y = (int)$request->input('y', 0);
        $w = (int)$request->input('w', 0);
        $h = (int)$request->input('h', 0);
        $mode = trim((string)$request->input('mode', 'replace'));

        if ($imageId <= 0 || $w <= 0 || $h <= 0) {
            return $this->redirect('/medical-images');
        }

        try {
            $service = new MedicalImageService($this->container);

            if ($mode === 'duplicate') {
                $newId = $service->cropImageAsCopy($imageId, $x, $y, $w, $h, $request->ip(), $request->header('user-agent'));
                return $this->redirect('/medical-images/annotate?id=' . $newId);
            } else {
                $service->cropImage($imageId, $x, $y, $w, $h, $request->ip(), $request->header('user-agent'));
                return $this->redirect('/medical-images/annotate?id=' . $imageId);
            }
        } catch (\RuntimeException $e) {
            $auth = new AuthService($this->container);
            $clinicId = $auth->clinicId();
            $pdo = $this->container->get(\PDO::class);
            $stmt = $pdo->prepare('SELECT patient_id FROM medical_images WHERE id = ? AND clinic_id = ? LIMIT 1');
            $stmt->execute([$imageId, $clinicId]);
            $row = $stmt->fetch();
            $pid = $row ? (int)$row['patient_id'] : 0;
            return $this->redirect('/medical-images?patient_id=' . $pid . '&error=' . urlencode($e->getMessage()));
        }
    }
}
