<?php

declare(strict_types=1);

namespace App\Controllers\Reports;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\PatientRepository;
use App\Services\Auth\AuthService;
use App\Services\Compliance\DataExportService;

final class FollowUpReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('patients.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $days = (int)$request->input('days', 180);
        if ($days < 30) { $days = 30; }
        if ($days > 730) { $days = 730; }

        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientRepository($pdo);
        $patients = $repo->listInactivePatients($clinicId, $days);

        // Stats
        $totalPatients = count($patients);
        $withPhone = 0;
        $withWa = 0;
        $neverCame = 0;
        foreach ($patients as $p) {
            if (trim((string)($p['phone'] ?? '')) !== '') { $withPhone++; }
            if ((int)($p['whatsapp_opt_in'] ?? 0) === 1) { $withWa++; }
            if (trim((string)($p['last_appointment_at'] ?? '')) === '') { $neverCame++; }
        }

        return $this->view('reports/follow_up', [
            'patients' => $patients,
            'days' => $days,
            'stats' => [
                'total' => $totalPatients,
                'with_phone' => $withPhone,
                'with_wa' => $withWa,
                'never_came' => $neverCame,
            ],
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorize('patients.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $dompdfClass = 'Dompdf\\Dompdf';
        if (!class_exists($dompdfClass)) {
            return Response::html('Exportação em PDF indisponível. Instale a dependência dompdf/dompdf via Composer.', 501);
        }

        @ini_set('memory_limit', '256M');

        $days = (int)$request->input('days', 180);
        if ($days < 30) { $days = 30; }
        if ($days > 730) { $days = 730; }

        $pdo = $this->container->get(\PDO::class);
        $repo = new PatientRepository($pdo);
        $patients = $repo->listInactivePatients($clinicId, $days, 200);

        // Clinic name
        $clinicName = '';
        try {
            $stmt = $pdo->prepare("SELECT name FROM clinics WHERE id = :id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['id' => $clinicId]);
            $row = $stmt->fetch();
            $clinicName = trim((string)($row['name'] ?? ''));
        } catch (\Throwable $e) {}

        $diasSemana = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
        $meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
        $dataHoje = $diasSemana[(int)date('w')] . ', ' . date('d') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');

        // Stats
        $totalPatients = count($patients);
        $withPhone = 0;
        $withWa = 0;
        $neverCame = 0;
        foreach ($patients as $p) {
            if (trim((string)($p['phone'] ?? '')) !== '') { $withPhone++; }
            if ((int)($p['whatsapp_opt_in'] ?? 0) === 1) { $withWa++; }
            if (trim((string)($p['last_appointment_at'] ?? '')) === '') { $neverCame++; }
        }

        $html = '<!doctype html><html><head><meta charset="utf-8" />'
            . '<style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#333;margin:0;padding:20px}'
            . 'h1{font-size:16px;color:#815901;margin:0 0 2px}'
            . '.meta{font-size:10px;color:#888;margin-bottom:14px}'
            . 'table{width:100%;border-collapse:collapse}'
            . 'th{background:#f9f5e8;color:#815901;font-size:10px;padding:6px;text-align:left;border-bottom:2px solid #eeb810}'
            . 'td{padding:5px 6px;border-bottom:1px solid #ddd;font-size:11px}'
            . '.footer{margin-top:14px;border-top:1px solid #ddd;padding-top:6px;font-size:9px;color:#aaa;text-align:center}'
            . '</style></head><body>';

        // Header
        $html .= '<h1>Relatório de Follow-up</h1>';
        if ($clinicName !== '') {
            $html .= '<div class="meta">' . htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $html .= '<div class="meta">Pacientes sem retorno há mais de ' . $days . ' dias · Gerado em ' . htmlspecialchars($dataHoje, ENT_QUOTES, 'UTF-8') . ' às ' . date('H:i') . '</div>';

        // Summary line
        $html .= '<div style="margin-bottom:12px;font-size:11px;">'
            . '<strong>' . $totalPatients . '</strong> pacientes · '
            . '<strong style="color:#dc2626">' . $neverCame . '</strong> nunca vieram · '
            . '<strong style="color:#16a34a">' . $withWa . '</strong> com WhatsApp · '
            . '<strong style="color:#2563eb">' . $withPhone . '</strong> com telefone'
            . '</div>';

        // Data table
        $html .= '<table><thead><tr>';
        $html .= '<th>#</th>';
        $html .= '<th>Paciente</th>';
        $html .= '<th>Última consulta</th>';
        $html .= '<th>Dias sem retorno</th>';
        $html .= '<th>Telefone</th>';
        $html .= '<th>WhatsApp</th>';
        $html .= '</tr></thead><tbody>';

        $i = 0;
        foreach ($patients as $p) {
            $i++;
            $name = htmlspecialchars((string)($p['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $lastAt = trim((string)($p['last_appointment_at'] ?? ''));
            $phone = htmlspecialchars(trim((string)($p['phone'] ?? '')), ENT_QUOTES, 'UTF-8');
            $waOptIn = (int)($p['whatsapp_opt_in'] ?? 0);

            $lastFormatted = 'Nunca';
            $daysSince = '—';
            $color = '#dc2626';
            if ($lastAt !== '') {
                $ts = strtotime($lastAt);
                $lastFormatted = date('d/m/Y', $ts);
                $diff = (int)round((time() - $ts) / 86400);
                $daysSince = (string)$diff;
                if ($diff > 365) { $color = '#dc2626'; }
                elseif ($diff > 180) { $color = '#92400e'; }
                else { $color = '#0369a1'; }
            }

            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td><strong>' . $name . '</strong></td>';
            $html .= '<td>' . $lastFormatted . '</td>';
            $html .= '<td style="color:' . $color . ';font-weight:bold">' . $daysSince . '</td>';
            $html .= '<td>' . $phone . '</td>';
            $html .= '<td>' . ($waOptIn ? 'Sim' : 'Não') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div class="footer">Relatório gerado automaticamente pelo LumiClinic · ' . htmlspecialchars($dataHoje, ENT_QUOTES, 'UTF-8') . ' às ' . date('H:i') . '</div>';
        $html .= '</body></html>';

        /** @var object $dompdf */
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $pdf = $dompdf->output();

        (new DataExportService($this->container))->record(
            'reports.followup.export_pdf',
            null,
            null,
            'pdf',
            null,
            ['days' => $days, 'total' => $totalPatients],
            $request->ip(),
            $request->header('user-agent')
        );

        $filename = 'followup_' . date('Ymd_His') . '.pdf';
        return Response::raw((string)$pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
