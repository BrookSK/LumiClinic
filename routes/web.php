<?php

declare(strict_types=1);

use App\Controllers\Auth\LoginController;
use App\Controllers\Audit\AuditLogController;
use App\Controllers\Clinics\ClinicController;
use App\Controllers\DashboardController;
use App\Controllers\Rbac\RbacController;
use App\Controllers\Scheduling\BlockController;
use App\Controllers\Scheduling\ProfessionalController;
use App\Controllers\Scheduling\ProfessionalScheduleController;
use App\Controllers\Scheduling\ScheduleController;
use App\Controllers\Scheduling\ServiceController;
use App\Controllers\Scheduling\ServiceMaterialsController;
use App\Controllers\Settings\SettingsController;
use App\Controllers\System\SystemClinicController;
use App\Controllers\Users\UserController;
use App\Controllers\Patients\PatientController;
use App\Controllers\MedicalRecords\MedicalRecordController;
use App\Controllers\MedicalImages\MedicalImageController;
use App\Controllers\Anamnesis\AnamnesisController;
use App\Controllers\Consent\ConsentController;
use App\Controllers\Finance\FinancialController;
use App\Controllers\Finance\PaymentController;
use App\Controllers\Finance\SalesController;
use App\Controllers\Stock\MaterialController;
use App\Controllers\Stock\StockController;
use App\Controllers\Stock\StockAlertsController;
use App\Controllers\Stock\StockReportsController;

$router->get('/', [DashboardController::class, 'index']);

$router->get('/login', [LoginController::class, 'show']);
$router->post('/login', [LoginController::class, 'login']);
$router->post('/logout', [LoginController::class, 'logout']);

$router->get('/clinic', [ClinicController::class, 'edit']);
$router->post('/clinic', [ClinicController::class, 'update']);

$router->get('/clinic/working-hours', [ClinicController::class, 'workingHours']);
$router->post('/clinic/working-hours', [ClinicController::class, 'storeWorkingHour']);
$router->post('/clinic/working-hours/delete', [ClinicController::class, 'deleteWorkingHour']);

$router->get('/clinic/closed-days', [ClinicController::class, 'closedDays']);
$router->post('/clinic/closed-days', [ClinicController::class, 'storeClosedDay']);
$router->post('/clinic/closed-days/delete', [ClinicController::class, 'deleteClosedDay']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/create', [UserController::class, 'create']);
$router->post('/users/create', [UserController::class, 'store']);

$router->get('/users/edit', [UserController::class, 'edit']);
$router->post('/users/edit', [UserController::class, 'update']);
$router->post('/users/disable', [UserController::class, 'disable']);

$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings', [SettingsController::class, 'update']);

$router->get('/settings/terminology', [SettingsController::class, 'terminology']);
$router->post('/settings/terminology', [SettingsController::class, 'updateTerminology']);

$router->get('/audit-logs', [AuditLogController::class, 'index']);
$router->get('/audit-logs/export', [AuditLogController::class, 'export']);

$router->get('/schedule', [ScheduleController::class, 'index']);
$router->get('/schedule/available', [ScheduleController::class, 'available']);
$router->post('/schedule/create', [ScheduleController::class, 'create']);
$router->post('/schedule/cancel', [ScheduleController::class, 'cancel']);
$router->post('/schedule/status', [ScheduleController::class, 'updateStatus']);
$router->get('/schedule/complete-materials', [ScheduleController::class, 'completeMaterials']);
$router->post('/schedule/complete-materials', [ScheduleController::class, 'completeMaterialsSubmit']);
$router->get('/schedule/reschedule', [ScheduleController::class, 'reschedule']);
$router->post('/schedule/reschedule', [ScheduleController::class, 'rescheduleSubmit']);
$router->get('/schedule/ops', [ScheduleController::class, 'ops']);
$router->get('/schedule/logs', [ScheduleController::class, 'logs']);

$router->get('/finance/sales', [SalesController::class, 'index']);
$router->post('/finance/sales/create', [SalesController::class, 'create']);
$router->get('/finance/sales/view', [SalesController::class, 'show']);
$router->post('/finance/sales/items/add', [SalesController::class, 'addItem']);
$router->post('/finance/sales/cancel', [SalesController::class, 'cancel']);

$router->post('/finance/payments/create', [PaymentController::class, 'create']);
$router->post('/finance/payments/refund', [PaymentController::class, 'refund']);

$router->get('/finance/cashflow', [FinancialController::class, 'cashflow']);
$router->post('/finance/entries/create', [FinancialController::class, 'createEntry']);
$router->post('/finance/entries/delete', [FinancialController::class, 'deleteEntry']);

$router->get('/finance/reports', [FinancialController::class, 'reports']);

$router->get('/stock/materials', [MaterialController::class, 'index']);
$router->post('/stock/materials/create', [MaterialController::class, 'create']);
$router->get('/stock/movements', [StockController::class, 'movements']);
$router->post('/stock/movements/create', [StockController::class, 'createMovement']);
$router->get('/stock/alerts', [StockAlertsController::class, 'index']);
$router->get('/stock/reports', [StockReportsController::class, 'index']);

$router->get('/services', [ServiceController::class, 'index']);
$router->post('/services/create', [ServiceController::class, 'create']);

$router->get('/services/materials', [ServiceMaterialsController::class, 'index']);
$router->post('/services/materials/create', [ServiceMaterialsController::class, 'create']);
$router->post('/services/materials/delete', [ServiceMaterialsController::class, 'delete']);

$router->get('/professionals', [ProfessionalController::class, 'index']);
$router->post('/professionals/create', [ProfessionalController::class, 'create']);

$router->get('/blocks', [BlockController::class, 'index']);
$router->post('/blocks/create', [BlockController::class, 'create']);

$router->get('/schedule-rules', [ProfessionalScheduleController::class, 'index']);
$router->post('/schedule-rules/create', [ProfessionalScheduleController::class, 'create']);

$router->get('/rbac', [RbacController::class, 'index']);
$router->get('/rbac/edit', [RbacController::class, 'edit']);
$router->post('/rbac/edit', [RbacController::class, 'update']);
$router->post('/rbac/clone', [RbacController::class, 'clone']);
$router->post('/rbac/reset', [RbacController::class, 'reset']);

$router->get('/patients', [PatientController::class, 'index']);
$router->get('/patients/create', [PatientController::class, 'create']);
$router->post('/patients/create', [PatientController::class, 'store']);
$router->get('/patients/view', [PatientController::class, 'show']);
$router->get('/patients/edit', [PatientController::class, 'edit']);
$router->post('/patients/edit', [PatientController::class, 'update']);

$router->get('/medical-records', [MedicalRecordController::class, 'index']);
$router->get('/medical-records/create', [MedicalRecordController::class, 'create']);
$router->post('/medical-records/create', [MedicalRecordController::class, 'store']);
$router->get('/medical-records/edit', [MedicalRecordController::class, 'edit']);
$router->post('/medical-records/edit', [MedicalRecordController::class, 'update']);

$router->get('/medical-images', [MedicalImageController::class, 'index']);
$router->post('/medical-images/upload', [MedicalImageController::class, 'upload']);
$router->get('/medical-images/file', [MedicalImageController::class, 'file']);

$router->get('/anamnesis/templates', [AnamnesisController::class, 'templates']);
$router->get('/anamnesis/templates/create', [AnamnesisController::class, 'createTemplate']);
$router->post('/anamnesis/templates/create', [AnamnesisController::class, 'storeTemplate']);
$router->get('/anamnesis/templates/edit', [AnamnesisController::class, 'editTemplate']);
$router->post('/anamnesis/templates/edit', [AnamnesisController::class, 'updateTemplate']);

$router->get('/anamnesis', [AnamnesisController::class, 'index']);
$router->get('/anamnesis/fill', [AnamnesisController::class, 'fill']);
$router->post('/anamnesis/fill', [AnamnesisController::class, 'submit']);

$router->get('/consent-terms', [ConsentController::class, 'terms']);
$router->get('/consent-terms/create', [ConsentController::class, 'createTerm']);
$router->post('/consent-terms/create', [ConsentController::class, 'storeTerm']);
$router->get('/consent-terms/edit', [ConsentController::class, 'editTerm']);
$router->post('/consent-terms/edit', [ConsentController::class, 'updateTerm']);

$router->get('/consent', [ConsentController::class, 'index']);
$router->get('/consent/accept', [ConsentController::class, 'accept']);
$router->post('/consent/accept', [ConsentController::class, 'submit']);
$router->get('/signatures/file', [ConsentController::class, 'signatureFile']);

$router->get('/sys/clinics', [SystemClinicController::class, 'index']);
$router->get('/sys/clinics/create', [SystemClinicController::class, 'create']);
$router->post('/sys/clinics/create', [SystemClinicController::class, 'store']);
