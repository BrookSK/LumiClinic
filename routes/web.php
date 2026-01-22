<?php

declare(strict_types=1);

use App\Controllers\Auth\LoginController;
use App\Controllers\Audit\AuditLogController;
use App\Controllers\Clinics\ClinicController;
use App\Controllers\DashboardController;
use App\Controllers\Rbac\RbacController;
use App\Controllers\Settings\SettingsController;
use App\Controllers\System\SystemClinicController;
use App\Controllers\Users\UserController;

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

$router->get('/rbac', [RbacController::class, 'index']);
$router->get('/rbac/edit', [RbacController::class, 'edit']);
$router->post('/rbac/edit', [RbacController::class, 'update']);
$router->post('/rbac/clone', [RbacController::class, 'clone']);
$router->post('/rbac/reset', [RbacController::class, 'reset']);

$router->get('/sys/clinics', [SystemClinicController::class, 'index']);
$router->get('/sys/clinics/create', [SystemClinicController::class, 'create']);
$router->post('/sys/clinics/create', [SystemClinicController::class, 'store']);
