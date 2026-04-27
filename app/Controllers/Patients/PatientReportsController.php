<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\PatientRepository;
use App\Repositories\WhatsappTemplateRepository;
use App\Services\Auth\AuthService;
use App\Services\Compliance\DataExportService;

final class PatientReportsController extends Controller
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

    private function getWaTemplates(int $clinicId): array
    {
        return (new WhatsappTemplateRepository($this->container->get(\PDO::class)))->listByClinic($clinicId);
    }

    public function birthdays(Request $request)
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $tab = trim((string)$request->input('tab', 'birthdays'));
        if (!in_array($tab, ['birthdays', 'followup'], true)) {
            $tab = 'birthdays';
        }

        $month = (int)$request->input('month', (int)date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }

        $days = (int)$request->input('days', 180);
        if ($days < 30) $days = 30;
        if ($days > 730) $days = 730;

        $repo = new PatientRepository($this->container->get(\PDO::class));
        $patients = $repo->listBirthdaysByMonth($clinicId, $month);
        $followUp = $repo->listInactivePatients($clinicId, $days);
        $waTemplates = $this->getWaTemplates($clinicId);

        return $this->view('patients/birthdays', [
            'patients'     => $patients,
            'follow_up'    => $followUp,
            'month'        => $month,
            'days'         => $days,
            'tab'          => $tab,
            'wa_templates' => $waTemplates,
        ]);
    }

    public function followUp(Request $request)
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $days = (int)$request->input('days', 180);
        if ($days < 30) {
            $days = 30;
        }
        if ($days > 730) {
            $days = 730;
        }

        $repo = new PatientRepository($this->container->get(\PDO::class));
        $patients = $repo->listInactivePatients($clinicId, $days);
        $waTemplates = $this->getWaTemplates($clinicId);

        return $this->view('patients/follow_up', [
            'patients'    => $patients,
            'days'        => $days,
            'wa_templates' => $waTemplates,
        ]);
    }

    public function followUpPdf(Request $request): Response
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $dompdfClass = 'Dompdf\\Dompdf';
        if (!class_exists($dompdfClass)) {
            return Response::html('Exportação em PDF indisponível. Instale a dependência dompdf/dompdf via Composer.', 501);
        }

        $days = (int)$request->input('days', 180);
        if ($days < 30) { $days = 30; }
        if ($days > 730) { $days = 730; }

        $repo = new PatientRepository($this->container->get(\PDO::class));
        $patients = $repo->listInactivePatients($clinicId, $days);

        // Clinic name
        $pdo = $this->container->get(\PDO::class);
        $clinicName = '';
        try {
            $stmt = $pdo->prepare("SELECT name FROM clinics WHERE id = :id AND deleted_at IS NULL LIMIT 1");
            $stmt->execute(['id' => $clinicId]);
            $row = $stmt->fetch();
            $clinicName = trim((string)($row['name'] ?? ''));
        } catch (\Throwable $e) {}

        // Logo
        $logoDataUri = null;
        $logoPath = realpath(__DIR__ . '/../../../public/icone_1.png');
        if (is_string($logoPath) && $logoPath !== '' && is_file($logoPath)) {
            $bin = @file_get_contents($logoPath);
            if (is_string($bin) && $bin !== '') {
                $logoDataUri = 'data:image/png;base64,' . base64_encode($bin);
            }
        }

        $diasSemana = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
        $meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
        $dataHoje = $diasSemana[(int)date('w')] . ', ' . date('d') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');

        $html = '<!doctype html><html><head><meta charset="utf-8" />'
            . '<style>'
            . 'body{font-family:DejaVu Sans, sans-serif;font-size:11px;color:#1f2937;margin:0;padding:20px}'
            . '.header{width:100%;margin-bottom:16px;border-bottom:2px solid #eeb810;padding-bottom:12px}'
            . '.header td{border:0;padding:0;vertical-align:middle}'
            . '.logo{width:42px;height:42px;object-fit:contain}'
            . '.clinic-name{font-size:16px;font-weight:bold;color:#1f2937}'
            . '.report-title{font-size:14px;font-weight:bold;color:#815901;margin:0 0 4px}'
            . '.report-meta{font-size:10px;color:#6b7280}'
            . '.summary{background:#fffdf8;border:1px solid #eeb810;border-radius:6px;padding:10px 14px;margin-bottom:14px}'
            . '.summary-value{font-size:22px;font-weight:bold;color:#815901}'
            . '.summary-label{font-size:10px;color:#6b7280;text-transform:uppercase}'
            . 'table{width:100%;border-collapse:collapse;margin-top:8px}'
            . 'th{background:#f9f5e8;color:#815901;font-size:10px;text-transform:uppercase;letter-spacing:0.3px;padding:8px 6px;text-align:left;border-bottom:2px solid #eeb810}'
            . 'td{padding:7px 6px;border-bottom:1px solid #e5e7eb;font-size:11px}'
            . 'tr:nth-child(even) td{background:#fefcf5}'
            . '.status-tag{display:inline-block;padding:2px 8px;border-radius:4px;font-size:9px;font-weight:bold}'
            . '.status-critical{background:#fef2f2;color:#dc2626}'
            . '.status-warning{background:#fffbeb;color:#92400e}'
            . '.status-info{background:#f0f9ff;color:#0369a1}'
            . '.footer{margin-top:16px;padding-top:8px;border-top:1px solid #e5e7eb;font-size:9px;color:#9ca3af;text-align:center}'
            . '</style></head><body>';

        // Header
        $html .= '<table class="header"><tr>';
        if ($logoDataUri !== null) {
            $html .= '<td style="width:50px"><img class="logo" src="' . htmlspecialchars($logoDataUri, ENT_QUOTES, 'UTF-8') . '" alt="Logo" /></td>';
        }
        $html .= '<td>';
        if ($clinicName !== '') {
            $html .= '<div class="clinic-name">' . htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $html .= '<div class="report-title">Relatório de Follow-up</div>';
        $html .= '<div class="report-meta">Pacientes sem retorno há mais de ' . $days . ' dias · Gerado em ' . htmlspecialchars($dataHoje, ENT_QUOTES, 'UTF-8') . ' às ' . date('H:i') . '</div>';
        $html .= '</td>';
        $html .= '<td style="text-align:right"><div class="report-meta">' . count($patients) . ' paciente(s)</div></td>';
        $html .= '</tr></table>';

        // Summary
        $totalPatients = count($patients);
        $withPhone = 0;
        $withWa = 0;
        $neverCame = 0;
        foreach ($patients as $p) {
            if (trim((string)($p['phone'] ?? '')) !== '') { $withPhone++; }
            if ((int)($p['whatsapp_opt_in'] ?? 0) === 1) { $withWa++; }
            if (trim((string)($p['last_appointment_at'] ?? '')) === '') { $neverCame++; }
        }

        $html .= '<table style="border:0;margin-bottom:14px"><tr>';
        $html .= '<td style="border:0;padding:8px;background:#fffdf8;border:1px solid #eeb810;border-radius:6px;text-align:center;width:25%"><div class="summary-value">' . $totalPatients . '</div><div class="summary-label">Total</div></td>';
        $html .= '<td style="border:0;width:8px"></td>';
        $html .= '<td style="border:0;padding:8px;background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;text-align:center;width:25%"><div class="summary-value" style="color:#dc2626">' . $neverCame . '</div><div class="summary-label">Nunca vieram</div></td>';
        $html .= '<td style="border:0;width:8px"></td>';
        $html .= '<td style="border:0;padding:8px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;text-align:center;width:25%"><div class="summary-value" style="color:#16a34a">' . $withWa . '</div><div class="summary-label">Com WhatsApp</div></td>';
        $html .= '<td style="border:0;width:8px"></td>';
        $html .= '<td style="border:0;padding:8px;background:#f0f9ff;border:1px solid #93c5fd;border-radius:6px;text-align:center;width:25%"><div class="summary-value" style="color:#2563eb">' . $withPhone . '</div><div class="summary-label">Com telefone</div></td>';
        $html .= '</tr></table>';

        // Table
        $html .= '<table><thead><tr>';
        $html .= '<th style="width:5%">#</th>';
        $html .= '<th style="width:30%">Paciente</th>';
        $html .= '<th style="width:20%">Última consulta</th>';
        $html .= '<th style="width:12%">Dias sem retorno</th>';
        $html .= '<th style="width:20%">Telefone</th>';
        $html .= '<th style="width:13%">WhatsApp</th>';
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
            $urgencyClass = 'status-info';
            if ($lastAt !== '') {
                $ts = strtotime($lastAt);
                $lastFormatted = date('d/m/Y', $ts);
                $diff = (int)round((time() - $ts) / 86400);
                $daysSince = (string)$diff;
                if ($diff > 365) { $urgencyClass = 'status-critical'; }
                elseif ($diff > 180) { $urgencyClass = 'status-warning'; }
            } else {
                $urgencyClass = 'status-critical';
            }

            $waLabel = $waOptIn ? '<span class="status-tag" style="background:#f0fdf4;color:#16a34a">Sim</span>' : '<span class="status-tag" style="background:#f3f4f6;color:#6b7280">Não</span>';

            $html .= '<tr>';
            $html .= '<td>' . $i . '</td>';
            $html .= '<td><strong>' . $name . '</strong></td>';
            $html .= '<td>' . $lastFormatted . '</td>';
            $html .= '<td><span class="status-tag ' . $urgencyClass . '">' . $daysSince . ' dias</span></td>';
            $html .= '<td>' . $phone . '</td>';
            $html .= '<td>' . $waLabel . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Footer
        $html .= '<div class="footer">Relatório gerado automaticamente pelo LumiClinic · ' . htmlspecialchars($dataHoje, ENT_QUOTES, 'UTF-8') . ' às ' . date('H:i') . '</div>';
        $html .= '</body></html>';

        /** @var object $dompdf */
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $pdf = $dompdf->output();

        (new DataExportService($this->container))->record(
            'patients.followup.export_pdf',
            null,
            null,
            'pdf',
            null,
            ['days' => $days, 'total' => count($patients)],
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
