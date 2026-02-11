<?php

declare(strict_types=1);

use App\Controllers\Auth\LoginController;
use App\Controllers\Audit\AuditLogController;
use App\Controllers\Compliance\ComplianceLgpdController;
use App\Controllers\Compliance\ComplianceCertificationController;
use App\Controllers\Compliance\SecurityIncidentController;
use App\Controllers\Bi\BiController;
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
use App\Controllers\System\SystemBillingAdminController;
use App\Controllers\System\SystemPlanAdminController;
use App\Controllers\System\SystemQueueJobController;
use App\Controllers\System\SystemErrorLogController;
use App\Controllers\Users\UserController;
use App\Controllers\Patients\PatientController;
use App\Controllers\Patients\PatientPortalAccessController;
use App\Controllers\Patients\PatientContentController;
use App\Controllers\MedicalRecords\MedicalRecordController;
use App\Controllers\MedicalImages\MedicalImageController;
use App\Controllers\MedicalImages\PatientUploadModerationController;
use App\Controllers\Anamnesis\AnamnesisController;
use App\Controllers\Consent\ConsentController;
use App\Controllers\Finance\FinancialController;
use App\Controllers\Finance\PaymentController;
use App\Controllers\Finance\SalesController;
use App\Controllers\Stock\MaterialController;
use App\Controllers\Stock\MaterialMetaController;
use App\Controllers\Stock\StockAlertsController;
use App\Controllers\Stock\StockController;
use App\Controllers\Stock\StockReportsController;
use App\Controllers\Portal\AuthPatientController;
use App\Controllers\Portal\PortalController;
use App\Controllers\Portal\PortalAgendaController;
use App\Controllers\Portal\PortalDocumentsController;
use App\Controllers\Portal\PortalUploadController;
use App\Controllers\Portal\PortalNotificationsController;
use App\Controllers\Portal\PortalContentController;
use App\Controllers\Portal\PortalMetricsController;
use App\Controllers\Portal\PortalLgpdController;
use App\Controllers\Portal\PortalApiTokensController;
use App\Controllers\Portal\PortalProfileController;
use App\Controllers\Portal\PortalSecurityController;
use App\Controllers\Portal\PortalSearchController;
use App\Controllers\Api\ApiV1Controller;
use App\Controllers\Billing\WebhookController;
use App\Controllers\Billing\ClinicSubscriptionController;
use App\Controllers\Dashboard\ClinicDashboardController;
use App\Controllers\Dashboard\ProfessionalDashboardController;
use App\Controllers\Dashboard\AdminDashboardController;
use App\Controllers\Dashboard\PlatformDashboardController;
use App\Controllers\Dashboard\SystemHealthController;
use App\Controllers\Ai\AiController;
use App\Controllers\Reports\ReportsController;
use App\Controllers\Private\PrivateTutorialController;
use App\Controllers\Auth\AccessChoiceController;

$router->get('/', [DashboardController::class, 'index']);

$router->get('/login', [LoginController::class, 'show']);
$router->post('/login', [LoginController::class, 'login']);
$router->get('/choose-access', [AccessChoiceController::class, 'show']);
$router->post('/choose-access', [AccessChoiceController::class, 'choose']);
$router->get('/forgot', [LoginController::class, 'showForgot']);
$router->post('/forgot', [LoginController::class, 'forgot']);
$router->get('/reset', [LoginController::class, 'showReset']);
$router->post('/reset', [LoginController::class, 'reset']);
$router->post('/logout', [LoginController::class, 'logout']);

$router->get('/portal/login', [AuthPatientController::class, 'showLogin']);
$router->post('/portal/login', [AuthPatientController::class, 'login']);
$router->post('/portal/logout', [AuthPatientController::class, 'logout']);
$router->get('/portal/forgot', [AuthPatientController::class, 'showForgot']);
$router->post('/portal/forgot', [AuthPatientController::class, 'forgot']);
$router->get('/portal/reset', [AuthPatientController::class, 'showReset']);
$router->post('/portal/reset', [AuthPatientController::class, 'reset']);
$router->get('/portal', [PortalController::class, 'dashboard']);

$router->get('/portal/agenda', [PortalAgendaController::class, 'index']);
$router->post('/portal/agenda/confirm', [PortalAgendaController::class, 'confirm']);
$router->post('/portal/agenda/cancel-request', [PortalAgendaController::class, 'requestCancel']);
$router->post('/portal/agenda/reschedule-request', [PortalAgendaController::class, 'requestReschedule']);

$router->get('/portal/documentos', [PortalDocumentsController::class, 'index']);
$router->get('/portal/signatures/file', [PortalDocumentsController::class, 'signatureFile']);
$router->get('/portal/medical-images/file', [PortalDocumentsController::class, 'medicalImageFile']);

$router->get('/portal/busca', [PortalSearchController::class, 'index']);

$router->get('/portal/uploads', [PortalUploadController::class, 'index']);
$router->post('/portal/uploads', [PortalUploadController::class, 'submit']);

$router->get('/portal/notificacoes', [PortalNotificationsController::class, 'index']);
$router->post('/portal/notificacoes/read', [PortalNotificationsController::class, 'markRead']);

$router->get('/portal/perfil', [PortalProfileController::class, 'index']);
$router->get('/portal/seguranca', [PortalSecurityController::class, 'index']);
$router->post('/portal/seguranca/reset', [PortalSecurityController::class, 'sendReset']);

$router->get('/portal/conteudos', [PortalContentController::class, 'index']);
$router->get('/portal/metricas', [PortalMetricsController::class, 'index']);
$router->get('/portal/lgpd', [PortalLgpdController::class, 'index']);
$router->post('/portal/lgpd', [PortalLgpdController::class, 'submit']);

$router->get('/portal/api-tokens', [PortalApiTokensController::class, 'index']);
$router->post('/portal/api-tokens/create', [PortalApiTokensController::class, 'create']);
$router->post('/portal/api-tokens/revoke', [PortalApiTokensController::class, 'revoke']);

$router->get('/api/v1/me', [ApiV1Controller::class, 'me']);
$router->get('/api/v1/appointments/upcoming', [ApiV1Controller::class, 'upcomingAppointments']);

$router->post('/webhooks/asaas', [WebhookController::class, 'asaas']);
$router->post('/webhooks/mercadopago', [WebhookController::class, 'mercadopago']);

$router->get('/billing/subscription', [ClinicSubscriptionController::class, 'index']);
$router->post('/billing/subscription/change-plan', [ClinicSubscriptionController::class, 'changePlan']);
$router->post('/billing/subscription/cancel', [ClinicSubscriptionController::class, 'cancel']);
$router->post('/billing/subscription/ensure-gateway', [ClinicSubscriptionController::class, 'ensureGateway']);

$router->get('/dashboard/clinic', [ClinicDashboardController::class, 'index']);
$router->get('/dashboard/professional', [ProfessionalDashboardController::class, 'index']);
$router->get('/dashboard/admin', [AdminDashboardController::class, 'index']);
$router->get('/dashboard/platform', [PlatformDashboardController::class, 'index']);
$router->get('/dashboard/system-health', [SystemHealthController::class, 'index']);

$router->get('/ai/insights', [AiController::class, 'insights']);
$router->get('/ai/forecast', [AiController::class, 'forecast']);
$router->get('/ai/anomalies', [AiController::class, 'anomalies']);

$router->get('/reports/metrics.csv', [ReportsController::class, 'metricsCsv']);
$router->get('/reports/performance.csv', [ReportsController::class, 'performanceCsv']);

$router->get('/private/tutorial/platform', [PrivateTutorialController::class, 'platform']);
$router->post('/private/tutorial/platform', [PrivateTutorialController::class, 'platform']);
$router->get('/private/tutorial/clinic', [PrivateTutorialController::class, 'clinic']);
$router->post('/private/tutorial/clinic', [PrivateTutorialController::class, 'clinic']);

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

$router->get('/compliance/lgpd-requests', [ComplianceLgpdController::class, 'index']);
$router->post('/compliance/lgpd-requests/process', [ComplianceLgpdController::class, 'process']);
$router->get('/compliance/lgpd-requests/export', [ComplianceLgpdController::class, 'export']);
$router->post('/compliance/lgpd-requests/anonymize', [ComplianceLgpdController::class, 'anonymize']);

$router->get('/compliance/certifications', [ComplianceCertificationController::class, 'index']);
$router->post('/compliance/certifications/policies/create', [ComplianceCertificationController::class, 'createPolicy']);
$router->post('/compliance/certifications/policies/update', [ComplianceCertificationController::class, 'updatePolicy']);
$router->post('/compliance/certifications/controls/create', [ComplianceCertificationController::class, 'createControl']);
$router->post('/compliance/certifications/controls/update', [ComplianceCertificationController::class, 'updateControl']);

$router->get('/compliance/incidents', [SecurityIncidentController::class, 'index']);
$router->post('/compliance/incidents/create', [SecurityIncidentController::class, 'create']);
$router->post('/compliance/incidents/update', [SecurityIncidentController::class, 'update']);

$router->get('/bi', [BiController::class, 'index']);
$router->post('/bi/refresh', [BiController::class, 'refresh']);

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
$router->get('/finance/reports/export.csv', [FinancialController::class, 'reportsExportCsv']);
$router->get('/finance/reports/export.pdf', [FinancialController::class, 'reportsExportPdf']);

$router->get('/stock/materials', [MaterialController::class, 'index']);
$router->get('/stock/materials/create', [MaterialController::class, 'createForm']);
$router->post('/stock/materials/create', [MaterialController::class, 'create']);
$router->get('/stock/categories', [MaterialMetaController::class, 'categories']);
$router->post('/stock/categories/create', [MaterialMetaController::class, 'createCategory']);
$router->post('/stock/categories/delete', [MaterialMetaController::class, 'deleteCategory']);
$router->get('/stock/units', [MaterialMetaController::class, 'units']);
$router->post('/stock/units/create', [MaterialMetaController::class, 'createUnit']);
$router->post('/stock/units/delete', [MaterialMetaController::class, 'deleteUnit']);
$router->get('/stock/movements', [StockController::class, 'movements']);
$router->post('/stock/movements/create', [StockController::class, 'createMovement']);
$router->get('/stock/alerts', [StockAlertsController::class, 'index']);
$router->get('/stock/reports', [StockReportsController::class, 'index']);
$router->get('/stock/reports/export.csv', [StockReportsController::class, 'exportCsv']);
$router->get('/stock/reports/export.pdf', [StockReportsController::class, 'exportPdf']);

$router->get('/services', [ServiceController::class, 'index']);
$router->post('/services/create', [ServiceController::class, 'create']);

$router->get('/services/materials', [ServiceMaterialsController::class, 'index']);
$router->post('/services/materials/create', [ServiceMaterialsController::class, 'create']);
$router->post('/services/materials/delete', [ServiceMaterialsController::class, 'delete']);

$router->get('/professionals', [ProfessionalController::class, 'index']);
$router->post('/professionals/create', [ProfessionalController::class, 'create']);
$router->get('/professionals/edit', [ProfessionalController::class, 'edit']);
$router->post('/professionals/edit', [ProfessionalController::class, 'update']);
$router->post('/professionals/delete', [ProfessionalController::class, 'delete']);

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
$router->get('/patients/search-json', [PatientController::class, 'searchJson']);

$router->get('/patients/portal-access', [PatientPortalAccessController::class, 'show']);
$router->post('/patients/portal-access/ensure', [PatientPortalAccessController::class, 'ensure']);

$router->get('/patients/content', [PatientContentController::class, 'index']);
$router->post('/patients/content/create', [PatientContentController::class, 'create']);
$router->post('/patients/content/grant', [PatientContentController::class, 'grant']);

$router->get('/medical-records', [MedicalRecordController::class, 'index']);
$router->get('/medical-records/create', [MedicalRecordController::class, 'create']);
$router->post('/medical-records/create', [MedicalRecordController::class, 'store']);
$router->get('/medical-records/edit', [MedicalRecordController::class, 'edit']);
$router->post('/medical-records/edit', [MedicalRecordController::class, 'update']);

$router->get('/medical-images', [MedicalImageController::class, 'index']);
$router->post('/medical-images/upload', [MedicalImageController::class, 'upload']);
$router->post('/medical-images/upload-pair', [MedicalImageController::class, 'uploadPair']);
$router->get('/medical-images/compare', [MedicalImageController::class, 'compare']);
$router->get('/medical-images/file', [MedicalImageController::class, 'file']);

$router->get('/medical-images/moderation', [PatientUploadModerationController::class, 'index']);
$router->post('/medical-images/moderation/approve', [PatientUploadModerationController::class, 'approve']);
$router->post('/medical-images/moderation/reject', [PatientUploadModerationController::class, 'reject']);

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
$router->get('/sys/clinics/edit', [SystemClinicController::class, 'edit']);
$router->post('/sys/clinics/update', [SystemClinicController::class, 'update']);
$router->post('/sys/clinics/set-status', [SystemClinicController::class, 'setStatus']);
$router->post('/sys/clinics/delete', [SystemClinicController::class, 'delete']);

$router->get('/sys/billing', [SystemBillingAdminController::class, 'index']);
$router->get('/sys/billing/view', [SystemBillingAdminController::class, 'details']);
$router->post('/sys/billing/set-plan', [SystemBillingAdminController::class, 'setPlan']);
$router->post('/sys/billing/set-status', [SystemBillingAdminController::class, 'setStatus']);
$router->post('/sys/billing/set-gateway', [SystemBillingAdminController::class, 'setGateway']);
$router->post('/sys/billing/ensure-gateway', [SystemBillingAdminController::class, 'ensureGateway']);
$router->post('/sys/billing/grant-month', [SystemBillingAdminController::class, 'grantMonth']);
$router->post('/sys/billing/skip-month', [SystemBillingAdminController::class, 'skipMonth']);

$router->get('/sys/plans', [SystemPlanAdminController::class, 'index']);
$router->post('/sys/plans/create', [SystemPlanAdminController::class, 'create']);
$router->post('/sys/plans/update', [SystemPlanAdminController::class, 'update']);
$router->post('/sys/plans/set-status', [SystemPlanAdminController::class, 'setStatus']);

$router->get('/sys/settings/billing', [\App\Controllers\System\SystemSettingsController::class, 'billing']);
$router->post('/sys/settings/billing', [\App\Controllers\System\SystemSettingsController::class, 'billingSubmit']);

$router->get('/sys/settings/seo', [\App\Controllers\System\SystemSettingsController::class, 'seo']);
$router->post('/sys/settings/seo', [\App\Controllers\System\SystemSettingsController::class, 'seoSubmit']);

$router->get('/sys/settings/support', [\App\Controllers\System\SystemSettingsController::class, 'support']);
$router->post('/sys/settings/support', [\App\Controllers\System\SystemSettingsController::class, 'supportSubmit']);

$router->get('/sys/settings/mail', [\App\Controllers\System\SystemSettingsController::class, 'mail']);
$router->post('/sys/settings/mail', [\App\Controllers\System\SystemSettingsController::class, 'mailSubmit']);
$router->post('/sys/settings/mail/test', [\App\Controllers\System\SystemSettingsController::class, 'mailTest']);

$router->get('/sys/settings/dev-alerts', [\App\Controllers\System\SystemSettingsController::class, 'devAlerts']);
$router->post('/sys/settings/dev-alerts', [\App\Controllers\System\SystemSettingsController::class, 'devAlertsSubmit']);

$router->get('/sys/error-logs', [SystemErrorLogController::class, 'index']);
$router->get('/sys/error-logs/view', [SystemErrorLogController::class, 'details']);

$router->get('/sys/queue-jobs', [SystemQueueJobController::class, 'index']);
$router->post('/sys/queue-jobs/retry', [SystemQueueJobController::class, 'retry']);
$router->post('/sys/queue-jobs/enqueue-test', [SystemQueueJobController::class, 'enqueueTest']);
