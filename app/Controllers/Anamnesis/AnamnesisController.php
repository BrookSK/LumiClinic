<?php

declare(strict_types=1);

namespace App\Controllers\Anamnesis;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Anamnesis\AnamnesisService;
use App\Services\Auth\AuthService;
use App\Repositories\AnamnesisResponseRepository;
use App\Repositories\ProfessionalRepository;

final class AnamnesisController extends Controller
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

    public function templates(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new AnamnesisService($this->container);
        $templates = $service->listTemplates();

        return $this->view('anamnesis/templates', [
            'templates' => $templates,
        ]);
    }

    public function createTemplate(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        return $this->view('anamnesis/templates-create');
    }

    public function storeTemplate(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $name = trim((string)$request->input('name', ''));
        $fieldsJson = trim((string)$request->input('fields_json', ''));

        if ($name === '') {
            return $this->view('anamnesis/templates-create', ['error' => 'Nome é obrigatório.']);
        }

        $fields = [];
        if ($fieldsJson !== '') {
            $decoded = json_decode($fieldsJson, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }

        $service = new AnamnesisService($this->container);
        $id = $service->createTemplate($name, is_array($fields) ? $fields : [], $request->ip());

        return $this->redirect('/anamnesis/templates/edit?id=' . $id . '&saved=1');
    }

    public function editTemplate(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/anamnesis/templates');
        }

        $service = new AnamnesisService($this->container);
        $data = $service->getTemplateWithFields($id);

        $saved = trim((string)$request->input('saved', ''));

        return $this->view('anamnesis/templates-edit', [
            'template' => $data['template'],
            'fields' => $data['fields'],
            'success' => $saved !== '' ? 'Salvo com sucesso.' : null,
        ]);
    }

    public function updateTemplate(Request $request)
    {
        $this->authorize('anamnesis.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $name = trim((string)$request->input('name', ''));
        $status = trim((string)$request->input('status', 'active'));
        $fieldsJson = trim((string)$request->input('fields_json', ''));

        if ($id <= 0 || $name === '') {
            $service = new AnamnesisService($this->container);
            $data = $service->getTemplateWithFields($id);
            return $this->view('anamnesis/templates-edit', [
                'template' => $data['template'],
                'fields' => $data['fields'],
                'error' => 'Preencha os campos obrigatórios.',
            ]);
        }

        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $fields = [];
        if ($fieldsJson !== '') {
            $decoded = json_decode($fieldsJson, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }

        $service = new AnamnesisService($this->container);
        try {
            $service->updateTemplate($id, $name, $status, is_array($fields) ? $fields : [], $request->ip());
        } catch (\RuntimeException $e) {
            $data = $service->getTemplateWithFields($id);
            return $this->view('anamnesis/templates-edit', [
                'template' => $data['template'],
                'fields' => $data['fields'],
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $data = $service->getTemplateWithFields($id);
            return $this->view('anamnesis/templates-edit', [
                'template' => $data['template'],
                'fields' => $data['fields'],
                'error' => 'Erro ao salvar.',
            ]);
        }

        return $this->redirect('/anamnesis/templates/edit?id=' . $id);
    }

    public function index(Request $request)
    {
        $this->authorize('anamnesis.fill');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new AnamnesisService($this->container);
        $data = $service->listForPatient($patientId, $request->ip(), $request->header('user-agent'));

        // Carregar templates de WhatsApp ativos
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $waTemplates = [];
        if ($clinicId !== null) {
            $waTemplates = (new \App\Repositories\WhatsappTemplateRepository($this->container->get(\PDO::class)))
                ->listByClinic($clinicId);
            $waTemplates = array_filter($waTemplates, fn($t) => (string)($t['status'] ?? 'active') === 'active');
        }

        return $this->view('anamnesis/index', [
            'patient'      => $data['patient'],
            'templates'    => $data['templates'],
            'responses'    => $data['responses'],
            'wa_templates' => array_values($waTemplates),
        ]);
    }

    public function sendLink(Request $request): \App\Core\Http\Response
    {
        $this->authorize('anamnesis.fill');

        $patientId    = (int)$request->input('patient_id', 0);
        $templateId   = (int)$request->input('template_id', 0);
        $channel      = trim((string)$request->input('channel', 'email'));
        $waCode       = trim((string)$request->input('wa_template_code', ''));

        if ($patientId <= 0 || $templateId <= 0) {
            return \App\Core\Http\Response::json(['ok' => false, 'error' => 'Parâmetros inválidos.'], 400);
        }

        try {
            $result = (new \App\Services\Anamnesis\AnamnesisLinkSendService($this->container))
                ->send($patientId, $templateId, $channel, $waCode, $request->ip());
            return \App\Core\Http\Response::json($result);
        } catch (\RuntimeException $e) {
            return \App\Core\Http\Response::json(['ok' => false, 'error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return \App\Core\Http\Response::json(['ok' => false, 'error' => 'Falha ao enviar.'], 500);
        }
    }

    public function fill(Request $request)
    {
        $this->authorize('anamnesis.fill');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $templateId = (int)$request->input('template_id', 0);
        if ($patientId <= 0 || $templateId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new AnamnesisService($this->container);
        $list = $service->listForPatient($patientId, $request->ip(), $request->header('user-agent'));
        $tpl = $service->getTemplateWithFields($templateId);

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        $defaultProfessionalId = null;
        $lockProfessional = false;
        if ($clinicId !== null && $userId !== null) {
            $prof = (new ProfessionalRepository($this->container->get(\PDO::class)))->findByUserId($clinicId, $userId);
            if ($prof !== null) {
                $defaultProfessionalId = (int)($prof['id'] ?? 0);
                if ($defaultProfessionalId !== null && $defaultProfessionalId > 0) {
                    $lockProfessional = true;
                }
            }
        }

        return $this->view('anamnesis/fill', [
            'patient' => $list['patient'],
            'template' => $tpl['template'],
            'fields' => $tpl['fields'],
            'professionals' => $service->listProfessionals(),
            'default_professional_id' => $defaultProfessionalId,
            'lock_professional' => $lockProfessional,
        ]);
    }

    public function submit(Request $request)
    {
        $this->authorize('anamnesis.fill');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $templateId = (int)$request->input('template_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);

        if ($patientId <= 0 || $templateId <= 0) {
            return $this->redirect('/patients');
        }

        $answers = [];
        foreach ($_POST as $k => $v) {
            if (str_starts_with((string)$k, 'a_')) {
                $answers[substr((string)$k, 2)] = $v;
            }
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($professionalId <= 0 && $clinicId !== null && $userId !== null) {
            $prof = (new ProfessionalRepository($this->container->get(\PDO::class)))->findByUserId($clinicId, $userId);
            if ($prof !== null) {
                $pid = (int)($prof['id'] ?? 0);
                if ($pid > 0) {
                    $professionalId = $pid;
                }
            }
        }

        $service = new AnamnesisService($this->container);
        $responseId = $service->submit(
            $patientId,
            $templateId,
            ($professionalId > 0 ? $professionalId : null),
            $answers,
            $request->ip(),
            $request->header('user-agent')
        );

        // Salvar assinatura se enviada
        $signatureDataUrl = trim((string)$request->input('signature_data_url', ''));
        if ($signatureDataUrl !== '' && $responseId > 0 && $clinicId !== null) {
            try {
                $pdo = $this->container->get(\PDO::class);
                $stmt = $pdo->prepare("
                    UPDATE anamnesis_responses
                    SET signature_data_url = :sig, signed_at = NOW()
                    WHERE id = :id AND clinic_id = :clinic_id AND deleted_at IS NULL
                    LIMIT 1
                ");
                $stmt->execute(['sig' => $signatureDataUrl, 'id' => $responseId, 'clinic_id' => $clinicId]);
            } catch (\Throwable $ignore) {}
        }

        return $this->redirect('/anamnesis?patient_id=' . $patientId);
    }

    public function response(Request $request)
    {
        $this->authorize('anamnesis.fill');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $responseId = (int)$request->input('id', 0);
        if ($responseId <= 0) {
            return $this->redirect('/patients');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new AnamnesisResponseRepository($pdo);
        $response = $repo->findById($clinicId, $responseId);
        if ($response === null) {
            return $this->redirect('/patients');
        }

        $patientId = (int)($response['patient_id'] ?? 0);
        $templateId = (int)($response['template_id'] ?? 0);
        if ($patientId <= 0 || $templateId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new AnamnesisService($this->container);
        $list = $service->listForPatient($patientId, $request->ip());
        $tpl = $service->getTemplateWithFields($templateId);

        $answers = [];
        $raw = (string)($response['answers_json'] ?? '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $answers = $decoded;
            }
        }

        return $this->view('anamnesis/response', [
            'patient' => $list['patient'],
            'template' => $tpl['template'],
            'fields' => $tpl['fields'],
            'response' => $response,
            'answers' => $answers,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('anamnesis.fill');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $responseId = (int)$request->input('id', 0);
        if ($responseId <= 0) {
            return $this->redirect('/patients');
        }

        $service = new AnamnesisService($this->container);
        try {
            $data = $service->getExportData($responseId, $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/patients?error=' . urlencode($e->getMessage()));
        }

        return $this->view('anamnesis/export', $data);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorize('anamnesis.fill');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $dompdfClass = 'Dompdf\\Dompdf';
        if (!class_exists($dompdfClass)) {
            return Response::html('Exportação em PDF indisponível. Instale a dependência dompdf/dompdf via Composer.', 501);
        }

        $responseId = (int)$request->input('id', 0);
        if ($responseId <= 0) {
            return Response::redirect('/patients');
        }

        $service = new AnamnesisService($this->container);
        try {
            $data = $service->getExportData($responseId, $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            return Response::redirect('/patients?error=' . urlencode($e->getMessage()));
        }

        $patient = is_array($data['patient'] ?? null) ? $data['patient'] : [];
        $template = is_array($data['template'] ?? null) ? $data['template'] : [];
        $response = is_array($data['response'] ?? null) ? $data['response'] : [];
        $fields = is_array($data['fields'] ?? null) ? $data['fields'] : [];
        $answers = is_array($data['answers'] ?? null) ? $data['answers'] : [];

        $fieldMap = [];
        foreach ($fields as $f) {
            if (is_array($f) && isset($f['field_key'])) {
                $k = (string)$f['field_key'];
                if ($k !== '') {
                    $fieldMap[$k] = $f;
                }
            }
        }

        $patientName = htmlspecialchars((string)($patient['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $templateName = (string)($response['template_name_snapshot'] ?? $template['name'] ?? '');
        $templateName = htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8');
        $createdAt = htmlspecialchars((string)($response['created_at'] ?? ''), ENT_QUOTES, 'UTF-8');

        $html = '<!doctype html><html><head><meta charset="utf-8" /><style>'
            . 'body{font-family:Arial,sans-serif;font-size:12px;color:#111827;}'
            . 'h1{font-size:18px;margin:0 0 8px 0;}'
            . '.meta{margin:0 0 14px 0;color:#374151;}'
            . '.q{font-weight:700;margin:10px 0 2px 0;}'
            . '.a{margin:0 0 6px 0;white-space:pre-wrap;}'
            . '</style></head><body>';

        $html .= '<h1>Anamnese</h1>';
        $html .= '<div class="meta"><div><strong>Paciente:</strong> ' . $patientName . '</div>'
            . '<div><strong>Template:</strong> ' . $templateName . '</div>'
            . '<div><strong>Registrado em:</strong> ' . $createdAt . '</div></div>';

        foreach ($answers as $k => $v) {
            $key = (string)$k;
            $label = $key;
            $type = null;
            if (isset($fieldMap[$key]) && is_array($fieldMap[$key])) {
                $label = (string)($fieldMap[$key]['label'] ?? $key);
                $type = isset($fieldMap[$key]['field_type']) ? (string)$fieldMap[$key]['field_type'] : null;
            }

            $display = '';
            if (is_array($v)) {
                $tmp = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $display = $tmp === false ? '' : $tmp;
            } else {
                $display = (string)$v;
            }

            if ($type === 'checkbox') {
                $vv = trim((string)$display);
                if ($vv === '1' || strtolower($vv) === 'true' || strtolower($vv) === 'sim') {
                    $display = 'Sim';
                } elseif ($vv === '0' || strtolower($vv) === 'false' || strtolower($vv) === 'não' || strtolower($vv) === 'nao') {
                    $display = 'Não';
                }
            }

            $html .= '<div class="q">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>';
            $html .= '<div class="a">' . nl2br(htmlspecialchars((string)$display, ENT_QUOTES, 'UTF-8')) . '</div>';
        }

        // Assinatura
        $sigDataUrl = trim((string)($response['signature_data_url'] ?? ''));
        $signedAt = trim((string)($response['signed_at'] ?? ''));
        if ($sigDataUrl !== '') {
            $signedFmt = '';
            if ($signedAt !== '') {
                try { $signedFmt = (new \DateTimeImmutable($signedAt))->format('d/m/Y H:i'); } catch (\Throwable $e) { $signedFmt = $signedAt; }
            }

            $html .= '<div style="margin-top:30px; padding-top:16px; border-top:1px solid #ddd;">';
            $html .= '<div style="font-weight:700; margin-bottom:8px;">Assinatura do paciente</div>';
            $html .= '<div style="border:1px solid #ddd; border-radius:8px; padding:12px; display:inline-block; background:#fafafa;">';
            $html .= '<img src="' . htmlspecialchars($sigDataUrl, ENT_QUOTES, 'UTF-8') . '" style="max-width:300px; height:auto;" />';
            $html .= '</div>';
            if ($signedFmt !== '') {
                $html .= '<div style="margin-top:6px; font-size:11px; color:#6b7280;">Assinado em: ' . htmlspecialchars($signedFmt, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</body></html>';

        /** @var object $dompdf */
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        $filename = 'anamnesis_' . $responseId . '.pdf';

        return Response::raw($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
