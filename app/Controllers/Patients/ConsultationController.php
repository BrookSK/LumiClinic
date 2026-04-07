<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Patients\ConsultationService;
use App\Services\Patients\PatientService;

final class ConsultationController extends Controller
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
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $appointmentId = (int)$request->input('appointment_id', 0);
        if ($appointmentId <= 0) {
            return $this->redirect('/patients');
        }

        try {
            $svc = new ConsultationService($this->container);
            $data = $svc->getByAppointment($appointmentId, $request->ip(), $request->header('user-agent'));

            $patientId = (int)($data['appointment']['patient_id'] ?? 0);
            $patient = $patientId > 0 ? (new PatientService($this->container))->get($patientId, $request->ip()) : null;

            // Load clinical protocol if service has a linked procedure
            $protocol = null;
            $serviceId = (int)($data['appointment']['service_id'] ?? 0);
            if ($serviceId > 0) {
                $pdo = $this->container->get(\PDO::class);
                $auth = new AuthService($this->container);
                $clinicId = $auth->clinicId();
                if ($clinicId !== null) {
                    // Find procedure linked to this service
                    $svcRow = $pdo->prepare("SELECT procedure_id FROM services WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL LIMIT 1");
                    $svcRow->execute([$serviceId, $clinicId]);
                    $svcData = $svcRow->fetch(\PDO::FETCH_ASSOC);
                    $procId = $svcData ? (int)($svcData['procedure_id'] ?? 0) : 0;
                    if ($procId > 0) {
                        $procRow = $pdo->prepare("SELECT * FROM procedures WHERE id = ? AND clinic_id = ? AND deleted_at IS NULL LIMIT 1");
                        $procRow->execute([$procId, $clinicId]);
                        $procData = $procRow->fetch(\PDO::FETCH_ASSOC);
                        if ($procData) {
                            // Load protocols and steps
                            $protsStmt = $pdo->prepare("SELECT * FROM procedure_protocols WHERE procedure_id = ? AND clinic_id = ? AND status = 'active' AND deleted_at IS NULL ORDER BY sort_order, id");
                            $protsStmt->execute([$procId, $clinicId]);
                            $prots = $protsStmt->fetchAll(\PDO::FETCH_ASSOC);
                            $stepsMap = [];
                            foreach ($prots as $pr) {
                                $stepsStmt = $pdo->prepare("SELECT * FROM procedure_protocol_steps WHERE protocol_id = ? AND clinic_id = ? AND deleted_at IS NULL ORDER BY sort_order, id");
                                $stepsStmt->execute([(int)$pr['id'], $clinicId]);
                                $stepsMap[(int)$pr['id']] = $stepsStmt->fetchAll(\PDO::FETCH_ASSOC);
                            }
                            $protocol = ['procedure' => $procData, 'protocols' => $prots, 'steps' => $stepsMap];
                        }
                    }
                }
            }

            return $this->view('patients/consultation', [
                'appointment' => $data['appointment'],
                'patient' => $patient,
                'consultation' => $data['consultation'],
                'attachments' => $data['attachments'],
                'clinical_alerts' => $data['clinical_alerts'] ?? [],
                'protocol' => $protocol,
                'professionals' => (new PatientService($this->container))->listReferenceProfessionals(),
                'error' => trim((string)$request->input('error', '')),
                'success' => trim((string)$request->input('success', '')),
            ]);
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients?error=' . urlencode($e->getMessage()));
        }
    }

    public function save(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $appointmentId = (int)$request->input('appointment_id', 0);
        $executedAt = trim((string)$request->input('executed_at', ''));
        $professionalId = (int)$request->input('professional_id', 0);
        $notes = trim((string)$request->input('notes', ''));

        if ($appointmentId <= 0) {
            return $this->redirect('/patients');
        }

        try {
            $svc = new ConsultationService($this->container);
            $svc->upsert($appointmentId, $executedAt, $professionalId, ($notes === '' ? null : $notes), $request->ip(), $request->header('user-agent'));
            return $this->redirect('/patients/consultation?appointment_id=' . $appointmentId . '&success=' . urlencode('Execução registrada.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/consultation?appointment_id=' . $appointmentId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function upload(Request $request)
    {
        $this->authorize('patients.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $appointmentId = (int)$request->input('appointment_id', 0);
        if ($appointmentId <= 0) {
            return $this->redirect('/patients');
        }

        $file = $_FILES['file'] ?? null;
        if (!is_array($file)) {
            return $this->redirect('/patients/consultation?appointment_id=' . $appointmentId . '&error=' . urlencode('Arquivo inválido.'));
        }

        $note = trim((string)$request->input('note', ''));

        try {
            $svc = new ConsultationService($this->container);
            $svc->uploadAttachment($appointmentId, $file, ($note === '' ? null : $note), $request->ip(), $request->header('user-agent'));
            return $this->redirect('/patients/consultation?appointment_id=' . $appointmentId . '&success=' . urlencode('Arquivo anexado.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients/consultation?appointment_id=' . $appointmentId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function file(Request $request)
    {
        $this->authorize('patients.read');
        $this->authorize('files.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/patients');
        }

        $svc = new ConsultationService($this->container);
        return $svc->serveAttachmentFile($id, $request->ip(), $request->header('user-agent'));
    }
}
