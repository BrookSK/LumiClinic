<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AnamnesisFieldRepository;
use App\Repositories\AnamnesisResponseRepository;
use App\Repositories\AnamnesisTemplateRepository;
use App\Repositories\AppointmentAnamnesisRequestRepository;
use App\Repositories\AuditLogRepository;

final class AppointmentAnamnesisController extends Controller
{
    public function show(Request $request)
    {
        $token = trim((string)$request->input('token', ''));
        if ($token === '') {
            return $this->view('public/anamnesis_fill', ['error' => 'Link inválido ou expirado.']);
        }

        $pdo = $this->container->get(\PDO::class);
        $tokenHash = hash('sha256', $token);

        $reqRepo = new AppointmentAnamnesisRequestRepository($pdo);
        $reqRow = $reqRepo->findValidByTokenHash($tokenHash);
        if ($reqRow === null) {
            return $this->view('public/anamnesis_fill', ['error' => 'Link inválido ou expirado.']);
        }

        $clinicId = (int)$reqRow['clinic_id'];
        $templateId = (int)$reqRow['template_id'];
        $patientId = (int)$reqRow['patient_id'];

        $tpl = (new AnamnesisTemplateRepository($pdo))->findById($clinicId, $templateId);
        if ($tpl === null) {
            return $this->view('public/anamnesis_fill', ['error' => 'Template inválido.']);
        }

        $fields = (new AnamnesisFieldRepository($pdo))->listByTemplate($clinicId, $templateId);

        return $this->view('public/anamnesis_fill', [
            'token' => $token,
            'request_row' => $reqRow,
            'template' => $tpl,
            'fields' => $fields,
            'patient_id' => $patientId,
        ]);
    }

    public function submit(Request $request)
    {
        $token = trim((string)$request->input('token', ''));
        if ($token === '') {
            return $this->view('public/anamnesis_fill', ['error' => 'Link inválido ou expirado.']);
        }

        $pdo = $this->container->get(\PDO::class);
        $tokenHash = hash('sha256', $token);

        $reqRepo = new AppointmentAnamnesisRequestRepository($pdo);
        $reqRow = $reqRepo->findValidByTokenHash($tokenHash);
        if ($reqRow === null) {
            return $this->view('public/anamnesis_fill', ['error' => 'Link inválido ou expirado.']);
        }

        $clinicId = (int)$reqRow['clinic_id'];
        $templateId = (int)$reqRow['template_id'];
        $patientId = (int)$reqRow['patient_id'];

        $tplRepo = new AnamnesisTemplateRepository($pdo);
        $tpl = $tplRepo->findById($clinicId, $templateId);
        if ($tpl === null) {
            return $this->view('public/anamnesis_fill', ['error' => 'Template inválido.']);
        }

        $fieldRepo = new AnamnesisFieldRepository($pdo);
        $fields = $fieldRepo->listByTemplate($clinicId, $templateId);

        $answers = [];
        foreach ($_POST as $k => $v) {
            if (str_starts_with((string)$k, 'a_')) {
                $answers[substr((string)$k, 2)] = $v;
            }
        }

        $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($answersJson === false) {
            return $this->view('public/anamnesis_fill', ['error' => 'Falha ao salvar respostas.']);
        }

        $fieldsSnapshotJson = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($fieldsSnapshotJson === false) {
            $fieldsSnapshotJson = null;
        }

        try {
            $pdo->beginTransaction();

            $respRepo = new AnamnesisResponseRepository($pdo);
            $responseId = $respRepo->create(
                $clinicId,
                $patientId,
                $templateId,
                isset($tpl['name']) ? (string)$tpl['name'] : null,
                isset($tpl['updated_at']) && $tpl['updated_at'] !== null ? (string)$tpl['updated_at'] : null,
                $fieldsSnapshotJson,
                null,
                (string)$answersJson,
                null
            );

            $reqRepo->markUsed($clinicId, (int)$reqRow['id'], 'submit', $responseId);

            (new AuditLogRepository($pdo))->log(null, $clinicId, 'appointments.anamnesis_public_submit', [
                'appointment_id' => (int)$reqRow['appointment_id'],
                'patient_id' => $patientId,
                'template_id' => $templateId,
                'response_id' => $responseId,
                'request_id' => (int)$reqRow['id'],
            ], $request->ip());

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return $this->view('public/anamnesis_fill', ['error' => 'Falha ao salvar respostas.']);
        }

        return $this->view('public/anamnesis_fill', [
            'success' => 'Anamnese enviada com sucesso. Obrigado!',
        ]);
    }
}
