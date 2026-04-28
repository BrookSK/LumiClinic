<?php

declare(strict_types=1);

namespace App\Controllers\MedicalRecords;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Repositories\AppointmentRepository;
use App\Services\MedicalRecords\MedicalRecordService;

final class MedicalRecordController extends Controller
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
        $this->authorize('medical_records.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        if ($patientId <= 0) {
            return $this->redirect('/patients');
        }

        $professionalId = (int)$request->input('professional_id', 0);
        $dateFrom = trim((string)$request->input('date_from', ''));
        $dateTo = trim((string)$request->input('date_to', ''));

        $service = new MedicalRecordService($this->container);
        $filters = [];
        if ($professionalId > 0) {
            $filters['professional_id'] = $professionalId;
        }
        if ($dateFrom !== '') {
            $filters['date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $filters['date_to'] = $dateTo;
        }

        $data = $filters !== []
            ? $service->timelineFiltered($patientId, $filters, $request->ip(), $request->header('user-agent'))
            : $service->timeline($patientId, $request->ip(), $request->header('user-agent'));

        return $this->view('medical-records/index', [
            'patient' => $data['patient'],
            'records' => $data['records'],
            'alerts' => $data['alerts'] ?? [],
            'allergies' => $data['allergies'] ?? [],
            'conditions' => $data['conditions'] ?? [],
            'images' => $data['images'] ?? [],
            'image_pairs' => $data['image_pairs'] ?? [],
            'professionals' => $service->listProfessionals(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
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

        $prefAttendedAt = trim((string)$request->input('attended_at', ''));
        $prefProcedureType = trim((string)$request->input('procedure_type', ''));
        $prefProfessionalId = (int)$request->input('professional_id', 0);
        $appointmentId = (int)$request->input('appointment_id', 0);

        if ($appointmentId > 0 && ($prefAttendedAt === '' || $prefProfessionalId <= 0 || $prefProcedureType === '')) {
            $auth = new AuthService($this->container);
            $clinicId = $auth->clinicId();
            if ($clinicId !== null) {
                $apptRepo = new AppointmentRepository($this->container->get(\PDO::class));
                $appt = $apptRepo->findDetailedById((int)$clinicId, $appointmentId);
                if ($appt !== null && (int)($appt['patient_id'] ?? 0) === $patientId) {
                    if ($prefAttendedAt === '') {
                        $prefAttendedAt = trim((string)($appt['started_at'] ?? ''));
                        if ($prefAttendedAt === '') {
                            $prefAttendedAt = trim((string)($appt['start_at'] ?? ''));
                        }
                    }
                    if ($prefProfessionalId <= 0) {
                        $prefProfessionalId = (int)($appt['professional_id'] ?? 0);
                    }
                    if ($prefProcedureType === '') {
                        $prefProcedureType = trim((string)($appt['service_name'] ?? ''));
                    }
                }
            }
        }

        $service = new MedicalRecordService($this->container);
        $data = $service->timeline($patientId, $request->ip(), $request->header('user-agent'));

        $error = trim((string)$request->input('error', ''));
        $success = trim((string)$request->input('success', ''));

        return $this->view('medical-records/create', [
            'patient' => $data['patient'],
            'records' => $data['records'],
            'alerts' => $data['alerts'] ?? [],
            'allergies' => $data['allergies'] ?? [],
            'conditions' => $data['conditions'] ?? [],
            'images' => $data['images'] ?? [],
            'image_pairs' => $data['image_pairs'] ?? [],
            'professionals' => $service->listProfessionals(),
            'materials' => (new \App\Services\Stock\StockService($this->container))->listMaterials(),
            'error' => $error !== '' ? $error : null,
            'success' => $success !== '' ? $success : null,
            'prefill' => [
                'attended_at' => $prefAttendedAt,
                'procedure_type' => $prefProcedureType,
                'professional_id' => $prefProfessionalId,
            ],
        ]);
    }

    public function store(Request $request)
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

        $attendedAt = trim((string)$request->input('attended_at', ''));
        $procedureType = trim((string)$request->input('procedure_type', ''));
        $professionalId = (int)$request->input('professional_id', 0);
        $clinicalDescription = trim((string)$request->input('clinical_description', ''));
        $clinicalEvolution = trim((string)$request->input('clinical_evolution', ''));
        $notes = trim((string)$request->input('notes', ''));

        if ($attendedAt === '') {
            $service = new MedicalRecordService($this->container);
            $data = $service->timeline($patientId, $request->ip());
            return $this->view('medical-records/create', [
                'patient' => $data['patient'],
                'alerts' => $data['alerts'] ?? [],
                'allergies' => $data['allergies'] ?? [],
                'conditions' => $data['conditions'] ?? [],
                'images' => $data['images'] ?? [],
                'image_pairs' => $data['image_pairs'] ?? [],
                'professionals' => $service->listProfessionals(),
                'error' => 'Data/hora do atendimento é obrigatória.',
            ]);
        }

        $attendedAt = str_replace('T', ' ', $attendedAt);
        if (strlen($attendedAt) === 16) {
            $attendedAt .= ':00';
        }

        $service = new MedicalRecordService($this->container);
        try {
            $id = $service->create($patientId, [
                'professional_id' => ($professionalId > 0 ? $professionalId : null),
                'attended_at' => $attendedAt,
                'procedure_type' => ($procedureType === '' ? null : $procedureType),
                'clinical_description' => ($clinicalDescription === '' ? null : $clinicalDescription),
                'clinical_evolution' => ($clinicalEvolution === '' ? null : $clinicalEvolution),
                'notes' => ($notes === '' ? null : $notes),
            ], $request->ip(), $request->header('user-agent'));

            // Salvar materiais usados e dar baixa no estoque
            $materialIds = $_POST['material_id'] ?? [];
            $materialQtys = $_POST['material_qty'] ?? [];
            $materialLotes = $_POST['material_lote'] ?? [];
            $materialDescs = $_POST['material_desc'] ?? [];
        if (is_array($materialIds)) {
                $auth = new AuthService($this->container);
                $clinicId = $auth->clinicId();
                $userId = $auth->userId();
                $pdo = $this->container->get(\PDO::class);

                foreach ($materialIds as $idx => $matId) {
                    $matId = (int)$matId;
                    $qty = (float)($materialQtys[$idx] ?? 1);
                    $lote = trim((string)($materialLotes[$idx] ?? ''));
                    $desc = trim((string)($materialDescs[$idx] ?? ''));
                    if ($matId <= 0 || $qty <= 0) continue;

                    try {
                        // Verificar estoque disponível
                        $matRepo = new \App\Repositories\MaterialRepository($pdo);
                        $mat = $matRepo->findById((int)$clinicId, $matId);
                        if ($mat === null) continue;
                        $currentStock = (float)($mat['stock_current'] ?? 0);
                        if ($currentStock < $qty) {
                            error_log('[MedicalRecord] Estoque insuficiente para material #' . $matId . ': disponível=' . $currentStock . ', solicitado=' . $qty);
                            continue;
                        }

                        // Registrar na tabela do prontuário
                        $pdo->prepare("INSERT INTO medical_record_materials (clinic_id, medical_record_id, material_id, quantity, lote, description, created_at) VALUES (:c, :mr, :m, :q, :l, :d, NOW())")
                            ->execute(['c' => $clinicId, 'mr' => $id, 'm' => $matId, 'q' => $qty, 'l' => $lote !== '' ? $lote : null, 'd' => $desc !== '' ? $desc : null]);

                        // Dar baixa no estoque — atualizar saldo e criar movimentação
                        $newStock = $currentStock - $qty;
                        $unitCost = (float)($mat['unit_cost'] ?? 0);
                        $totalCost = round($unitCost * $qty, 2);

                        $matRepo->updateStockCurrent((int)$clinicId, $matId, number_format($newStock, 3, '.', ''));

                        $moveRepo = new \App\Repositories\StockMovementRepository($pdo);
                        $moveRepo->create(
                            (int)$clinicId,
                            $matId,
                            'exit',
                            number_format($qty, 3, '.', ''),
                            'medical_record',
                            $id,
                            null,
                            number_format($unitCost, 2, '.', ''),
                            number_format($totalCost, 2, '.', ''),
                            'Uso em prontuário #' . $id . ($desc !== '' ? ' — ' . $desc : '') . ($lote !== '' ? ' (Lote: ' . $lote . ')' : ''),
                            $userId
                        );
                    } catch (\Throwable $e) {
                        error_log('[MedicalRecord] Material/stock error: ' . $e->getMessage());
                    }
                }
            }

            // Upload de imagens vinculadas ao prontuário
            $imageFiles = $_FILES['record_images'] ?? null;
            if (is_array($imageFiles) && isset($imageFiles['name']) && is_array($imageFiles['name'])) {
                $imgService = new \App\Services\MedicalImages\MedicalImageService($this->container);
                for ($fi = 0; $fi < count($imageFiles['name']); $fi++) {
                    if (($imageFiles['error'][$fi] ?? 4) !== UPLOAD_ERR_OK) continue;
                    if (($imageFiles['size'][$fi] ?? 0) <= 0) continue;
                    $singleFile = [
                        'name' => $imageFiles['name'][$fi],
                        'type' => $imageFiles['type'][$fi] ?? '',
                        'tmp_name' => $imageFiles['tmp_name'][$fi] ?? '',
                        'error' => $imageFiles['error'][$fi] ?? 0,
                        'size' => $imageFiles['size'][$fi] ?? 0,
                    ];
                    try {
                        $imgService->upload($patientId, [
                            'kind' => 'photo',
                            'medical_record_id' => $id,
                            'professional_id' => ($professionalId > 0 ? $professionalId : null),
                        ], $singleFile, $request->ip(), $request->header('user-agent'));
                    } catch (\Throwable $e) {
                        error_log('[MedicalRecord] Image upload error: ' . $e->getMessage());
                    }
                }
            }
        } catch (\RuntimeException $e) {
            $data = $service->timeline($patientId, $request->ip(), $request->header('user-agent'));
            return $this->view('medical-records/create', [
                'patient' => $data['patient'],
                'alerts' => $data['alerts'] ?? [],
                'allergies' => $data['allergies'] ?? [],
                'conditions' => $data['conditions'] ?? [],
                'images' => $data['images'] ?? [],
                'image_pairs' => $data['image_pairs'] ?? [],
                'professionals' => $service->listProfessionals(),
                'error' => $e->getMessage(),
            ]);
        }

        return $this->redirect('/medical-records/edit?patient_id=' . $patientId . '&id=' . $id);
    }    public function edit(Request $request)
    {
        $this->authorize('medical_records.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $id = (int)$request->input('id', 0);
        if ($patientId <= 0 || $id <= 0) {
            return $this->redirect('/patients');
        }

        $service = new MedicalRecordService($this->container);
        $data = $service->getForEdit($patientId, $id, $request->ip(), $request->header('user-agent'));
        $summary = $service->timeline($patientId, $request->ip(), $request->header('user-agent'));

        // Linked images
        $linkedImages = [];
        try {
            $auth = new AuthService($this->container);
            $clinicId = $auth->clinicId();
            if ($clinicId !== null) {
                $pdo = $this->container->get(\PDO::class);
                $stmt = $pdo->prepare("SELECT id, created_at FROM medical_images WHERE clinic_id = :c AND medical_record_id = :mr AND deleted_at IS NULL ORDER BY id");
                $stmt->execute(['c' => $clinicId, 'mr' => $id]);
                $linkedImages = $stmt->fetchAll();
            }
        } catch (\Throwable $e) {}

        // Linked materials
        $linkedMaterials = [];
        try {
            if ($clinicId !== null) {
                $stmt2 = $pdo->prepare("SELECT mrm.*, m.name AS material_name, m.unit AS material_unit FROM medical_record_materials mrm JOIN materials m ON m.id = mrm.material_id WHERE mrm.clinic_id = :c AND mrm.medical_record_id = :mr ORDER BY mrm.id");
                $stmt2->execute(['c' => $clinicId, 'mr' => $id]);
                $linkedMaterials = $stmt2->fetchAll();
            }
        } catch (\Throwable $e) {}

        return $this->view('medical-records/edit', [
            'patient' => $data['patient'],
            'record' => $data['record'],
            'alerts' => $summary['alerts'] ?? [],
            'allergies' => $summary['allergies'] ?? [],
            'conditions' => $summary['conditions'] ?? [],
            'images' => $summary['images'] ?? [],
            'image_pairs' => $summary['image_pairs'] ?? [],
            'professionals' => $service->listProfessionals(),
            'linked_images' => $linkedImages,
            'linked_materials' => $linkedMaterials,
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('medical_records.update');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $patientId = (int)$request->input('patient_id', 0);
        $id = (int)$request->input('id', 0);
        if ($patientId <= 0 || $id <= 0) {
            return $this->redirect('/patients');
        }

        $attendedAt = trim((string)$request->input('attended_at', ''));
        $procedureType = trim((string)$request->input('procedure_type', ''));
        $professionalId = (int)$request->input('professional_id', 0);
        $clinicalDescription = trim((string)$request->input('clinical_description', ''));
        $clinicalEvolution = trim((string)$request->input('clinical_evolution', ''));
        $notes = trim((string)$request->input('notes', ''));

        if ($attendedAt === '') {
            $service = new MedicalRecordService($this->container);
            $data = $service->getForEdit($patientId, $id, $request->ip(), $request->header('user-agent'));
            $summary = $service->timeline($patientId, $request->ip(), $request->header('user-agent'));
            return $this->view('medical-records/edit', [
                'patient' => $data['patient'],
                'record' => $data['record'],
                'alerts' => $summary['alerts'] ?? [],
                'allergies' => $summary['allergies'] ?? [],
                'conditions' => $summary['conditions'] ?? [],
                'images' => $summary['images'] ?? [],
                'image_pairs' => $summary['image_pairs'] ?? [],
                'professionals' => $service->listProfessionals(),
                'error' => 'Data/hora do atendimento é obrigatória.',
            ]);
        }

        $attendedAt = str_replace('T', ' ', $attendedAt);
        if (strlen($attendedAt) === 16) {
            $attendedAt .= ':00';
        }

        $service = new MedicalRecordService($this->container);
        try {
            $service->update($patientId, $id, [
                'professional_id' => ($professionalId > 0 ? $professionalId : null),
                'attended_at' => $attendedAt,
                'procedure_type' => ($procedureType === '' ? null : $procedureType),
                'clinical_description' => ($clinicalDescription === '' ? null : $clinicalDescription),
                'clinical_evolution' => ($clinicalEvolution === '' ? null : $clinicalEvolution),
                'notes' => ($notes === '' ? null : $notes),
            ], $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            $data = $service->getForEdit($patientId, $id, $request->ip(), $request->header('user-agent'));
            $summary = $service->timeline($patientId, $request->ip(), $request->header('user-agent'));
            return $this->view('medical-records/edit', [
                'patient' => $data['patient'],
                'record' => $data['record'],
                'alerts' => $summary['alerts'] ?? [],
                'allergies' => $summary['allergies'] ?? [],
                'conditions' => $summary['conditions'] ?? [],
                'images' => $summary['images'] ?? [],
                'image_pairs' => $summary['image_pairs'] ?? [],
                'professionals' => $service->listProfessionals(),
                'error' => $e->getMessage(),
            ]);
        }

        return $this->redirect('/medical-records?patient_id=' . $patientId . '#mr-' . $id);
    }
}
