<?php

declare(strict_types=1);

namespace App\Controllers\Stock;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;
use App\Services\Stock\StockService;

final class StockReportsController extends Controller
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
        $this->authorize('stock.reports.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', date('Y-m-d')));

        $svc = new StockService($this->container);
        $data = $svc->reports($from, $to);

        return $this->view('stock/reports', [
            'from' => $data['from'],
            'to' => $data['to'],
            'summary' => $data['summary'],
            'by_material' => $data['by_material'],
            'by_service' => $data['by_service'],
            'by_professional' => $data['by_professional'],
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        $this->authorize('stock.reports.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', date('Y-m-d')));

        $svc = new StockService($this->container);
        $data = $svc->reports($from, $to);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['relatorio', 'de', 'ate']);
        fputcsv($out, ['estoque', (string)$data['from'], (string)$data['to']]);
        fputcsv($out, []);

        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        fputcsv($out, ['resumo']);
        fputcsv($out, ['custo_total_saidas', (string)($summary['total_exit_cost'] ?? 0)]);
        fputcsv($out, ['custo_perdas', (string)($summary['total_loss_cost'] ?? 0)]);
        fputcsv($out, ['custo_vencimento', (string)($summary['total_expiration_cost'] ?? 0)]);
        fputcsv($out, ['custo_consumo_sessoes', (string)($summary['total_session_cost'] ?? 0)]);
        fputcsv($out, []);

        fputcsv($out, ['consumo_por_material']);
        fputcsv($out, ['material', 'unidade', 'qtd_saida', 'custo']);
        foreach (($data['by_material'] ?? []) as $it) {
            fputcsv($out, [
                (string)($it['material_name'] ?? ''),
                (string)($it['unit'] ?? ''),
                (string)($it['qty'] ?? ''),
                (string)($it['cost'] ?? ''),
            ]);
        }
        fputcsv($out, []);

        fputcsv($out, ['consumo_por_servico']);
        fputcsv($out, ['servico', 'qtd_sessoes', 'custo']);
        foreach (($data['by_service'] ?? []) as $it) {
            fputcsv($out, [
                (string)($it['service_name'] ?? ''),
                (string)($it['sessions'] ?? ''),
                (string)($it['cost'] ?? ''),
            ]);
        }
        fputcsv($out, []);

        fputcsv($out, ['consumo_por_profissional']);
        fputcsv($out, ['profissional', 'qtd_sessoes', 'custo']);
        foreach (($data['by_professional'] ?? []) as $it) {
            fputcsv($out, [
                (string)($it['professional_name'] ?? ''),
                (string)($it['sessions'] ?? ''),
                (string)($it['cost'] ?? ''),
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $filename = 'stock_reports_' . date('Ymd_His') . '.csv';
        return Response::raw((string)$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorize('stock.reports.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $dompdfClass = 'Dompdf\\Dompdf';
        if (!class_exists($dompdfClass)) {
            return Response::html('Exportação em PDF indisponível. Instale a dependência dompdf/dompdf via Composer.', 501);
        }

        $from = trim((string)$request->input('from', date('Y-m-01')));
        $to = trim((string)$request->input('to', date('Y-m-d')));

        $svc = new StockService($this->container);
        $data = $svc->reports($from, $to);

        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $html = '<!doctype html><html><head><meta charset="utf-8" />'
            . '<style>body{font-family:DejaVu Sans, sans-serif;font-size:12px}h1{font-size:16px;margin:0 0 8px}h2{font-size:13px;margin:14px 0 6px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px}th{background:#f5f5f5;text-align:left}</style>'
            . '</head><body>';

        $html .= '<h1>Relatórios de Estoque e Custos</h1>';
        $html .= '<div>Período: ' . htmlspecialchars((string)$data['from'], ENT_QUOTES, 'UTF-8') . ' a ' . htmlspecialchars((string)$data['to'], ENT_QUOTES, 'UTF-8') . '</div>';

        $html .= '<h2>Resumo</h2><table><thead><tr><th>Indicador</th><th>Valor</th></tr></thead><tbody>';
        $html .= '<tr><td>Custo total saídas</td><td>R$ ' . number_format((float)($summary['total_exit_cost'] ?? 0), 2, ',', '.') . '</td></tr>';
        $html .= '<tr><td>Custo perdas</td><td>R$ ' . number_format((float)($summary['total_loss_cost'] ?? 0), 2, ',', '.') . '</td></tr>';
        $html .= '<tr><td>Custo vencimento</td><td>R$ ' . number_format((float)($summary['total_expiration_cost'] ?? 0), 2, ',', '.') . '</td></tr>';
        $html .= '<tr><td>Custo consumo (sessões)</td><td>R$ ' . number_format((float)($summary['total_session_cost'] ?? 0), 2, ',', '.') . '</td></tr>';
        $html .= '</tbody></table>';

        $html .= '<h2>Consumo por material</h2><table><thead><tr><th>Material</th><th>Qtd saída</th><th>Custo</th></tr></thead><tbody>';
        foreach (($data['by_material'] ?? []) as $it) {
            $html .= '<tr><td>' . htmlspecialchars((string)($it['material_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . number_format((float)($it['qty'] ?? 0), 3, ',', '.') . ' ' . htmlspecialchars((string)($it['unit'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>R$ ' . number_format((float)($it['cost'] ?? 0), 2, ',', '.') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<h2>Consumo por serviço</h2><table><thead><tr><th>Serviço</th><th>Qtd sessões</th><th>Custo</th></tr></thead><tbody>';
        foreach (($data['by_service'] ?? []) as $it) {
            $html .= '<tr><td>' . htmlspecialchars((string)($it['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . (int)($it['sessions'] ?? 0) . '</td>'
                . '<td>R$ ' . number_format((float)($it['cost'] ?? 0), 2, ',', '.') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<h2>Consumo por profissional</h2><table><thead><tr><th>Profissional</th><th>Qtd sessões</th><th>Custo</th></tr></thead><tbody>';
        foreach (($data['by_professional'] ?? []) as $it) {
            $html .= '<tr><td>' . htmlspecialchars((string)($it['professional_name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . (int)($it['sessions'] ?? 0) . '</td>'
                . '<td>R$ ' . number_format((float)($it['cost'] ?? 0), 2, ',', '.') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '</body></html>';

        /** @var object $dompdf */
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        $filename = 'stock_reports_' . date('Ymd_His') . '.pdf';
        return Response::raw((string)$pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
