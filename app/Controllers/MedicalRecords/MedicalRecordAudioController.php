<?php

declare(strict_types=1);

namespace App\Controllers\MedicalRecords;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\MedicalRecords\MedicalRecordAudioService;

final class MedicalRecordAudioController extends Controller
{
    private function redirectSuperAdminWithoutClinicContext(): ?\App\Core\Http\Response
    {
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if (!$isSuperAdmin) {
            return null;
        }

        if (!isset($_SESSION['clinic_id']) || (int)$_SESSION['clinic_id'] <= 0) {
            return $this->redirect('/sys/clinics');
        }

        return null;
    }

    public function transcribe(Request $request)
    {
        $this->authorize('medical_records.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $medicalRecordId = (int)$request->input('medical_record_id', 0);
        $appointmentId = (int)$request->input('appointment_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);

        $file = $_FILES['audio'] ?? null;
        if (!is_array($file)) {
            return $this->redirect('/medical-records/create?patient_id=' . $patientId . '&error=' . urlencode('Áudio não enviado.'));
        }

        try {
            $svc = new MedicalRecordAudioService($this->container);
            $res = $svc->uploadAndTranscribe([
                'patient_id' => $patientId,
                'medical_record_id' => ($medicalRecordId > 0 ? $medicalRecordId : null),
                'appointment_id' => ($appointmentId > 0 ? $appointmentId : null),
                'professional_id' => ($professionalId > 0 ? $professionalId : null),
            ], $file, $request->ip(), $request->header('user-agent'));

            $qs = http_build_query([
                'patient_id' => $patientId,
                'transcript' => (string)($res['transcript_text'] ?? ''),
                'audio_note_id' => (int)($res['audio_note_id'] ?? 0),
            ]);

            return $this->redirect('/medical-records/create?' . $qs);
        } catch (\RuntimeException $e) {
            return $this->redirect('/medical-records/create?patient_id=' . $patientId . '&error=' . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            return $this->redirect('/medical-records/create?patient_id=' . $patientId . '&error=' . urlencode('Falha ao transcrever áudio.'));
        }
    }

    public function transcribeJson(Request $request): Response
    {
        $this->authorize('medical_records.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return Response::json(['ok' => false, 'error' => 'Contexto inválido.'], 400);
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return Response::json(['ok' => false, 'error' => 'Paciente inválido.'], 400);
        }

        $medicalRecordId = (int)$request->input('medical_record_id', 0);
        $appointmentId = (int)$request->input('appointment_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $durationSeconds = (int)$request->input('duration_seconds', 0);

        $file = $_FILES['audio'] ?? null;
        if (!is_array($file)) {
            return Response::json(['ok' => false, 'error' => 'Áudio não enviado.'], 400);
        }

        try {
            $svc = new MedicalRecordAudioService($this->container);
            $res = $svc->uploadAndTranscribe([
                'patient_id' => $patientId,
                'medical_record_id' => ($medicalRecordId > 0 ? $medicalRecordId : null),
                'appointment_id' => ($appointmentId > 0 ? $appointmentId : null),
                'professional_id' => ($professionalId > 0 ? $professionalId : null),
                'duration_seconds' => ($durationSeconds > 0 ? $durationSeconds : null),
            ], $file, $request->ip(), $request->header('user-agent'));

            return Response::json([
                'ok' => true,
                'transcript' => (string)($res['transcript_text'] ?? ''),
                'audio_note_id' => (int)($res['audio_note_id'] ?? 0),
            ]);
        } catch (\RuntimeException $e) {
            return Response::json(['ok' => false, 'error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'error' => 'Falha ao transcrever áudio.'], 500);
        }
    }

    public function transcriptionStatus(Request $request): Response
    {
        $this->authorize('medical_records.create');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return Response::json(['ok' => false], 400);
        }

        $auth = new \App\Services\Auth\AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return Response::json(['ok' => false], 400);
        }

        $ent = new \App\Services\Billing\PlanEntitlementsService($this->container);
        $status = $ent->transcriptionStatus($clinicId);

        return Response::json([
            'ok' => true,
            'limit' => $status['limit'],
            'used' => $status['used'],
            'remaining' => $status['remaining'],
            'blocked' => $status['blocked'],
        ]);
    }
}
