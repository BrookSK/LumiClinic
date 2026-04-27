<?php

declare(strict_types=1);

namespace App\Controllers\Finance;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\Auth\AuthService;
use App\Services\Finance\SalesService;
use App\Services\Patients\PatientService;

final class SalesController extends Controller
{
    private function isProfessionalRole(): bool
    {
        $codes = $_SESSION['role_codes'] ?? [];
        return is_array($codes) && in_array('professional', $codes, true);
    }

    private function forceProfessionalIdForCurrentUser(int $clinicId): int
    {
        $auth = new AuthService($this->container);
        $userId = $auth->userId();
        if ($userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new \App\Repositories\ProfessionalRepository($this->container->get(\PDO::class));
        $prof = $repo->findByUserId($clinicId, $userId);
        if ($prof === null) {
            throw new \RuntimeException('Profissional não vinculado ao usuário.');
        }

        return (int)$prof['id'];
    }

    private function assertProfessionalOwnsSale(int $clinicId, int $saleId): void
    {
        if (!$this->isProfessionalRole()) {
            return;
        }

        $ownProfessionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
        $items = new \App\Repositories\SaleItemRepository($this->container->get(\PDO::class));
        if (!$items->saleHasProfessional($clinicId, $saleId, $ownProfessionalId)) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

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
        $this->authorize('finance.sales.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $service = new SalesService($this->container);

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $professionalId = null;
        if ($this->isProfessionalRole()) {
            $professionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            $patientId = null;
        }

        $selectedPatient = null;
        if ($patientId !== null) {
            $selectedPatient = (new \App\Repositories\PatientRepository($this->container->get(\PDO::class)))
                ->findById($clinicId, $patientId);
            if ($selectedPatient === null) {
                $patientId = null;
            }
        }

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 50);
        $page = max(1, $page);
        $perPage = max(25, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $budgetStatus = trim((string)$request->input('budget_status', ''));
        $allowedBudget = ['draft', 'sent', 'approved', 'standby', 'rejected', 'completed'];
        if ($budgetStatus !== '' && !in_array($budgetStatus, $allowedBudget, true)) {
            $budgetStatus = '';
        }

        $sales = $service->listSales($professionalId, $perPage + 1, $offset, $patientId, $budgetStatus !== '' ? $budgetStatus : null);
        $hasNext = count($sales) > $perPage;
        if ($hasNext) {
            $sales = array_slice($sales, 0, $perPage);
        }

        return $this->view('finance/sales', [
            'sales' => $sales,
            'professionals' => $service->listReferenceProfessionals(),
            'services' => $service->listServices(),
            'packages' => $service->listPackages(),
            'plans' => $service->listSubscriptionPlans(),
            'error' => trim((string)$request->input('error', '')),
            'created' => (int)$request->input('created', 0),
            'is_professional' => $this->isProfessionalRole(),
            'page' => $page,
            'per_page' => $perPage,
            'has_next' => $hasNext,
            'patient_id' => $patientId,
            'selected_patient' => $selectedPatient,
            'budget_status' => $budgetStatus,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('finance.sales.create');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $origin = trim((string)$request->input('origin', 'reception'));
        $desconto = trim((string)$request->input('desconto', '0'));
        $descontoType = trim((string)$request->input('desconto_type', 'fixed'));
        $notes = trim((string)$request->input('notes', ''));

        // Se desconto for percentual, armazenar como nota especial para recalcular depois
        // Por ora, desconto percentual é aplicado como 0 na criação e recalculado ao adicionar itens
        if ($descontoType === 'percent') {
            $pct = (float)str_replace(',', '.', $desconto);
            if ($pct < 0) $pct = 0.0;
            if ($pct > 100) $pct = 100.0;
            // Armazenar percentual nas notas para referência futura
            $pctNote = 'desconto_pct:' . number_format($pct, 2, '.', '');
            $notes = $notes !== '' ? ($notes . ' [' . $pctNote . ']') : ('[' . $pctNote . ']');
            $desconto = '0'; // será recalculado ao adicionar itens
        }

        if ($patientId <= 0) {
            return $this->redirect('/finance/sales?error=' . urlencode('Paciente é obrigatório.'));
        }

        try {
            $service = new SalesService($this->container);
            $saleId = $service->createSale($patientId, $origin, $desconto, $notes === '' ? null : $notes, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales?error=' . urlencode($e->getMessage()));
        }
    }

    public function patientSearchJson(Request $request): Response
    {
        $this->authorize('finance.sales.read');

        if ($this->isProfessionalRole()) {
            return Response::json(['items' => []]);
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return Response::json(['items' => []]);
        }

        $q = trim((string)$request->input('q', ''));
        $limit = (int)$request->input('limit', 20);
        $limit = max(1, min(30, $limit));

        $service = new PatientService($this->container);
        $rows = $service->search($q, $limit, 0);

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id' => (int)($r['id'] ?? 0),
                'name' => (string)($r['name'] ?? ''),
                'email' => (string)($r['email'] ?? ''),
                'phone' => (string)($r['phone'] ?? ''),
            ];
        }

        return Response::json(['items' => $items]);
    }

    public function show(Request $request)
    {
        $this->authorize('finance.sales.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/finance/sales');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $this->assertProfessionalOwnsSale($clinicId, $id);

        $service = new SalesService($this->container);
        $data = $service->getSale($id);
        if ($data === null) {
            return $this->redirect('/finance/sales');
        }

        return $this->view('finance/sale_view', [
            'sale' => $data['sale'],
            'items' => $data['items'],
            'payments' => $data['payments'],
            'logs' => $data['logs'],
            'procedures' => $data['procedures'] ?? [],
            'professionals' => $service->listReferenceProfessionals(),
            'services' => $service->listServices(),
            'packages' => $service->listPackages(),
            'plans' => $service->listSubscriptionPlans(),
            'error' => trim((string)$request->input('error', '')),
            'is_professional' => $this->isProfessionalRole(),
        ]);
    }

    public function addItem(Request $request)
    {
        $this->authorize('finance.sales.update');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $saleId = (int)$request->input('sale_id', 0);
        $type = trim((string)$request->input('type', ''));
        $referenceId = (int)$request->input('reference_id', 0);
        $professionalId = (int)$request->input('professional_id', 0);
        $quantity = (int)$request->input('quantity', 1);
        $unitPrice = trim((string)$request->input('unit_price', '0'));

        if ($saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        try {
            $service = new SalesService($this->container);
            $service->addItem(
                $saleId,
                $type,
                $referenceId,
                $professionalId > 0 ? $professionalId : null,
                $quantity,
                $unitPrice,
                $request->ip(),
                $request->header('user-agent')
            );
            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function removeItem(Request $request)
    {
        $this->authorize('finance.sales.update');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $saleId = (int)$request->input('sale_id', 0);
        $itemId = (int)$request->input('item_id', 0);

        if ($saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        try {
            $service = new SalesService($this->container);
            $service->removeItem($saleId, $itemId, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function printBudget(Request $request)
    {
        $this->authorize('finance.sales.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/finance/sales');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($this->isProfessionalRole()) {
            $this->assertProfessionalOwnsSale($clinicId, $id);
        }

        $service = new SalesService($this->container);
        $data = $service->getSale($id);
        if ($data === null) {
            return $this->redirect('/finance/sales');
        }

        $pdo = $this->container->get(\PDO::class);
        $clinic = (new \App\Repositories\ClinicRepository($pdo))->findById($clinicId);
        $patient = null;
        if ($data['sale']['patient_id'] !== null) {
            $patient = (new \App\Repositories\PatientRepository($pdo))->findById($clinicId, (int)$data['sale']['patient_id']);
        }

        return $this->view('finance/sale_print', [
            'sale' => $data['sale'],
            'items' => $data['items'],
            'payments' => $data['payments'],
            'services' => $service->listServices(),
            'packages' => $service->listPackages(),
            'plans' => $service->listSubscriptionPlans(),
            'professionals' => $service->listReferenceProfessionals(),
            'clinic' => $clinic,
            'patient' => $patient,
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        $this->authorize('finance.sales.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return Response::html('Sem contexto.', 403);
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return Response::html('Contexto inválido.', 403);
        }

        $professionalId = null;
        if ($this->isProfessionalRole()) {
            $professionalId = $this->forceProfessionalIdForCurrentUser($clinicId);
        }

        $patientId = (int)$request->input('patient_id', 0);
        $budgetStatus = trim((string)$request->input('budget_status', ''));
        $allowed = ['draft', 'sent', 'approved', 'standby', 'rejected', 'completed'];
        if ($budgetStatus !== '' && !in_array($budgetStatus, $allowed, true)) {
            $budgetStatus = '';
        }

        $service = new SalesService($this->container);
        $sales = $service->listSales($professionalId, 5000, 0, $patientId > 0 ? $patientId : null, $budgetStatus !== '' ? $budgetStatus : null);

        $statusLabels = ['open'=>'Aberto','paid'=>'Pago','cancelled'=>'Cancelado'];
        $budgetLabels = ['draft'=>'Rascunho','sent'=>'Enviado','approved'=>'Aprovado','standby'=>'Em espera','rejected'=>'Recusado','completed'=>'Concluído'];

        $xlsx = new \App\Services\Export\XlsxWriter();
        $xlsx->setSheetName('Orçamentos');
        $xlsx->setHeaders(['ID', 'Data', 'Paciente', 'Status Orçamento', 'Status Pagamento', 'Total Bruto', 'Desconto', 'Total Líquido', 'Pago', 'Pendente', 'Parcelas Pagas', 'Parcelas Pendentes']);
        $xlsx->setColumnFormats([0 => 'number', 5 => 'currency', 6 => 'currency', 7 => 'currency', 8 => 'currency', 9 => 'currency', 10 => 'number', 11 => 'number']);

        foreach ($sales as $s) {
            $createdAt = (string)($s['created_at'] ?? '');
            $dateFmt = '';
            try { $dateFmt = $createdAt !== '' ? (new \DateTimeImmutable($createdAt))->format('d/m/Y') : ''; } catch (\Throwable $e) {}

            $xlsx->addRow([
                (int)($s['id'] ?? 0),
                $dateFmt,
                (string)($s['patient_name'] ?? ''),
                (string)($budgetLabels[(string)($s['budget_status'] ?? '')] ?? (string)($s['budget_status'] ?? '')),
                (string)($statusLabels[(string)($s['status'] ?? '')] ?? (string)($s['status'] ?? '')),
                (float)($s['total_bruto'] ?? 0),
                (float)($s['desconto'] ?? 0),
                (float)($s['total_liquido'] ?? 0),
                (float)($s['paid_total'] ?? 0),
                (float)($s['pending_total'] ?? 0),
                (int)($s['paid_count'] ?? 0),
                (int)($s['pending_count'] ?? 0),
            ]);
        }

        $bytes = $xlsx->generate();
        $suffix = $budgetStatus !== '' ? ('_' . $budgetStatus) : '';
        $filename = 'orcamentos' . $suffix . '_' . date('Y-m-d_His') . '.xlsx';
        return Response::raw($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function cancel(Request $request)
    {
        $this->authorize('finance.sales.cancel');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $saleId = (int)$request->input('sale_id', 0);
        if ($saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        try {
            $service = new SalesService($this->container);
            $service->cancelSale($saleId, $request->ip(), $request->header('user-agent'));
            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function setBudgetStatus(Request $request)
    {
        $this->authorize('finance.sales.update');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $saleId = (int)$request->input('sale_id', 0);
        $budgetStatus = trim((string)$request->input('budget_status', ''));
        if ($saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        try {
            $service = new SalesService($this->container);
            $service->setBudgetStatus($saleId, $budgetStatus, $request->ip(), $request->header('user-agent'));

            // Aplicar desconto se enviado junto
            $descontoRaw = trim((string)$request->input('desconto', ''));
            if ($descontoRaw !== '') {
                try {
                    $service->applyDiscount($saleId, $descontoRaw, $request->ip(), $request->header('user-agent'));
                } catch (\Throwable $ignore) {}
            }

            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function sendBudget(Request $request)
    {
        $this->authorize('finance.sales.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $saleId = (int)$request->input('sale_id', 0);
        $via = trim((string)$request->input('send_via', ''));
        if ($saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/finance/sales');
        }

        $service = new SalesService($this->container);
        $data = $service->getSale($saleId);
        if ($data === null) {
            return $this->redirect('/finance/sales');
        }

        $sale = $data['sale'];
        $patientName = (string)($sale['patient_name'] ?? '');
        $patientPhone = trim((string)($sale['patient_phone'] ?? ''));
        $patientEmail = trim((string)($sale['patient_email'] ?? ''));
        $total = number_format((float)($sale['total_liquido'] ?? 0), 2, ',', '.');

        // Get clinic name
        $pdo = $this->container->get(\PDO::class);
        $clinicName = '';
        try {
            $stmt = $pdo->prepare("SELECT name FROM clinics WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $clinicId]);
            $row = $stmt->fetch();
            $clinicName = trim((string)($row['name'] ?? ''));
        } catch (\Throwable $e) {}

        // Build print URL
        $cfg = $this->container->has('config') ? $this->container->get('config') : [];
        $baseUrl = is_array($cfg) && isset($cfg['app']['base_url']) ? (string)$cfg['app']['base_url'] : '';
        if ($baseUrl === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        $printUrl = rtrim($baseUrl, '/') . '/finance/sales/print?id=' . $saleId;

        // Build items summary
        $itemsSummary = '';
        foreach ($data['items'] as $it) {
            $itemName = (string)($it['description'] ?? $it['service_name'] ?? '');
            $itemTotal = number_format((float)($it['total'] ?? 0), 2, ',', '.');
            if ($itemName !== '') {
                $itemsSummary .= "\n• {$itemName} — R$ {$itemTotal}";
            }
        }

        $sent = false;

        if ($via === 'whatsapp' && $patientPhone !== '') {
            try {
                $number = preg_replace('/\D/', '', $patientPhone);
                $msg = "Olá {$patientName}, segue seu orçamento da *{$clinicName}*:\n"
                    . $itemsSummary . "\n\n"
                    . "*Total: R$ {$total}*\n\n"
                    . "Para visualizar o orçamento completo, acesse:\n{$printUrl}";

                $client = new \App\Services\Whatsapp\EvolutionClient($this->container);
                $client->sendText($number, $msg);
                $sent = true;
            } catch (\Throwable $e) {
                error_log('[SalesController] WhatsApp send error: ' . $e->getMessage());
                return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode('Falha ao enviar WhatsApp: ' . $e->getMessage()));
            }
        } elseif ($via === 'email' && $patientEmail !== '') {
            try {
                $safeName = htmlspecialchars($patientName !== '' ? $patientName : $patientEmail, ENT_QUOTES, 'UTF-8');
                $safeClinic = htmlspecialchars($clinicName, ENT_QUOTES, 'UTF-8');
                $safeUrl = htmlspecialchars($printUrl, ENT_QUOTES, 'UTF-8');

                $itemsHtml = '';
                foreach ($data['items'] as $it) {
                    $iName = htmlspecialchars((string)($it['description'] ?? $it['service_name'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $iTotal = number_format((float)($it['total'] ?? 0), 2, ',', '.');
                    if ($iName !== '') {
                        $itemsHtml .= '<tr><td style="padding:6px 10px;border-bottom:1px solid #eee;">' . $iName . '</td><td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;font-weight:600;">R$ ' . $iTotal . '</td></tr>';
                    }
                }

                $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;">'
                    . '<div style="background:linear-gradient(135deg,#fde59f,#815901);padding:20px;text-align:center;border-radius:12px 12px 0 0;">'
                    . '<div style="font-size:18px;font-weight:800;color:#fff;">' . $safeClinic . '</div>'
                    . '<div style="font-size:13px;color:rgba(255,255,255,.85);">Orçamento</div></div>'
                    . '<div style="padding:24px;background:#fffdf8;border:1px solid #eee;border-top:0;border-radius:0 0 12px 12px;">'
                    . '<p>Olá, <strong>' . $safeName . '</strong>!</p>'
                    . '<p>Segue seu orçamento:</p>'
                    . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
                    . '<thead><tr><th style="padding:8px 10px;text-align:left;border-bottom:2px solid #eeb810;font-size:12px;color:#815901;">Item</th><th style="padding:8px 10px;text-align:right;border-bottom:2px solid #eeb810;font-size:12px;color:#815901;">Valor</th></tr></thead>'
                    . '<tbody>' . $itemsHtml . '</tbody>'
                    . '<tfoot><tr><td style="padding:10px;font-weight:800;font-size:14px;">Total</td><td style="padding:10px;text-align:right;font-weight:800;font-size:14px;color:#815901;">R$ ' . htmlspecialchars($total, ENT_QUOTES, 'UTF-8') . '</td></tr></tfoot>'
                    . '</table>'
                    . '<p style="text-align:center;"><a href="' . $safeUrl . '" style="display:inline-block;background:#815901;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">Ver orçamento completo</a></p>'
                    . '</div></div>';

                (new \App\Services\Mail\MailerService($this->container))->send(
                    $patientEmail,
                    $patientName !== '' ? $patientName : $patientEmail,
                    'Orçamento — ' . ($clinicName !== '' ? $clinicName : 'LumiClinic'),
                    $html
                );
                $sent = true;
            } catch (\Throwable $e) {
                error_log('[SalesController] Email send error: ' . $e->getMessage());
                return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode('Falha ao enviar e-mail: ' . $e->getMessage()));
            }
        } else {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode('Paciente sem ' . ($via === 'whatsapp' ? 'telefone' : 'e-mail') . ' cadastrado.'));
        }

        if ($sent) {
            // Update budget status to 'sent' if still draft
            try {
                if ((string)($sale['budget_status'] ?? '') === 'draft') {
                    $service->setBudgetStatus($saleId, 'sent', $request->ip(), $request->header('user-agent'));
                }
            } catch (\Throwable $ignore) {}
        }

        return $this->redirect('/finance/sales/view?id=' . $saleId);
    }

    public function generateAppointments(Request $request)
    {
        $this->authorize('finance.sales.update');
        $this->authorize('scheduling.create');

        if ($this->isProfessionalRole()) {
            throw new \RuntimeException('Acesso negado.');
        }

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $saleId = (int)$request->input('sale_id', 0);
        $startDate = trim((string)$request->input('start_date', ''));
        if ($saleId <= 0) {
            return $this->redirect('/finance/sales');
        }

        try {
            $service = new SalesService($this->container);
            $result = $service->generateAppointmentsFromApprovedBudget($saleId, $startDate, $request->ip(), $request->header('user-agent'));

            if ($result['errors'] !== []) {
                return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode(implode(' | ', $result['errors'])));
            }

            return $this->redirect('/finance/sales/view?id=' . $saleId);
        } catch (\RuntimeException $e) {
            return $this->redirect('/finance/sales/view?id=' . $saleId . '&error=' . urlencode($e->getMessage()));
        }
    }
}
