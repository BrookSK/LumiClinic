<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AnamnesisResponseRepository;
use App\Repositories\AnamnesisFieldRepository;
use App\Repositories\AnamnesisTemplateRepository;
use App\Repositories\PatientRepository;
use App\Services\Portal\PatientAuthService;
use App\Services\Compliance\DataExportService;
use App\Repositories\AuditLogRepository;

final class PortalAnamnesisController extends Controller
{
    public function exportPdf(Request $request): Response
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return Response::redirect('/portal/login');
        }

        $dompdfClass = 'Dompdf\\Dompdf';
        if (!class_exists($dompdfClass)) {
            return Response::html('Exportação em PDF indisponível. Instale a dependência dompdf/dompdf via Composer.', 501);
        }

        $responseId = (int)$request->input('id', 0);
        if ($responseId <= 0) {
            return Response::redirect('/portal');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new AnamnesisResponseRepository($pdo);
        $response = $repo->findById($clinicId, $responseId);
        if ($response === null) {
            return Response::redirect('/portal?error=' . urlencode('Registro inválido.'));
        }

        if ((int)($response['patient_id'] ?? 0) !== (int)$patientId) {
            return Response::redirect('/portal?error=' . urlencode('Acesso negado.'));
        }

        $templateId = (int)($response['template_id'] ?? 0);

        $patients = new PatientRepository($pdo);
        $patient = $patients->findClinicalById($clinicId, $patientId);
        if ($patient === null) {
            return Response::redirect('/portal?error=' . urlencode('Paciente inválido.'));
        }

        $tplRepo = new AnamnesisTemplateRepository($pdo);
        $tpl = $tplRepo->findById($clinicId, $templateId);
        if ($tpl === null) {
            $tpl = ['id' => $templateId, 'name' => null, 'status' => null, 'created_at' => null, 'updated_at' => null];
        }

        $fields = [];
        $fieldsRaw = (string)($response['fields_snapshot_json'] ?? '');
        if ($fieldsRaw !== '') {
            $decoded = json_decode($fieldsRaw, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }
        if ($fields === []) {
            $fieldRepo = new AnamnesisFieldRepository($pdo);
            $fields = $fieldRepo->listByTemplate($clinicId, $templateId);
        }

        $answers = [];
        $raw = (string)($response['answers_json'] ?? '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $answers = $decoded;
            }
        }

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
        $templateName = (string)($response['template_name_snapshot'] ?? $tpl['name'] ?? '');
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

        $html .= '</body></html>';

        /** @var object $dompdf */
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        (new AuditLogRepository($pdo))->log(null, $clinicId, 'portal.anamnesis.export_pdf', [
            'anamnesis_response_id' => $responseId,
            'patient_id' => $patientId,
        ], $request->ip(), null, 'patient', $patientId, $request->header('user-agent'));

        (new DataExportService($this->container))->record(
            'portal.anamnesis.export_pdf',
            'patient',
            $patientId,
            'pdf',
            null,
            ['anamnesis_response_id' => $responseId],
            $request->ip(),
            $request->header('user-agent')
        );

        $filename = 'anamnesis_' . $responseId . '.pdf';
        return Response::raw($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
